<?php

/**
 * LICENSE: This source file is subject to Creative Commons Attribution
 * 3.0 License that is available through the world-wide-web at the following URI:
 * http://creativecommons.org/licenses/by/3.0/.  Basically you are free to adapt
 * and use this script commercially/non-commercially. My only requirement is that
 * you keep this header as an attribution to my work. Enjoy!
 *
 * @license http://creativecommons.org/licenses/by/3.0/
 *
 * @package WildBot
 * @subpackage Library
 * @author Daniel Siepmann <coding.layne@me.com>
 *
 * @filesource
 */
namespace Library\IRC\Command;

/**
 * An IRC command.
 *
 * @package WildBot
 * @subpackage Library
 * @author Daniel Siepmann <daniel.siepmann@me.com>
 */
abstract class Base extends \Library\IRC\Base {
	/**
	 * The number of arguments the command needs.
	 * By default no arguments are to be given.
	 * You have to define this in the command.
	 *
	 * @var integer
	 */
	protected $argc = 0;
	
	/**
	 * The help string, shown to the user if he calls the command with wrong
	 * parameters.
	 *
	 * You have to define this in the command.
	 *
	 * @var string
	 */
	protected $help = '';

	/**
	 * Require admin, set to true if only admin may execute this.
	 * @var boolean
	 */
	protected $requireAdmin = false;

	/**
	 * Returns whether admin is required for this command or not
	 * @var boolean
	 */
	public function requiresAdmin() { return $this->requireAdmin; }
	
	/**
	 * Executes the command.
	 *
	 * @param array $arguments
	 * @param string $source
	 * @param string $data
	 */
	public function executeCommand()
	{
		// If a number of arguments is incorrect then run the command, if
		// not then show the relevant help text.
		if ( $this->requireAdmin && !$this->getInfo()->is_admin ) return;
		elseif ( $this->argc != -1 && count( $this->arguments ) != $this->argc )
			$this->say( ' Incorrect Arguments. Usage: ' . $this->getHelp() ); // Show help text.
		else
			$this->command(); // Execute the command.
	}
	
	/**
	 * Get the help string
	 *
	 * @return string
	 */
	public function getHelp() { return $this->help; }
	
	/**
	 * Returns requesting user IP
	 *
	 * @return string
	 */
	protected function getUserIp()
	{
		// catches from @ to first space
		if ( preg_match( '/@([a-z0-9.-]*) /i', $this->data, $match ) === 1 )
		{
			$hostname = '1cfksm1tu7fmy.2.ip6.xand.co.uk';
			$hostname = escapeshellarg($match[1]);
			$ip = `host $hostname`;

			$matches = [];
			// did we really get an IP
			if ( preg_match( '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $ip, $matches ) === 1 ) return $matches[1];
		}
		return null;
	}
	
	/**
	 * Overwrite this method for your needs.
	 * This method is called if the command get's executed.
	 */
	abstract public function command();
}
