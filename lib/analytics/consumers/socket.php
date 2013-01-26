<?php

class Analytics_RequestConsumer {

    private $secret;

    public function __construct($secret) {
        $this->secret = $secret;
    }

    public function __destruct() {
    }

    public function track ($user_id, $event, $properties, $context, $timestamp) {

        $body = array(
            "secret"     => $this->secret,
            "userId"    => $user_id,
            "event"      => $event,
            "properties" => $properties,
            "timestamp"  => $timestamp
        );

        $this->request("track", $body);
    }

    public function identify ($user_id, $event, $properties, $context, $timestamp) {

        $body = array(
            "secret"     => $this->secret,
            "userId"    => $user_id,
            "traits"     => $traits,
            "context"    => $context,
            "timestamp"  => $timestamp
        );

        $this->request("identify", $body);
    }


    /**
     * Make an async request to our API.
     * @param  [string] $type ("track" or "identify")
     * @param  [array]  $body post body content.
     */
    private function request($type, $body) {

        $body["type"] = $type;
        $body["secret"] = $this->secret;

        $content = json_encode($body);

        $protocol = "ssl";
        $host = "api.segment.io";
        $port = 443;
        $path = "/v1/" . $type;

        $socket = fsockopen($protocol . "://" . $host, $port, $errno, $errstr, 0.3);

        echo $errno . ' ' . $errstr;

        if ($errno != 0) {
          $req = "";
          $req.= "POST " . $path . " HTTP/1.1\r\n";
          $req.= "Host: " . $host . "\r\n";
          $req.= "Content-Type: application/json\r\n";
          $req.= "Accept: application/json\r\n";
          $req.= "Content-length: " . strlen($content) . "\r\n";
          $req.= "\r\n";
          $req.= $content;

          fwrite($socket, $req);
          fclose($socket);
        }
    }
}
?>

