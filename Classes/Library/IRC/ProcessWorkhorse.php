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
class ProcessWorkhorse
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
	private $socket = null;
	
	/**
	 * serverPassword
	 */
	private $serverPassword = '';
	
	/**
	 * adminPassword
	 */
	private $adminPassword = '';
	
	/**
	 * A list of all channels the bot should connect to.
	 */
	private $channel = array ();
	
	/**
	 * The name of the bot.
	 */
	private $name = '';
	
	/**
	 * The nick of the bot.
	 */
	private $nick = '';
	
	/**
	 * The number of reconnects before the bot stops running.
	 */
	private $maxReconnects = 0;
	
	/**
	 * Complete file path to the log file.
	 * Configure the path, the filename is generated and added.
	 */
	private $logFile = '';
	
	/**
	 * Defines the prefix for all commands interacting with the bot.
	 */
	private $commandPrefix = '!';
	
	/**
	 * All of the messages both server and client
	 */
	private $ex = array ();
	
	/**
	 * The nick counter, used to generate a available nick.
	 */
	private $nickCounter = 0;
	
	/**
	 * Contains the number of reconnects.
	 */
	private $numberOfReconnects = 0;
	
	/**
	 * All available commands.
	 * Commands are type of IRCCommand
	 */
	private $commands = array ();
	
	/**
	 * All available listeners.
	 * Listeners are type of IRCListener
	 */
	private $listeners = array ();
	
	/**
	 * All admins will be stored here
	 */
	private $admins = array ();

	/**
	 * Holds the reference to the file.
	 */
	private $logFileHandler = null;
	private static $commandString = 'Command';
	private static $listenerString = 'Listener';
	private static $serialiseStrings = array (
			'Serialise End' => 'successfully serialised!',
			'Serialise End Fail' => 'didn\'t serialise!',
			'Remember End' => 'successfully remebered all it can!',
			'Remember End Fail' => 'could not remember anything!' 
	);

	/**
	 * Cleanup handlers.
	 */
	public function __destruct()
	{
		if ( $this->logFileHandler ) fclose( $this->logFileHandler );
		
		/*if($this->socket != null)
		{
			socket_shutdown($this->socket);
			socket_close($this->socket);
			unlink($this->socket);
		}
		This isn't working, for one reason or another. */
	}
	
	/**
	 * Connects to the server in the config and starts the workhorse once a connection is found.
	 */
	public function connectToServerAndStart()
	{
		while(true)
		{
			$this->socket = @fsockopen( 'unix:///tmp/'. $this->socketName, NULL );
			
			if (!is_resource( $this->socket ))
			{
				echo 'Unable to connect to server via fsockopen with server: "' . $this->socketName ."\".\n";
				sleep(1); // Wait a second before trying again.
			}
			else $this->main(); // Run the workhorse because we have a bot connection.
		}
	}
	
	/**
	 * This is the workhorse function, grabs the data from the server and
	 * displays on the browser
	 *
	 * @author Super3 <admin@wildphp.com>
	 * @author Daniel Siepmann <coding.layne@me.com>
	 * @author Hoshang Sadiq <superaktieboy@gmail.com>
	 */
	private function main()
	{
	
		$command = '';
		$arguments = array ();
		$arr = array();

		while(true)
		{
			do
			{
				$data = fgets( $this->socket, 513 );
				$arr = stream_get_meta_data($this->socket);
			} while ($arr['timed_out']);
			
			if($data === FALSE) return; // The main bot has terminated. Return to try again to connect.
			
			// Get the response from irc:
			$args = explode( ' ', $data );
			$this->log( $data );
			
			$this->args = $args;

			if($this->args[1] === 'PRIVMSG' && $this->args[2] == $this->getNick() && (count($this->args) == 4 || count($this->args) == 5))
			{
				if($this->args[3] == ':'.$this->getCommandPrefix().'admin')
				{
					if(isset($this->args[4]) && $this->adminPassword == trim($this->args[4]))
						$this->setAdmin($this->args[0]);
					else
						$this->removeAdmin($this->args[0]);
				}
			}
			
			if($this->args[1] === 'PRIVMSG' && (count($this->args) == 5 || count($this->args) == 6))
			{
				if($this->args[3] == ':'.$this->getCommandPrefix().'help')
				{
					$this->getHelp(false); // Someone has generically asked for help! e.g. !help <foo>
					continue;
				} else if($this->args[3] == ':'. $this->getNick() .':' && strtolower(trim($this->args[4])) == 'help')
				{
					$this->getHelp(true); // Someone has addressed us for help! e.g. Cirno: help <foo>
					continue;
				}
			}
		
			/**
			 *	This section is the initial Nick swap between the bot and the Workhorse,
			 *  this tries to make sure the nick is correct assuming the config nick is taken.
			 */
			if($this->args[0] === 'NICK')
			{
				$explodedNicks = explode('!', $this->args[1]);
				$this->nick = ($this->nick === trim($explodedNicks[0])) ? trim($explodedNicks[1]) : $this->nick;
				$this->log('NICK SYNC: '. $this->nick);
			}
			
			if($this->args[1] === 'NICK')
			{
				$this->updateNick($this->args[0], $this->args[2]);
				$this->adminChange($this->args[0], $this->args[2]);
			}
			
			if($this->args[1] === 'QUIT' || $this->args[1] === 'PART')
				$this->removeAdmin($this->args[0]);

			/* @var $listener \Library\IRC\Listener\Base */
			foreach ( $this->listeners as $listener )
				if ( is_array( $listener->getKeywords() ) )
					foreach ( $listener->getKeywords() as $keyword )
						// compare listeners keyword and 1st arguments of server
						// response
						if ( $keyword === $args[1] )
							$listener->setIRCConnection( $this->socket )->setArgs( $args )->execute( $data );
			
			if ( isset( $args[3] ) )
			{
				// Explode the server response and get the command.
				// $source finds the channel or user that the command
				// originated.
				$command = substr( trim( \Library\FunctionCollection::removeLineBreaks( $args[3] ) ), 1 );

				// Check if the response was a command.
				if ( stripos( $command, $this->commandPrefix ) === 0 )
				{
					$command = strtolower( substr( $command, 1 ) );
					
					if($command == "admin" || $command == "help")
						continue;
					
					// Command does not exist:
					if ( !array_key_exists( $command, $this->commands ) )
					{
						$this->log( 'The following, not existing, command was called: "' . $command . '".', 'MISSING' );
						$this->log( 'The following commands are known by the bot: "' . implode( ',', array_keys( $this->commands ) ) . '".', 'MISSING' );
						continue;
					}
					
					$this->executeCommand( $command, $args );
					unset( $args );
				}
			}
		}
	}
	
	/**
	 * Gets help for a command.
	 */
	private function getHelp($isAddressingMe)
	{
		$position = $isAddressingMe ? 5 : 4;
		
		$command = trim( \Library\FunctionCollection::removeLineBreaks( $this->args[$position] ) );
		if( array_key_exists( $command, $this->commands ) )
		{	
			$help = $this->commands[$command]->getHelp();
			$toChan = trim($this->args[2]);
			$nick = ltrim(substr($this->args[0], 0, strpos($this->args[0], '!')), ':');
			$msg = 'PRIVMSG '. $toChan .' :'. $nick .': '. $help;
			
			$this->log( $msg );
			fwrite( $this->socket, $msg . "\r\n" );
		}
	}
	
	/**
	 * Adds a single command to the bot.
	 *
	 * @param IRCCommand $command
	 *			The command to add.
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function addCommand(\Library\IRC\Command\Base $command )
	{
		$commandName = strtolower( $this->getClassName( $command ) );
		$command->setIRCConnection( $this->socket );
		$command->setIRCBot( $this );
		$this->commands[$commandName] = $command;
		$this->log( 'The following Command was added to the Bot: "' . $commandName . '".', 'INFO' );
	}
	
	/**
	 * Executes a command.
	 */
	protected function executeCommand( $commandName, $args )
	{
		// Execute command:
		$command = $this->commands[$commandName];
		$command->setIRCConnection( $this->socket )->setArgs( $args )->executeCommand();
	}
	
	
	public function addListener(\Library\IRC\Listener\Base $listener )
	{
		$listenerName = $this->getClassName( $listener );
		$listener->setIRCConnection( $this->socket );
		$listener->setIRCBot( $this );
		$this->listeners[$listenerName] = $listener;
		$this->log( 'The following Listener was added to the Bot: "' . $listenerName . '".', 'INFO' );
	}
	
	/**
	 * Returns class name of $object without namespace
	 *
	 * @param mixed $object			
	 * @author Matej Velikonja <matej@velikonja.si>
	 * @return string
	 */
	private function getClassName( $object )
	{
		$objectName = explode( '\\', get_class( $object ) );
		$objectName = $objectName[count( $objectName ) - 1];
		
		return $objectName;
	}
	
	/**
	 * Adds a log entry to the log file.
	 *
	 * @param string $log
	 *			The log entry to add.
	 * @param string $status
	 *			The status, used to prefix the log entry.
	 *			
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function log( $log, $status = '' )
	{
		if ( empty( $status ) ) {
			$status = 'LOG';
		}
		
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
	 * @author Daniel Siepmann <coding.layne@me.com>
	 */
	public function configure() {
		$this->setSocketName( \WorkHorse::get('config')->socketName );
		$this->setServerPassword( \WorkHorse::get('config')->serverPassword );
		$this->setAdminPassword( \WorkHorse::get('config')->adminPassword );
		$this->setCommandPrefix( \WorkHorse::get('config')->commandPrefix );
		$this->setChannel( \WorkHorse::get('config')->channels );
		$this->setName( \WorkHorse::get('config')->name );
		$this->setNick( \WorkHorse::get('config')->nick );
		$this->setMaxReconnects( \WorkHorse::get('config')->max_reconnects );
		$this->setLogFile( \WorkHorse::get('config')->log_file );
	}
	
	/**
	 * Sets the socket name for bot communication.
	 *
	 * @param string $sn
	 *			The socket to set.
	 */
	public function setSocketName( $sn) { $this->socketName = $sn; }
	
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
	
	public function updateNick( $oldNick, $newNick )
	{
		$oldNick = explode('!', $oldNick );
		$oldNick = explode(':', $oldNick[0] );
		$newNick = explode(':', $newNick );
	
		if($this->nick == $oldNick[1])
			$this->nick = $newNick[1];
	}
	
	/**
	 * Set a user as an admin
	 *
	 * @param string $user
	 *			The user to set as admin.
	 */
	public function setAdmin( $user )
	{
		if(!isset($this->admins[(string) $user]))
		{
			$this->admins[(string) $user] = true;
			$nick = ltrim(substr($user, 0, strpos($user, '!')), ':');
			$this->log( 'User '.$user.' was added to admin list.' );
			$msg = 'PRIVMSG '.$nick.' :You are now admin. For security reasons, do not close this window.';
			fwrite( $this->socket, $msg . "\r\n" );
		}
		return $this;
	}
	
	/**
	 * Changes an admin's name to $newnick whose old name was $user. 
	 */
	public function adminChange($user, $newnick)
	{
		if(isset($this->admins[(string) $user]))
		{
			$newuser = trim($newnick).substr($user, strpos($user, '!'));
			$this->removeAdmin($user)->setAdmin($newuser);
		}
		return $this;
	}

	/**
	 * Removes an admin from the admin list.
	 */
	public function removeAdmin( $user ) 
	{
		if(isset($this->admins[(string) $user]))
		{
			unset($this->admins[(string) $user]);
			$this->log( 'User '.$user.' was removed from admin list.' );
		}
		return $this;
	}

	/**
	 * Returns a list of admins if no argument is specified, else it returns if the user is an admin or not.
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
		$this->logFile = (string) $logFile;
		if ( !empty( $this->logFile ) )
		{
			$logFilePath = dirname( $this->logFile );
			if ( !is_dir( $logFilePath ) ) mkdir( $logFilePath, 0600, true );
			
			$this->logFile .= date( 'd-m-Y' ) . '.log';
			$this->logFileHandler = fopen( $this->logFile, 'w+' );
		}
	}
	
	/**
	 * Allows plug-ins to serliase to files, if they don't have a serialise
	 * method then they won't serialise.
	 *
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
	 */
	public function serialise() { $this->serialRemMain( "serialise" ); }
	
	/**
	 * Allows plug-ins to remember data, if they don't have a remember method
	 * then they won't try to load anything.
	 *
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
	 */
	public function remember() { $this->serialRemMain( "remember" ); }
	
	/**
	 * Performs serialisation or remembering across all listeners and commands
	 * if they have implemented this.
	 *
	 * @param string $serialOrRem
	 *			serialise or remember.
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
	 */
	private function serialRemMain( $serialOrRem )
	{
		foreach ( $this->listeners as $listener )
			$this->serialRemLoop( $serialOrRem, self::$listenerString, $listener );
		
		foreach ( $this->commands as $command )
			$this->serialRemLoop( $serialOrRem, self::$commandString, $command );
	}

	/**
	 * Helper function for serialise/remember to actually perform the
	 * serialisation or remembering.
	 * Logs whether the command was successful or not.
	 *
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
	 */
	private function serialRemLoop( $methodName, $beginningString, $object )
	{
		if ( method_exists( $object, $methodName ) )
			if ( $object->{$methodName}() !== FALSE )
				$this->serialRemLog( $beginningString, $object, $methodName, "INFO" );
			else
				$this->serialRemLog( $beginningString, $object, $methodName, "WARNING" );
	}
	
	/**
	 * The log cop
	 *
	 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
	 */
	private function serialRemLog( $beginningString, $object, $methodName, $logType )
	{
		$fail = ( $logType == "WARNING" ) ? " Fail" : "";
		$this->log( $beginningString . " '" . $this->getClassName( $object ) . "' " . self::$serialiseStrings[ucfirst( $methodName ) . " End" . $fail], $logType );
	}
	
	/**
	 * Returns a specific requested command.
	 * Please inspect \Library\IRC\Bot::$commands to find out the name
	 *
	 * @param string $commands			
	 * @return \Library\IRC\Command\Base
	 */
	public function getCommand( $command ) { return $this->commands[$command]; }

	/**
	 * Return a list of all the commands registered
	 * @return array
	 */
	public function getCommands() { return $this->commands; }

	/**
	 * Get the command prefix
	 * @return string
	 */
	public function getCommandPrefix() { return $this->commandPrefix; }
	
	public function setCommandPrefix($prefix)
	{
		$this->commandPrefix = $prefix;
		return $this;
	}

	/**
	 * get the nick of the bot
	 * @return string
	 */
	public function getNick() { return $this->nick; }

	/**
	 * get the connection
	 * @return \Library\IRC\Connection
	 */
	public function getConnection() { return $this->socket; }
	
	/**
	 * Return a list of loaded listeners
	 *
	 * @return array
	 */
	public function getListeners() { return $this->listeners; }

	/**
	 * Returns a specific requested listener.
	 * Please inspect \Library\IRC\Bot::$listeners to find out the name
	 *
	 * @param string $listener			
	 * @return \Library\IRC\Listener\Base
	 */
	public function getListener( $listener ) { return $this->listeners[$listener]; }
}
