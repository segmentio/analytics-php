<?php

class Analytics_RequestConsumer {

    private $secret;

    public function __construct($secret) {
        $this->$secret = $secret;
        $this->async_request('track');
    }

    public function track ($user_id, $event) {
        $this->request('track',
                       array('secret'  => $this->$secret,
                             'user_id' => $user_id,
                             'event'   => $event));
    }

    public function identify ($user_id, $traits=array()) {
        $this->request('identify',
                       array('secret'  => $this->$secret,
                             'user_id' => $user_id,
                             'trait'   => $traits));
    }

    private function request($type, $body) {

    }

    /**
     * Make an async request to our API.
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    private function async_request($type) {

        $ch = curl_init();

        $host = 'https://api.segment.io';
        $path = '/v1/' . $type;

        curl_setopt($ch, CURLOPT_URL, $host . $path);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADERS, array('Content-Type: application/json'));


        $path = '/v1/' . $type;
        $port = 80;//443;
        $timeout = 0.05; // wait 10 ms.

        $socket = fsockopen($host, $port, $errno, $errstr, $timeout);

        $out = 'POST ' . $path . ' HTTP/1.1\r\n';
        $out.= 'STUFF';

        echo "BLARG\n";

    }
}
?>

