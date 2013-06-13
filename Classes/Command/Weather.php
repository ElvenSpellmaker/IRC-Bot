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
	protected $help = '!weather [location]';
	
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
	
		$tomorrow = false;
	
		$location = implode( " ", $this->arguments );
		// remove new lines and double spaces
		$location = preg_replace( '/\s\s+/', ' ', $location );
		$location = trim( $location );
		$location = urlencode( $location );
		
		echo $location;
		
		$matches = array();
		if(preg_match('/(.+)+tomorrow/', $location, $matches))
		{
			var_dump($matches);
			$tomorrow = true;
			$location = $matches[1];
		} else if($location == 'tomorrow')
		{
			$tomorrow = true;
			$location = '';
		}
		
		if ( !strlen( $location ) ) {
			$ip = $this->getUserIp();
			
			if ( !$ip ) {
				$this->say( sprintf( "Enter location. (Usage: !weather location)" ) );
				return;
			}
			
			$location = $this->getLocationNameFromIp( $ip );
			
			if ( !strlen( $location ) ) {
				$this->say( sprintf( "Enter location. (Usage: !weather location)" ) );
				return;
			}
		}
		
		$this->bot->log( "Looking for Woeid for location $location." );
		
		$locationObject = $this->getLocation( $location );
		
		if ( $locationObject ) {
			$this->bot->log( "Woeid for {$locationObject->name} is {$locationObject->woeid}" );
			
			$weather = $this->getWeather( $locationObject->woeid, $tomorrow );
			
			if ( $weather ) {
				$this->say( "Weather for {$locationObject->name}, {$locationObject->country}: " . $weather );
			} else {
				$this->say( "Weather for {$locationObject->name}, {$locationObject->country} not found." );
			}
		} else {
			$this->say( sprintf( "Location '%s' not found.", $location ) );
		}
	}
	protected function getWeather( $woeid, $tomorrow ) {
		$yql = sprintf( 'select * from weather.forecast where woeid=%d and u="c"', $woeid );
		
		$response = $this->fetch( sprintf( $this->weatherUri, urlencode( $yql ) ) );
		$jsonResponse = json_decode( $response );
		
		if ( !$jsonResponse ) {
			return false;
		}
		
		if ( !isset( $jsonResponse->query->results ) ) {
			return false;
		}
		
		$results = $jsonResponse->query->results;
		
		$tempUnit = $results->channel->units->temperature;
		
		if(!$tomorrow) // If today
		{
			$condition = $results->channel->item->condition;
			
			$windSpeed = $results->channel->wind->speed;
			$chill = $results->channel->wind->chill;
			
			$humidity = $results->channel->atmosphere->humidity;
			$visibility = $results->channel->atmosphere->visibility;
			
			$sunrise = $results->channel->astronomy->sunrise;
			$sunset = $results->channel->astronomy->sunset;
			
			$speedUnit = $results->channel->units->speed;
			$distanceUnit = $results->channel->units->distance;
			
			return $condition->text .', '. $condition->temp .'°'. $tempUnit .'. Wind Speed: '. $windSpeed . $speedUnit .', Wind Temp: '. $chill .'°'. $tempUnit .'.'.
				   ' Humidity '. $humidity .'%, Visibility '. $visibility . $distanceUnit .'. Sunrise at '. $sunrise. ' and Sunset at '. $sunset .'.';
		}
		else
		{
			$highTemp = $results->channel->item->forecast[1]->high;
			$lowTemp = $results->channel->item->forecast[1]->low;
			$weatherText = $results->channel->item->forecast[1]->text;
			$weatherDate = $results->channel->item->forecast[1]->date;
			
			return 'Tomorrow ('. $weatherDate .'), High Temp: '. $highTemp .'°'. $tempUnit .', Low Temp: '. $lowTemp .'°'. $tempUnit. '. '. $weatherText . '.';
		}
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
}