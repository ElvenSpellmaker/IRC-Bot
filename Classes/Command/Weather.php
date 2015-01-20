<?php
// Namespace
namespace Command;

/**
 * Sends the weather condition to the channel.
 *
 * @package WildBot
 * @subpackage Command
 * @author Matej Velikonja <matej@velikonja.si>
 * @author Jack Blower <Jack@elvenspellmaker.co.uk> 
 */
class Weather extends \Library\IRC\Command\Base {
	/**
	 * The command's help text.
	 * arguments[0] == Location to check
	 *
	 * @var string
	 */
	protected $help = '!weather [--set] [location]';
	
	/**
	 * The number of arguments the command needs.
	 *
	 * @var integer
	 */
	protected $argc = -1;
	
	/**
	 * Require admin, set to true if only admin may execute this.
	 * @var boolean
	 */
	protected $requireAdmin = false;
	
	/**
	 * Yahoo API key.
	 *
	 * @var string
	 */
	private $yahooKey = '';
	
	/**
	 * Location URI API call
	 *
	 * @var string
	 */
	private $locationUri = "http://where.yahooapis.com/v1/places.q('%s')?appid=%s&format=json";
	
	/**
	 * Weather URI API call
	 *
	 * @var string
	 */
	private $weatherUri = "http://query.yahooapis.com/v1/public/yql?q=%s&format=json";
	
	/**
	 * API for getting location from IP
	 *
	 * @var string
	 */
	private $ipUri = "http://ip-api.com/json/%s";
	
	/**
	 * Allows overriding the default weather place for a user.
	 * 
	 * @var array
	 */
	private $savedPlaces = array();
	
	/**
	 *
	 * @param string $yahooKey			
	 */
	public function __construct( $yahooKey ) {
		if ( empty( $yahooKey ) ) {
			throw new \Exception( 'Invalid arguments' );
		}
		
		$this->yahooKey = $yahooKey['yahooKey'];
	}
	
	/**
	 * Sends the arguments to the channel.
	 * Weather for location that user requested.
	 */
	public function command() {
	
	
		//$this->say($this->queryUser .': Sorry, weather isn\'t available right now, due to kestrel not having a required package. Hopefully Tim can get it installed tomorrow.~~');
		//return;
	
		$tomorrow = false;
		
		if(strtolower($this->arguments[0]) == '--set' && $this->arguments[1] != '')
		{	
			$this->savedPlaces[$this->queryUser] = '';
			for($i = 1; $i < count($this->arguments); $i++)
				@$this->savedPlaces[$this->queryUser] .= ($this->arguments[$i] . ' ');
			$this->say( $this-> queryUser . ': Weather for you has been set to '. $this->savedPlaces[$this->queryUser] );
			return;
		}
		
		if(!empty($this->savedPlaces[$this->queryUser]) && (empty($this->arguments[0]) || strtolower($this->arguments[0]) == 'tomorrow' ))
		{
			if(strtolower($this->arguments[0]) == 'tomorrow')
				$tomorrow = true;
			$this->arguments = array(); // Wipe the arguments array.
			$this->arguments[0] = $this->savedPlaces[$this->queryUser]; // Use the stored one instead!
		}
	
		$location = implode( " ", $this->arguments );
		// remove new lines and double spaces
		$location = preg_replace( '/\s\s+/', ' ', $location );
		$location = trim( $location );
		$location = urlencode( $location );
		
		//echo $location;
		
		$matches = array();
		if(preg_match('/(.+)+tomorrow/', $location, $matches))
		{
			//var_dump($matches);
			$tomorrow = true;
			$location = $matches[1];
		} else if($location == 'tomorrow')
		{
			$tomorrow = true;
			$location = '';
		}
		
		//$location = ucwords($location);
		$location = mb_convert_case($location, MB_CASE_TITLE);
		
		if ( !strlen( $location ) )
		{
			$ip = $this->getUserIp();
			
			if ( !$ip )
			{
				$this->say( sprintf( "No IP can be found, please enter location. (Usage: !weather location)" ) );
				return;
			}

			$location = $this->getLocationNameFromIp( $ip );
			
			if ( !strlen( $location ) )
			{
				$this->say( sprintf( "No location found for your IP, please enter location. (Usage: !weather location)" ) );
				return;
			}
		}
		
		$this->bot->log( "Looking for Woeid for location $location." );
		
		$locationObject = $this->getLocation( $location );
		
		if ( $locationObject )
		{
			$this->bot->log( "Woeid for {$locationObject->name} is {$locationObject->woeid}" );
			
			$weather = $this->getWeather( $locationObject->woeid, $tomorrow );
			
			if ( $weather )
				$this->say( $this->queryUser . ": Weather for {$locationObject->name}, {$locationObject->country}: " . $weather );
			else
				$this->say( $this-> queryUser . ": Weather for {$locationObject->name}, {$locationObject->country} not found." );
				
		} else $this->say( sprintf( "Location '%s' not found.", urldecode($location) ) );
	}
	protected function getWeather( $woeid, $tomorrow ) {
		$yql = sprintf( 'select * from weather.forecast where woeid=%d and u="c"', $woeid );
		
		$response = $this->fetch( sprintf( $this->weatherUri, urlencode( $yql ) ) );
		$jsonResponse = json_decode( $response );
		
		if ( !$jsonResponse ) return false;
		
		if ( !isset( $jsonResponse->query->results ) ) return false;
		
		$results = $jsonResponse->query->results;
		
		$tempUnit = $results->channel->units->temperature;
		
		if(!$tomorrow) // If today
		{
			//var_dump($results);
			$condition = $results->channel->item->condition;
			
			$windSpeed = round(($results->channel->wind->speed / 1.60934), 2);
			$chill = $results->channel->wind->chill;
			
			$humidity = $results->channel->atmosphere->humidity;
			$visibility = $results->channel->atmosphere->visibility;
			
			$sunrise = $results->channel->astronomy->sunrise;
			$sunset = $results->channel->astronomy->sunset;
			
			//$speedUnit = $results->channel->units->speed;
			$speedUnit = 'mph';
			$distanceUnit = $results->channel->units->distance;
			//$distanceUnit = 'miles';
			
			$highTemp = $results->channel->item->forecast[0]->high;
			$lowTemp = $results->channel->item->forecast[0]->low;
			
			return $condition->text .', '. Weather::colourTemp($condition->temp) .'°'. $tempUnit .'. Wind Speed: '. $windSpeed . $speedUnit .', Wind Temp: '. Weather::colourTemp($chill) .'°'. $tempUnit .'.'.
				   ' Humidity '. $humidity .'%, Visibility '. $visibility . $distanceUnit .'. High Temp: '. Weather::colourTemp($highTemp) .'°'. $tempUnit .', Low Temp: '. Weather::colourTemp($lowTemp) .'°'. $tempUnit .'. Sunrise at '. $sunrise. ' and Sunset at '. $sunset .'.';
		}
		else
		{
			$highTemp = $results->channel->item->forecast[1]->high;
			$lowTemp = $results->channel->item->forecast[1]->low;
			$weatherText = $results->channel->item->forecast[1]->text;
			$weatherDate = $results->channel->item->forecast[1]->date;
			
			return 'Tomorrow ('. $weatherDate .'), High Temp: '. Weather::colourTemp($highTemp) .'°'. $tempUnit .', Low Temp: '. Weather::colourTemp($lowTemp) .'°'. $tempUnit. '. '. $weatherText . '.';
		}
	}
	
	/**
	 * Colours the temperature appropriately.
	 *
 	 */
	protected static function colourTemp( $temperature )
	{
		if($temperature > 25) return Weather::colourTempString($temperature, 5, true); // Colour Dark Red and Flashing.
		else if($temperature > 18) return Weather::colourTempString($temperature, 4); // Colour Red.
		else if($temperature > 10) return Weather::colourTempString($temperature, 7); // Colour Dark Yellow.
		else if($temperature > 0) return $temperature; // Don't colour.
		else return Weather::colourTempString($temperature, 10); // Colour Teal.
	
	}
	
	protected static function colourTempString($temperature, $colourNumber, $flashing = false)
	{
		if(!$flashing) return "$colourNumber".$temperature."";
		else return "$colourNumber".$temperature."";
	}
	
	/**
	 * Returns WOEid of $location
	 *
	 * @param string $location			
	 * @return int null
	 */
	protected function getLocation( $location ) {
		$uri = sprintf( $this->locationUri, $location, $this->yahooKey );
		
		$response = $this->fetch( $uri );
		
		$jsonResponse = json_decode( $response );
		
		if ( $jsonResponse ) {
			if ( isset( $jsonResponse->places ) && isset( $jsonResponse->places->place ) && is_array( $jsonResponse->places->place ) ) {
				return array_shift( $jsonResponse->places->place );
			}
		}
		
		return null;
	}
	
	/**
	 * Returns location name of $ip.
	 *
	 * @param
	 *			$ip
	 *			
	 * @return string
	 */
	protected function getLocationNameFromIp( $ip ) {
		$uri = sprintf( $this->ipUri, $ip );
		
		$response = $this->fetch( $uri );
		
		$jsonResponse = json_decode( $response );
		
		if ( $jsonResponse ) {
			if ( isset( $jsonResponse->city ) ) {
				return $jsonResponse->city;
			}
		}
		
		return null;
	}
	
	/**
	 * Serialises to disk.
	 */
	public function serialise()
	{
		$hashFileName = "cerealeyes/WeatherHash.jack";
		return file_put_contents($hashFileName, serialize($this->savedPlaces));
	}
	 
	/**
	* Remembers the Weather database from file.
	*/ 
	public function remember()
	{
		$hashFileName = "cerealeyes/WeatherHash.jack";
		
		@$hashCereal = file_get_contents($hashFileName);
		
		if($hashCereal !== FALSE) $this->savedPlaces = unserialize($hashCereal);
		
		return $hashCereal;
	}
}
