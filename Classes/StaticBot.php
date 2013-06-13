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
 
/**
 * Super class for both parts of the bot.
 * Common code is here.
 *
 * @package WildBot
 *		 
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
abstract class StaticBot
{
	/**
	 * This contains the registered data
	 * 
	 * @var array
	 */
	private static $_registry = array();

	
	/**
	 * Register a new variable
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	private static function set( $key, $value ) { self::$_registry[$key] = $value; }
	
	/**
	 * Unregister a variable from register by key
	 *
	 * @param string $key
	 */
	private static function unregister( $key )
	{
		if ( isset( self::$_registry[$key] ) )
		{
			if ( is_object( self::$_registry[$key] ) && ( method_exists( self::$_registry[$key], '__destruct' ) ) )
				self::$_registry[$key]->__destruct();
			unset( self::$_registry[$key] );
		}
	}
	
	/**
	 * Retrieve a value from registry by a key
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get( $key )
	{
		if ( isset( self::$_registry[$key] ) ) return self::$_registry[$key];
		return null;
	}
	
	/**
	 * Sets basic config up
	 *
 	 */
	protected static function basicConfiguration()
	{
		require_once ( 'Autoloader.php' );
		spl_autoload_register( 'Autoloader::load' );
		date_default_timezone_set( 'Europe/London' );
		
		if ( function_exists( 'setproctitle' ) )
		{
			$title = basename( __FILE__, '.php' ) . ' - ' . self::get( 'config' )->nick;
			setproctitle( $title );
		}
	}
	
	/**
	 * Loads and saves the configuration to the registry
	 */
	protected static function configure($process)
	{
		if ( file_exists( ROOT_DIR . DS . 'config.local.php' ) )
			$config = include_once ( ROOT_DIR . DS . 'config.local.php' );
		else
			$config = include_once ( ROOT_DIR . DS . 'config.php' );
			
		self::set( 'config', (object) $config );
		$process->configure();
	}
}