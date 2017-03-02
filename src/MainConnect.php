<?php

namespace Weblebby\GameConnect;

class MainConnect
{
	/**
	 * IP address on server.
	 *
	 * @var string
	 */
	public $ip;

	/**
	 * Port on server.
	 *
	 * @var integer
	 */
	public $port = 25565;

	/**
	 * Server status. online/offline
	 *
	 * @var boolean
	 */
	public $status = false;

	/**
	 * Connection timeout.
	 *
	 * @var integer
	 */
	public $timeout = 2;
	
	/**
	 * Connection socket.
	 *
	 * @var mixed
	 */
	protected $socket;

	/**
	 * Config.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Load the class.
	 *
	 * @param string $ip IP address on server.
	 * @param integer $port Port on server.
	 * @param integer $timeout Connection timeout.
	 * @param array $config Config values.
	 *
	 * @return void
	 */
	public function __construct($ip, $port, $timeout = 2, array $config = [])
	{
		$this->ip = $ip;
		$this->port = $port;

		$this->config = $config;
		$this->timeout = $timeout;
	}

	/**
	 * Get config value.
	 *
	 * @param string $config Config key.
	 * @return mixed
	 */
	public function config($config)
	{
		return isset($this->config[$config]) ? $this->config[$config] : null;
	}

	/**
	 * Close the connection.
	 *
	 * @return void
	 */
	protected function disconnect()
	{
		if ( $this->socket ) {
			fclose($this->socket);
		}
	}
}