<?php
// Namespace
namespace Command;

/**
 * Restarts the bot.
 *
 * @package WildBot
 * @subpackage Command
 * @author Super3 <admin@wildphp.com>
 */
class Restart extends \Library\IRC\Command\Base {
	/**
	 * The command's help text.
	 *
	 * @var string
	 */
	protected $help = '!restart';

	/**
	 * The number of arguments the command needs.
	 *
	 * @var integer
	 */
	protected $argc = 0;
	
	/**
	 * Require admin, set to true if only admin may execute this.
	 * @var boolean
	 */
	protected $requireAdmin = true;
	
	/**
	 * Restarts the bot.
	 */
	public function command() {
		// Exit from Sever
		//$this->connection->sendData( 'QUIT' );
		
		// Reconnect to Server
		//$this->bot->connectToServer();
		
		$this->say( $this->queryUser . ': Attempting to serialise for a restart.~');
		$this->bot->serialise();
		$this->say( $this->queryUser . ': Starting new bot.~');
		exec('runPW.sh');
		//$this->say('nohup php '. $this->bot->getRootFileName() .' >> '. $this->bot->getRootDir() .'/pw.out 2>>&1 &');
		$this->say( $this->queryUser . ': Exiting now.~');
		exit();
	}
}
