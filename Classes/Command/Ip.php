<?php
// Namespace
namespace Command;

/**
 * Sends the user's IP to the channel.
 *
 * @package WildBot
 * @subpackage Command
 * @author Matej Velikonja <matej@velikonja.si>
 */
class Ip extends \Library\IRC\Command\Base {
	/**
	 * The command's help text.
	 *
	 * @var string
	 */
	protected $help = '!ip - Tells you your current IP address.';
	
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
	 * Sends the arguments to the channel.
	 * An IP.
	 *
	 * IRC-Syntax: PRIVMSG [#channel]or[user] : [message]
	 */
	public function command() {
		$ip = $this->getUserIp();
		
		if ( $ip ) {
			$this->say( 'Your IP is: ' . $ip );
		} else {
			$this->say( 'You don\'t have an IP' );
		}
	}
}