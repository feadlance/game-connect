<?php

namespace Weblebby\GameConnect\Minecraft;

use Weblebby\GameConnect\MainConnect;

class Votifier extends MainConnect
{
    protected $key;
    protected $username;
    protected $client_ip;
    protected $vote_time;
    protected $server_list;

    public function __construct($ip, $port, $key, $username, $server_list, $client_ip)
    {
        parent::__construct($ip, $port, 2);

        $this->key = $key;
        $this->key = wordwrap($this->key, 65, "\n", true);
        $this->key = <<<EOF
-----BEGIN PUBLIC KEY-----
$this->key
-----END PUBLIC KEY-----
EOF;
        $this->username = preg_replace('/[^A-Za-z0-9_]+/', '', $username);
        $this->client_ip = $client_ip;
        $this->server_list = $server_list;
    }

    public function send()
    {
        $this->vote_time = time();

        $vote_package = 'VOTE'."\n".$this->server_list."\n".$this->username."\n".$this->client_ip."\n".$this->vote_time."\n";

        $leftover = (256 - strlen($vote_package)) / 2;

        while ($leftover > 0) {
            $vote_package .= "\x0";
            --$leftover;
        }

        $server__socket = @fsockopen($this->ip, $this->port, $errno, $errstr, 3);
        
        if ( !$server__socket ) {
            return false;
        }

        $encrypt = @openssl_public_encrypt($vote_package, $enc_public_key, $this->public_key);

        if ( $encrypt === false ) {
            return 'error_key';
        }

        fwrite($server__socket, $enc_public_key);

        return true;
    }
}