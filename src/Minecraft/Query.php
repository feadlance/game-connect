<?php

namespace Weblebby\GameConnect\Minecraft;

use Weblebby\GameConnect\MainConnect;

class Query extends MainConnect
{
	public $motd;
	public $favicon;
	public $slot;
	public $online;
	public $software;
	public $versions;
	public $versionRaw;

	const CONNECTION_TYPE = "tcp://";

	public function __construct($ip, $port, $timeout = 2)
	{
		parent::__construct($ip, $port, $timeout, [
			'connection_type' => self::CONNECTION_TYPE
		]);

		$this->boot();
	}

	protected function boot()
	{
		$result = $this->register();

		if ( isset($result['description']) ) {
			$result['description'] = isset($result['description']['text']) ? $result['description']['text'] : $result['description'];
		}

		if ( isset($result['version']['name']) ) {
			preg_match_all('/\d+(?:\.\d+)+/', $result['version']['name'], $versions);

			$software = explode(' ', $result['version']['name']);
			$software = preg_replace('/[^A-Za-z]+/', null, $software[0]);

			$versionRaw = $result['version']['name'];
		}

		$this->online = isset($result['players']['online']) ? $result['players']['online'] : null;
		$this->slot = isset($result['players']['max']) ? $result['players']['max'] : null;
		$this->motd = isset($result['description']) ? $result['description'] : null;
		$this->versions = isset($versions[0]) ? $versions[0] : null;
		$this->versionRaw = isset($versionRaw) ? $versionRaw : null;
		$this->software = isset($software) ? $software : null;
		$this->favicon = isset($result['favicon']) ? $result['favicon'] : null;
	}

	protected function register()
	{
		$this->connect();

		if ( $this->socket === false ) {
			return null;
		}

		$data = "\x00";
		$data .= "\x04";
		$data .= pack('c', strlen($this->ip)) . $this->ip;
		$data .= pack('n', $this->port);
		$data .= "\x01";
		$data = pack('c', strlen($data)) . $data;

		fwrite($this->socket, $data);
		fwrite($this->socket, "\x01\x00");

		return $this->receive();
	}

	protected function connect()
	{
		$this->socket = @fsockopen($this->config('connection_type') . $this->ip, $this->port, $errno, $errstr, $this->timeout);

		@stream_set_timeout($this->socket, $this->timeout);

		$this->status = !$this->socket ? false : true;

		return $this->status;
	}

	protected function receive()
	{
		$data = null;
		$length = $this->readVarInt();

		while (strlen($data) < $length) {
			$r = $length - strlen($data);
			$data .= fread($this->socket, $r);
		}

		$data = json_decode(strstr($data, '{'), true);

		return $data;
	}

	protected function readVarInt()
	{
		$i = 0;
		$j = 0;

		while (true) {
			$k = @fgetc($this->socket);

			if ( $k === false ) {
				return 0;
			}

			$k = ord($k);

			$i |= ($k & 0x7F) << $j++ * 7;

			if ( $j > 5 ) {
				return false;
			}

			if ( ($k & 0x80) != 128 ) {
				break;
			}
		}

		return $i;
	}
}