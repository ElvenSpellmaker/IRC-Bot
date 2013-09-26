<?php
// Namespace
namespace Command;

/**
 * Leave IRC altogether.
 * This disconnects from the server.
 *
 * @package WildBot
 * @subpackage Command
 * @author Daniel Siepmann <coding.layne@me.com>
 */
class Quit extends \Library\IRC\Command\Base {
	/**
	 * The command's help text.
	 *
	 * @var string
	 */
	protected $help = '!quit';

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
	 * Leave IRC altogether.
	 * This disconnects from the server.
	 */
	public function command() {
		$this->sayRaw('QUIT'); // Need to work out something better than this, the code seems to complain about the exiting.
		exit();
	}
}
