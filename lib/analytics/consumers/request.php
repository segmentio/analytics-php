<?php

class Analytics_RequestConsumer {

    private $secret;
    private $mh;

    public function __construct($secret) {
        $this->secret = $secret;
        $this->mh = curl_multi_init();
    }

    public function __destruct() {
        curl_multi_close($this->mh);
    }

    public function track ($user_id, $event, $properties, $context, $timestamp) {

        $body = array(
            'secret'     => $this->secret,
            'user_id'    => $user_id,
            'event'      => $event,
            'properties' => $properties,
            'timestamp'  => $timestamp
        );

        $this->request('track', $body);
    }

    public function identify ($user_id, $traits=array()) {

        $body = array(
            'secret'     => $this->secret,
            'user_id'    => $user_id,
            'traits'     => $traits,
            'context'    => $context,
            'timestamp'  => $timestamp
        );

        $this->request('identify', $body);
    }


    /**
     * Make an async request to our API.
     * @param  [string] $type ('track' or 'identify')
     * @param  [array]  $body post body content.
     */
    private function request($type, $body) {

        $ch = curl_init();

        $host = 'http://localhost:7001/v1/track';
        $path = '/v1/' . $type;

        $body['type'] = $type;
        $body['secret'] = $this->secret;

        $content = json_encode($body);

        curl_setopt($ch, CURLOPT_URL, $host . $path);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);

        $still_running = null;

        curl_multi_add_handle($this->mh, $ch);
        curl_multi_exec($this->mh, $still_running);
    }
}
?>

