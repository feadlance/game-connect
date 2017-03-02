<?php

/**
 * See https://developer.valvesoftware.com/wiki/Source_RCON_Protocol for
 * more information about Source RCON Packets
 *
 * PHP Version 7
 *
 * @copyright 2013 Chris Churchwell
 * @author thedudeguy
 * @link https://github.com/thedudeguy/PHP-Minecraft-Rcon
 */

namespace Weblebby\GameConnect\Minecraft;

use Weblebby\GameConnect\MainConnect;

class Rcon extends MainConnect
{
	public $response;
	public $authorized;

	protected $password;

	const PACKET_COMMAND = 6;
	const PACKET_AUTHORIZE = 5;

	const SERVERDATA_AUTH = 3;
	const SERVERDATA_AUTH_RESPONSE = 2;
	const SERVERDATA_EXECCOMMAND = 2;
	const SERVERDATA_RESPONSE_VALUE = 0;

	/**
	 * Create a new instance of the Rcon class.
	 *
	 * @param string $host
	 * @param integer $port
	 * @param string $password
	 * @param integer $timeout
	 */
	public function __construct($ip, $port, $password, $timeout = 2) 
	{
		parent::__construct($ip, $port, $timeout);

		$this->password = $password;

		$this->connect();
	}

	/**
	 * Connect to a server.
	 *
	 * @return boolean
	 */
	protected function connect() 
	{
		$this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $this->timeout);

		if ( !$this->socket ) {
			$this->response = $errstr;
			return false;
		}

		//set timeout
		@stream_set_timeout($this->socket, 3, 0);

		// check authorization
		if ( $this->authorize() ) {
			return true;
		}

		return false;
	}

	/**
	 * Send a command to the connected server.
	 *
	 * @param string $command
	 *
	 * @return boolean|mixed
	 */
	public function sendCommand($command) 
	{
		if ( $this->authorized !== true ) {
			return false;
		}

		// send command packet
		$this->writePacket(Rcon::PACKET_COMMAND, Rcon::SERVERDATA_EXECCOMMAND, $command);

		// get response
		$response_packet = $this->readPacket();
		if ($response_packet['id'] == Rcon::PACKET_COMMAND) {
			if ($response_packet['type'] == Rcon::SERVERDATA_RESPONSE_VALUE) {
				$this->response = $response_packet['body'];

				return $response_packet['body'];
			}
		}

		return false;
	}

	/**
	 * List online players.
	 *
	 * @return array
	 */
	public function listPlayers()
	{
		$this->sendCommand('list');

		$response = explode(':', $this->response);

		if ( isset($response[1]) === false ) {
			return [];
		}

		$response[1] = preg_replace('/[^a-zA-Z0-9,]/', null, $response[1]);

		return $response[1] ? explode(',', $response[1]) : [];
	}

	/**
	 * Log into the server with the given credentials.
	 *
	 * @return boolean
	 */
	protected function authorize() 
	{
		$this->writePacket(Rcon::PACKET_AUTHORIZE, Rcon::SERVERDATA_AUTH, $this->password);
		$response_packet = $this->readPacket();

		if ($response_packet['type'] == Rcon::SERVERDATA_AUTH_RESPONSE) {
			if ($response_packet['id'] == Rcon::PACKET_AUTHORIZE) {
				$this->authorized = true;
				$this->status = true;

				return true;
			}
		}

		$this->disconnect();
		return false;
	}

	/**
	 * Writes a packet to the socket stream.
	 *
	 * @param $packet_id
	 * @param $packet_type
	 * @param $packet_body
	 *
	 * @return void
	 */
	protected function writePacket($packet_id, $packet_type, $packet_body)
	{
		/*
		Size			32-bit little-endian Signed Integer	 	Varies, see below.
		ID				32-bit little-endian Signed Integer		Varies, see below.
		Type	        32-bit little-endian Signed Integer		Varies, see below.
		Body		    Null-terminated ASCII String			Varies, see below.
		Empty String    Null-terminated ASCII String			0x00
		*/

		//create packet
		$packet = pack("VV", $packet_id, $packet_type);
		$packet = $packet . $packet_body . "\x00";
		$packet = $packet . "\x00";

		// get packet size.
		$packet_size = strlen($packet);

		// attach size to packet.
		$packet = pack("V", $packet_size) . $packet;

		// write packet.
		fwrite($this->socket, $packet, strlen($packet));
	}

	/**
	 * Read a packet from the socket stream.
	 *
	 * @return array
	 */
	protected function readPacket() 
	{
		//get packet size.
		$size_data = fread($this->socket, 4);
		$size_pack = unpack("V1size", $size_data);
		$size = $size_pack['size'];

		// if size is > 4096, the response will be in multiple packets.
		// this needs to be address. get more info about multi-packet responses
		// from the RCON protocol specification at
		// https://developer.valvesoftware.com/wiki/Source_RCON_Protocol
		// currently, this script does not support multi-packet responses.

		$packet_data = fread($this->socket, $size);
		$packet_pack = unpack("V1id/V1type/a*body", $packet_data);

		return $packet_pack;
	}
}