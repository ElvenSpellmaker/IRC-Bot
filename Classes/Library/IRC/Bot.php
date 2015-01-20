<?php
/**
 * WildBot
 *
 * LICENSE: This source file is subject to Creative Commons Attribution
 * 3.0 License that is available through the world-wide-web at the following URI:
 * http://creativecommons.org/licenses/by/3.0/.  Basically you are free to adapt
 * and use this script commercially/non-commercially. My only requirement is that
 * you keep this header as an attribution to my work. Enjoy!
 *
 * @license	http://creativecommons.org/licenses/by/3.0/
 *
 * @package WildPHP
 * @subpackage Library
 *
 * @author Daniel Siepmann <coding.layne@me.com>
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
namespace Library\IRC;

/**
 * A simple IRC Bot with basic features.
 *
 * @package WildBot
 * @subpackage Library
 *			
 * @author Super3 <admin@wildphp.com>
 * @author Daniel Siepmann <coding.layne@me.com>
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
class Bot
{
	/**
	 * Holds the bot connectionName
	 */
	 private $socketName = '';

	/**
	 * Holds the server connection.
	 *
	 * @var \Library\IRC\Connection
	 */
	private $connection = null;
	
	/**
	 * Holds a socket
	 */
	private $workhorseConnection = null;
	
	private $botSocket = null;
	 
	/**
	 * serverPassword
	 *
	 * @var string
	 */
	private $serverPassword = '';
	
	/**
	 * A list of all channels the bot should connect to.
	 *
	 * @var array
	 */
	private $channel = array ();
	
	/**
	 * The name of the bot.
	 *
	 * @var string
	 */
	private $name = '';
	
	/**
	 * The nick of the bot.
	 *
	 * @var string
	 */
	private $nick = 'WildBot';
	
	/**
	 * The number of reconnects before the bot stops running.
	 *
	 * @var integer
	 */
	private $maxReconnects = 0;
	
	/**
	 * Complete file path to the log file.
	 * Configure the path, the filename is generated and added.
	 *
	 * @var string
	 */
	private $logFile = '';
	
	/**
	 * The nick of the bot from the config file.
	 * Only used in the connection, should *not* be used outside of that method.
	 * use $this->nick instead.
	 *
	 * @var string
	 */
	private $nickToUse = '';
	
	/**
	 * The original nick the bot tried to use.
	 *
	 * @var array
	 */
	private $originalNick = '';
	
	/**
	 * Defines the prefix for all commands interacting with the bot.
	 *
	 * @var String
	 */
	private $commandPrefix = '!';
	
	/**
	 * The nick counter, used to generate a available nick.
	 *
	 * @var integer
	 */
	private $nickCounter = 0;
	
	/**
	 * Contains the number of reconnects.
	 *
	 * @var integer
	 */
	private $numberOfReconnects = 0;
	
	/**
	 *	NickName list
	 *
	 *	@var array
	 */
	private $nicknames = array();

	/**
	 * Holds the reference to the file.
	 *
	 * @var type
	 */
	private $logFileHandler = null;
	
	/**
	 * Creates a new IRC Bot.
	 *
	 * @param array $configuration
	 *			The whole configuration, you can use the setters, too.
	 * @return void
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function __construct( ) { $this->connection = new \Library\IRC\Connection\Socket(); }
	
	/**
	 * Cleanup handlers.
	 */
	public function __destruct()
	{
		if ( $this->logFileHandler ) fclose( $this->logFileHandler );
		
		/*if($this->connection != null)
		{
			socket_shutdown($this->connection);
			socket_close($this->connection);
			unlink($this->connection);
		}
		This isn't working, for one reason or another. */
	}
	
	/**
	 * Connects the bot to the server.
	 *
	 * @author Super3 <admin@wildphp.com>
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function connectToServer()
	{
		if ( empty( $this->nickToUse ) ) $this->nickToUse = $this->nick;
		
		if ( $this->connection->isConnected() ) $this->connection->disconnect();
		
		$this->connection->connect();
		$this->connection->setNonBlocking();
		
		if(!empty($this->serverPassword)) $this->sendDataToServer( 'PASS ' . $this->serverPassword );
		$this->sendDataToServer( 'NICK ' . $this->nickToUse );
		$this->originalNick = $this->nickToUse;
		$this->sendDataToServer( 'USER ' . $this->nickToUse . ' Layne-Obserdia.de ' . $this->nickToUse . ' :' . $this->name );
		
		// Create Socket for Slave Process //
		$unixSocketPath = '/tmp/'. $this->socketName;
		@unlink($unixSocketPath);
		$this->botSocket = socket_create(AF_UNIX, SOCK_STREAM, 0);
		$socketBind = socket_bind($this->botSocket, $unixSocketPath);
		socket_listen($this->botSocket);
		
		$this->main();
	}
	
	/**
	 * This is the workhorse function, grabs the data from the server and
	 * displays on the browser
	 *
	 * @author Super3 <admin@wildphp.com>
	 * @author Daniel Siepmann <coding.layne@me.com>
	 * @author Hoshang Sadiq <superaktieboy@gmail.com>
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk> 
	 */
	private function main()
	{
		$clients = array($this->connection->getSocket(), $this->botSocket);
		
		while(true)
		{
			$read = $clients;
			
			$this->log("TEST: BEFORE SOCKET SELECT.");
			$write = NULL;
			$except = NULL;
			if( socket_select( $read, $write, $except, NULL ) < 1 )
			{
				$this->log( 'I\'m here \o/' );
				$message = socket_strerror( socket_last_error() );
				$this->log( $message );
				exit(1);
			}
			$this->log("TEST: AFTER SOCKET SELECT.");
			
			// If we don't have a workhorse connection, try to acquire one.
			if( !$this->workhorseConnection && in_array( $this->botSocket, $read ) )
			{
				$workhorseConnection = @socket_accept( $this->botSocket );
				$this->workhorseConnection = new \Library\IRC\Connection\Socket();
				$this->workhorseConnection->setSocket( $workhorseConnection );
				
				if( $this->workhorseConnection->getSocket()  ) // If we managed to get a connection.
				{
					$clients[] = $this->workhorseConnection->getSocket();
					$this->workhorseConnection->setNonBlocking();
					
					$nickMSG = 'NICK '. $this->originalNick .'!'. $this->nickToUse; // Tell the workhorse what our current nickname is compared to the one we saw in the config.
					$this->log($nickMSG);
					
					$this->log("TEST: NICKSYNC BEFORE WRITE.");
					$writeResult = $this->workhorseConnection->sendData( $nickMSG, strlen($nickMSG) );
					$this->log("TEST: NICKSYNC AFTER WRITE.");
					
					if ($writeResult === FALSE)
					{
						$key = array_search( $this->workhorseConnection->getSocket(), $clients );
						unset( $clients[$key] );
						$this->workhorseConnection = null; // If we couldn't write then we have lost connection and so need to update that.
					}
				}
			}
			
			if( is_object( $this->workhorseConnection ) > 0 && in_array( $this->workhorseConnection->getSocket(), $read ) ) // We have a connection from the workhorse, hoorah!
			{
				$this->log("TEST: WORKHORSE BEFORE READ.");
				$readResult = $this->workhorseConnection->getAllData();
				$this->log("TEST: WORKHORSE AFTER READ.");
				
				// NOTE: The manual is WRONG about how the results from the connection are...
				// FALSE === No data from the socket.
				// ''	=== The socket is no longer connected.
				
				if( $readResult === '' ) // If the connection terminated...
				{
					$key = array_search( $this->workhorseConnection->getSocket(), $clients );
					unset( $clients[$key] );
					$this->workhorseConnection = null; // ... nullify the connection.
				}
				else
					foreach( $readResult as $line )
					{
						$this->log("TEST: WORKHORSE --> IRC BEFORE WRITE.");
						$this->connection->sendData( $line ); // ... send the received data to the IRC server.
						$this->log( $line );
						$this->log("TEST: WORKHORSE --> IRC AFTER WRITE.");
					}
			}
			
			if( in_array( $this->connection->getSocket(), $read ) )
			{
				$data = $this->connection->getAllData();
				if($data === '') die(); // Die for now 
				foreach( $data as $line ) $this->mainHandler( $line, $clients );
			}
		}
	}
	
	/**
	 * Main handler for data from the IRC Server.
	 * 
	 * @param clients The clients sockets array.
	 */
	public function mainHandler( $data, &$clients )
	{
		$args = explode( ' ', $data );
		$this->log( $data );
		
		if ( stripos( $data, 'PRIVMSG' ) === FALSE ) // Only do the following if the message hasn't come from a message...
		{
			// Nick List //
			if($args[1] === '353')
			{
				$channel = $args[4];
				
				$args[5] = substr($args[5], 1); // Strip colon from the first name.
				
				//Parse Usernames into List //
				for($i = 5; $i < count($args) - 1; $i++)
				{
					//echo '"', $args[$i], '" "', $i, '"';
					if(preg_match('/[A-Za-z{}[\]\-\\|^`]/', $args[$i][0]))
						$nickNames[$channel][''][] = \Library\FunctionCollection::removeLineBreaks( $args[$i] );
					else
						$nickNames[$channel][$args[$i][0]][] = \Library\FunctionCollection::removeLineBreaks( substr($args[$i], 1) );
				}
				//////////////////////////////
			}
			///////////////
			
			// Check for some special situations and react:
			if ( stripos( $data, 'Nickname is already in use.' ) !== FALSE )
			{
				// The nickname is in use, create a now one using a counter
				// and try again.
				$this->nickToUse = $this->nick . ( ++$this->nickCounter );
				$this->nick = $this->nickToUse;
				$this->sendDataToServer( 'NICK ' . $this->nickToUse );
			}
			
			// We're welcome. Let's join the configured channel(s).
			if ( stripos( $data, 'End of /MOTD command' ) !== false ) $this->join_channel( $this->channel );
			
			// Something really went wrong.
			if ( stripos( $data, 'Registration Timeout' ) !== FALSE || stripos( $data, 'Erroneous Nickname' ) !== FALSE || stripos( $data, 'Closing Link' ) !== FALSE )
			{
				// If the error occurs to often, create a log entry and
				// exit.
				if ( $this->numberOfReconnects >= (int) $this->maxReconnects )
				{
					$this->log( 'Closing Link after "' . $this->numberOfReconnects . '" reconnects.', 'EXIT' );
					exit();
				}
				
				// Notice the error.
				$this->log( $data, 'CONNECTION LOST' );
				// Wait before reconnect ...
				sleep( 60 );
				++$this->numberOfReconnects;
				// ... and reconnect.
				$this->connection->connect();
				return;
			}
			
			// Play ping pong with server, to stay connected:
			if ( $args[0] == 'PING' ) $this->sendDataToServer( 'PONG ' . $args[1] );
			
		}
		
		if ( $args[1] === 'PRIVMSG' )
		{
		
			preg_match( '/:(.+)!/', $args[0], $queryUser );
			$queryUser = $queryUser[1];

			
			if( $args[3] == ":VERSION\r\n" ) $this->sendDataToServer( "NOTICE $queryUser :VERSION WildBot (Multi-Process) : v0.1 : PHP ". phpversion() ."" );
		}
		
		// Lastly if we have a connection ...
		if($this->workhorseConnection && $this->workhorseConnection->getSocket() > 0) // We have a connection from the workhorse, hoorah!
		{
			$this->log("TEST: WORKHORSE DATA BEFORE WRITE.");

			$writeResult = $this->workhorseConnection->sendData( $data ); // Write the data through to the socket for the workhorse to use.
			$this->log("TEST: WORKHORSE DATA AFTER WRITE.");
			
			if ($writeResult === FALSE)
			{
				$key = array_search( $this->workhorseConnection->getSocket(), $clients );
				unset( $clients[$key] );
				$this->workhorseConnection = null; // If we couldn't write then we have lost connection and so need to update that.
			}
		}
	}
	
	/**
	 * Displays stuff to the broswer and sends data to the server.
	 *
	 * @param string $cmd
	 *			The command to execute.
	 *			
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function sendDataToServer( $cmd )
	{
		$this->log( $cmd, 'COMMAND' );
		$this->connection->sendData( $cmd );
	}
	
	/**
	 * Joins one or multiple channel/-s.
	 *
	 * @param mixed $channel
	 *			An string or an array containing the name/-s of the channel.
	 *			
	 * @author Super3 <admin@wildphp.com>
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk> 
	 */
	private function join_channel( $channel, $key = '' )
	{
		if ( is_array( $channel ) )
			foreach ( $channel as $chan => $key ) $this->sendDataToServer( 'JOIN ' . $chan . ' ' . $key );
		else
			$this->sendDataToServer( 'JOIN ' . $channel . ' ' . $key );
	}
	
	/**
	 * Adds a log entry to the log file.
	 *
	 * @param string $log
	 *			The log entry to add.
	 * @param string $status
	 *			The status, used to prefix the log entry.
	 *
	 */
	public function log( $log, $status = '' )
	{
		if ( empty( $status ) ) $status = 'LOG';
		
		$msg = date( 'd.m.Y - H:i:s' ) . "\t  [ " . $status . " ] \t" . \Library\FunctionCollection::removeLineBreaks( $log ) . "\r\n";
		echo $msg;
		
		if ( !is_null( $this->logFileHandler ) ) fwrite( $this->logFileHandler, $msg );
	}

	// Setters
	
	/**
	 * Sets the whole configuration.
	 *
	 * @param array $configuration
	 *			The whole configuration, you can use the setters, too.
	 */
	public function configure()
	{
		$this->setSocketName( \WildBot::get('config')->socketName );
		$this->setServer( \WildBot::get('config')->server );
		$this->setServerPassword( \WildBot::get('config')->serverPassword );
		$this->setAdminPassword( \WildBot::get('config')->adminPassword );
		$this->setPort( \WildBot::get('config')->port );
		$this->setChannel( \WildBot::get('config')->channels );
		$this->setName( \WildBot::get('config')->name );
		$this->setNick( \WildBot::get('config')->nick );
		$this->setMaxReconnects( \WildBot::get('config')->max_reconnects );
		$this->setLogFile( \WildBot::get('config')->log_file );
	}
	
	/**
	 * Sets the socket name for bot communication.
	 *
	 * @param string $sn
	 *			The socket to set.
	 */
	public function setSocketName( $sn ) { $this->socketName = $sn; }
	
	/**
	 * Sets the server.
	 * E.g. irc.quakenet.org or irc.freenode.org
	 *
	 * @param string $server
	 *			The server to set.
	 */
	public function setServer( $server ) { $this->connection->setServer( $server ); }
	
	/**
	 * Sets the server password for connecting to the server.
	 *
	 * @param string $server
	 *			The server to set.
	 */
	public function setServerPassword( $password ) { $this->serverPassword = $password; }
	
	/**
	 * Sets the admin password for connecting to the server.
	 *
	 * @param string $password
	 */
	public function setAdminPassword( $password ) { $this->adminPassword = $password; }
	
	/**
	 * Sets the port.
	 * E.g. 6667
	 *
	 * @param integer $port
	 *			The port to set.
	 */
	public function setPort( $port ) { $this->connection->setPort( $port ); }
	
	/**
	 * Sets the channel.
	 * E.g. '#testchannel' or array('#testchannel','#helloWorldChannel')
	 *
	 * @param string|array $channel
	 *			The channel as string, or a set of channels as array.
	 */
	public function setChannel( $channel ) { $this->channel = (array) $channel; }
	
	/**
	 * Sets the name of the bot.
	 * "Yes give me a name!"
	 *
	 * @param string $name
	 *			The name of the bot.
	 */
	public function setName( $name ) { $this->name = (string) $name; }
	
	/**
	 * Sets the nick of the bot.
	 * "Yes give me a nick too. I love nicks."
	 *
	 * @param string $nick
	 *			The nick of the bot.
	 */
	public function setNick( $nick ) { $this->nick = (string) $nick; }
	
	/**
	 * Set a user as an admin
	 *
	 * @param string $user
	 * 			The user to set as admin.
	 */
	public function setAdmin( $user )
	{
		if(!isset($this->admins[(string) $user]))
		{
			$this->admins[(string) $user] = true;
			$nick = ltrim(substr($user, 0, strpos($user, '!')), ':');
			$this->log( 'User '.$user.' was added to admin list.' );
			$this->connection->sendData( 'PRIVMSG '. $nick .' :You are now admin. For security reasons, do not close this window.' );
		}
		return $this;
	}
	
	/**
	 * Changes an admin's nickname.
	 * Used when 
	 * @param string $user
	 *			The old nick of the admin.
	 * @param string $newNick
	 * 			The new nick of the admin.
	 */
	public function adminChange($user, $newNick)
	{
		if(isset($this->admins[(string) $user]))
		{
			$newuser = trim($newNick).substr($user, strpos($user, '!'));
			$this->removeAdmin($user)->setAdmin($newuser);
		}
		return $this;
	}

	/**
	 * Removes a user from being an admin.
	 * @param string $user
	 * 			The user's nick to remove.
	 */ 
	public function removeAdmin( $user )
	{
		if(isset($this->admins[(string) $user]))
		{
			unset($this->admins[(string) $user]);
			$this->log( 'User '. $user .' was removed from admin list.' );
		}
		return $this;
	}

	/**
	 * Returns a list of all admins if no arguments are given, else returns true if the admin exists name passed.
	 * @param string $user
	 * 				The username to check.
	 */
	public function getAdmins( $user = null )
	{
		return $user == null ? $this->admins : (isset($this->admins[$user]) ? $this->admins[$user] : false);
	}
	
	/**
	 * Sets the limit of reconnects, before the bot exits.
	 *
	 * @param integer $maxReconnects
	 *			The number of reconnects before the bot exits.
	 */
	public function setMaxReconnects( $maxReconnects ) { $this->maxReconnects = (int) $maxReconnects; }
	
	/**
	 * Sets the filepath to the log.
	 * Specify the folder and a prefix.
	 * E.g. /Users/yourname/logs/wildbot- That will result in a logfile like the
	 * following:
	 * /Users/yourname/logs/wildbot-11-12-2012.log
	 *
	 * @param string $logFile
	 *			The filepath and prefix for a logfile.
	 */
	public function setLogFile( $logFile )
	{
		$this->logFile = "log/". (string) $logFile;
		
		if ( !empty( $this->logFile ) )
		{
			$logFilePath = dirname( $this->logFile );
			if ( !is_dir( $logFilePath ) ) mkdir( $logFilePath, 0600, true );
			$this->logFile .= date( 'd-m-Y' ) . '.log';
			$this->logFileHandler = fopen( $this->logFile, 'w+' );
		}
	}
}
