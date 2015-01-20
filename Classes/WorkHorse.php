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
 * Main WorkHorse class.
 * This will invoke everything
 *
 * @package WildBot
 *
 * @author Hoshang Sadiq <superaktieboy@gmail.com>
 */
abstract class WorkHorse extends \StaticBot
{

	/**
	 * This will contain the process workhorse for the bot.
	 *
	 * @var \Library\IRC\ProcessWorkhorse
	 */
	public static $pw = null;

	/**
	 * Initiates the application.
	 * Loads the autoloader and runs the configurations
	 */
	public static function init()
	{
		self::basicConfiguration();
		self::$pw = new Library\IRC\ProcessWorkhorse();
		self::configure(self::$pw);

		self::registerPlugins('Command');
		self::registerPlugins('Listener');
		self::$pw->remember(); // Try to remember everything.
		// Connect to the server.
		self::$pw->connectToServerAndStart();
	}

	/**
	 * Finds all the commands or listeners and registers them
	 */
	public static function registerPlugins($type = NULL)
	{
		if ($type !== 'Command' && $type !== 'Listener')
			return;
		foreach (self::get('config')->{strtolower($type) . 's'} as $class => $args)
		{ // 'Commands' or 'Listeners'
			$pluginPath = $type . '\\' . $class; // Command\<foo> or Listener\<foo>
			echo $pluginPath;
			try
			{
				$plugin = new $pluginPath($args); // Try to instantiate a new
				// plugin
				// with the arguments.
			}
			catch (Exception $e)
			{
				$plugin = new $pluginPath(); // Try to instantiate a new plugin with
				// no arguments if it fails to work
				// before.
				if (!empty($args))
					self::$pw->log('The ' . $type . ' "' . $plugin . '" has arguments in the config but doesn\'t accept any!', 'WARNING');
			}

			self::$pw->{'add' . $type}($plugin); // addCommand or addListener
		}
	}

}
