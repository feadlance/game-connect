<?php

namespace Weblebby\GameConnect;

class MainConnect
{
	public $ip;
	public $port;
	public $status;
	public $timeout;
	
	protected $socket;
	protected $options;

	public function __construct($ip, $port, $timeout = 2, array $options = [])
	{
		$this->ip = $ip;
		$this->port = $port;

		$this->options = $options;
		$this->timeout = $timeout;
	}

	public function option($option)
	{
		return isset($this->options[$option]) ? $this->options[$option] : null;
	}

	protected function disconnect()
	{
		if ( $this->socket ) {
			fclose($this->socket);
		}
	}
}