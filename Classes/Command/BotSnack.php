<?php
// Namespace
namespace Command;

/**
 * Joins the specified channel.
 * arguments[0] == Channel to join.
 *
 * @package IRCBot
 * @subpackage Command
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
class BotSnack extends \Library\IRC\Command\Base
{
	/**
	* The command's help text.
	*/
	protected $help = '!botsnack - Responds with YUM!';


	/**
	 * The number of arguments the command needs.
	 * You have to define this in the command.
	 */
	protected $argc = 0;
	
	/**
	 * Require admin, set to true if only admin may execute this.
	 */
	protected $requireAdmin = false;

	/**
	 * Joins the specified channel.
	 * IRC-Syntax: JOIN [#channel]
	 */
	public function command()
	{
		
		preg_match("/(.+)!/", $this->privSource, $queryUser);
		$queryUser = $queryUser[1];
		$this->say($queryUser .": YUM!");
		return;
	}
}
?>