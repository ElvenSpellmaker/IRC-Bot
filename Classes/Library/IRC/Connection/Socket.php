<?php
/**
 * IRC Bot
 *
 * LICENSE: This source file is subject to Creative Commons Attribution
 * 3.0 License that is available through the world-wide-web at the following URI:
 * http://creativecommons.org/licenses/by/3.0/.  Basically you are free to adapt
 * and use this script commercially/non-commercially. My only requirement is that
 * you keep this header as an attribution to my work. Enjoy!
 *
 * @license	http://creativecommons.org/licenses/by/3.0/
 *
 * @package WildBot
 * @subpackage Library
 *
 * @encoding UTF-8
 * @created Jan 11, 2012 11:02:00 PM
 *
 * @author Daniel Siepmann <coding.layne@me.com>
 * @author Jack Blower <Jack@elvenspellmaker.co.uk> 
 */

namespace Library\IRC\Connection;

/**
 * Delivers a connection via stream to the IRC server.
 *
 * @package WildBot
 * @subpackage Library
 */
class Socket implements \Library\IRC\Connection
{

	/**
	 * The server you want to connect to.
	 * @var string
	 */
	private $server = '';

	/**
	 * The port of the server you want to connect to.
	 * @var integer
	 */
	private $port = 0;
	
	/**
	 * The TCP/IP socket.
	 * @var PHP Socket
	 */	 
	private $socket;

	/**
	 * Close the connection.
	 */
	public function __destruct() { $this->disconnect(); }

	/**
	 * Establish the connection to the server.
	 */
	public function connect()
	{
		$this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		if( ! socket_connect( $this->socket, $this->server, $this->port ) )
			throw new \Exception( 'Unable to connect to server via socket_connect with server: "'. $this->server .'" and port: "'. $this->port .'.' );
	}

	/**
	 * Disconnects from the server.
	 *
	 * @return boolean True if the connection was closed. False otherwise.
	 */
	public function disconnect()
	{
		if( $this->isConnected() )
		{
			socket_shutdown( $this->socket, 2 );
			socket_close( $this->socket );
		}
	}

	/**
	 * Interaction with the server.	
	 * For example, send commands or some other data to the server.
	 *
	 * @return int|boolean the number of bytes written, or FALSE on error.
	 */
	public function sendData( $data ) { return socket_write( $this->socket, $data . "\r\n" ); }

	/**
	 * Returns data from the server.
	 *
	 * @return string|boolean The data as string, or false if no data is available or an error occurred.
	 */
	public function getData() { return socket_read( $this->socket, 512 ); }
	
	/**
	 * Returns all the available data from the socket. This will only work with non-blocking connections.
	 * 
	 * @return string|boolean The data as a string, FALSE if there is no data available for any reason and empty string '' if the socket has disconnected.
	 */
	public function getAllData()
	{ 
		$data = '';
		
		while ( ($rawData = $this->getData()) !== false )
		{
			if( $rawData === '' ) return ''; // The connection has shut down.
			$data .= $rawData;
		}
		
		if( $data === '' ) return FALSE; // If the message has no data then nothing could be read from the server.
	
		$data = explode( "\r\n", $data ); // Explode by new lines which indicate separate messages.
		foreach( $data as &$line ) $line .= "\r\n"; // Add the new lines back onto the messages.
	
		return array_slice( $data, 0, -1 ); // The last message is always an empty string, so don't return it.
	}

	/**
	 * Check whether the connection exists.
	 *
	 * @return boolean True if the connection exists. False otherwise.
	 */
	public function isConnected() { return is_resource( $this->socket ); }

	/**
	 * Sets the server.
	 * E.g. irc.quakenet.org or irc.freenode.org
	 * @param string $server The server to set.
	 */
	public function setServer( $server ) { $this->server = gethostbyname( $server ); }

	/**
	 * Sets the port.
	 * E.g. 6667
	 * @param integer $port The port to set.
	 */
	public function setPort( $port ) { $this->port = (int) $port; }
	
	/**
	 * Sets the connection to non-blocking mode.
	 * 
	 * @return TRUE on success, FALSE on failure.
	 */
	public function setNonBlocking() { return socket_set_nonblock( $this->socket ); }
	
	/**
	 * Sets the socket for this connection Object.
	 *
	 * @param resource The PHP socket object to store.
	 */
	public function setSocket( $socket ) { $this->socket = $socket; }
	
	/**
	 * Returns the raw socket. 
	 *
	 * @return The raw socket.
	 */ 
	public function getSocket() { return $this->socket; }
}
