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
 * @license http://creativecommons.org/licenses/by/3.0/
 *
 * @package WildBot
 * @author Hoshang Sadiq <superaktieboy@gmail.com>
 */
 
require('StaticBot.php');

/**
 * Main WildBot class.
 * This will invoke everything
 *
 * @package WildBot
 *		 
 * @author Hoshang Sadiq <superaktieboy@gmail.com>
 */
abstract class WildBot extends \StaticBot
{	
	/**
	 * This will contain the bot class
	 * 
	 * @var \Library\IRC\Bot
	 */
	public static $bot = null;
	
	/**
	 * Initiates the application.
	 * Loads the autoloader and runs the configurations
	 */
	public static function init()
	{	
		self::basicConfiguration();
		self::$bot = new Library\IRC\Bot();
		self::configure(self::$bot);
		
		// Connect to the server.
		self::$bot->connectToServer();
	}
}
