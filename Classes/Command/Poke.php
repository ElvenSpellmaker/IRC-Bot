<?php
// Namespace
namespace Command;

/**
 * Sends the arguments to the channel, like say from a user.
 *
 * @package WildBot
 * @subpackage Command
 * @author Super3 <admin@wildphp.com>
 */
class Poke extends \Library\IRC\Command\Base {
	/**
	 * The command's help text.
	 * arguments[0] == Channel or User to send Poke to.
	 * arguments[1] == Poke Victim.
	 *
	 * @var string
	 */
	protected $help = '!poke [#channel] [user] OR !poke [user]';
	
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
	 * Sends the arguments to the channel, like say from a user.
	 *
	 * IRC-Syntax: PRIVMSG [#channel]or[user] :0x01Action pokes [User]0x01
	 * 0x01 or the chr(1) represents "Start of Heading" which is a
	 * control charater. This is needed to send the ACTION command, but
	 * it not needed when sending a regular text message.
	 */
	public function command()
	{
		//var_dump($this->arguments);
		
		if( $this->arguments[1] != '' )
			$this->doAction( 'pokes ' . trim( $this->arguments[1] ) . ' (' . $this->queryUser . ')' );
		else if( count($this->arguments) == 1 )
			$this->doAction( 'pokes ' . trim( $this->arguments[0] ) . ' (' . $this->queryUser . ')' );
	}
}
