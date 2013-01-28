<?php

class Analytics_RequestConsumer {

    private $secret;
    private $options;

    public function __construct($secret, $options = array()) {

        if (!isset($options["timeout"]))
          $options["timeout"] = 0.4;

        $this->secret  = $secret;
        $this->options = $options;
    }

    public function track ($user_id, $event, $properties, $context, $timestamp) {

        $timestamp = date("c", $timestamp);

        $body = array(
            "secret"     => $this->secret,
            "userId"    => $user_id,
            "event"      => $event,
            "properties" => $properties,
            "timestamp"  => $timestamp
        );

        return $this->request("track", $body);
    }

    public function identify ($user_id, $event, $properties, $context, $timestamp) {

        $timestamp = date("c", $timestamp);

        $body = array(
            "secret"     => $this->secret,
            "userId"    => $user_id,
            "traits"     => $traits,
            "context"    => $context,
            "timestamp"  => $timestamp
        );

        return $this->request("identify", $body);
    }


    /**
     * Make an async request to our API. Does this by opening up a socket
     * and then writing to it without waiting for the response.
     * @param  [string]  $type ("track" or "identify")
     * @param  [array]   $body post body content.
     * @return [boolean] whether the request succeeded
     */
    private function request($type, $body) {

        $body["type"] = $type;
        $body["secret"] = $this->secret;

        $content = json_encode($body);

        $protocol = "ssl";
        $host = "api.segment.io";
        $port = 443;
        $path = "/v1/" . $type;

        $timeout = $this->options['timeout'];

        $socket = fsockopen($protocol . "://" . $host, $port, $errno, $errstr,
                            $timeout);

        if ($errno == 0) {
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

            return true;
        } else {

            return false;
        }
    }
}
?>

