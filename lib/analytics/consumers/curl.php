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
            'userId'    => $user_id,
            'event'      => $event,
            'properties' => $properties,
            'timestamp'  => $timestamp
        );

        echo(var_dump($body));

        $this->request('track', $body);
    }

    public function identify ($user_id, $event, $properties, $context, $timestamp) {

        $body = array(
            'secret'     => $this->secret,
            'userId'    => $user_id,
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

        $host = 'https://api.segment.io';
        $path = '/v1/' . $type;

        $body['type'] = $type;
        $body['secret'] = $this->secret;

        $content = json_encode($body);

        curl_setopt($ch, CURLOPT_URL, $host . $path);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
                                                   'Accept: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1500);

        $still_running = null;

        echo $content . "\n";

        curl_multi_add_handle($this->mh, $ch);
        curl_multi_exec($this->mh, $still_running);


        do {
            $mrc = curl_multi_exec($this->mh, $still_running);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        /*while ($still_running && $mrc == CURLM_OK) {
            if (curl_multi_select($this->mh) != -1) {
                do {
                    $mrc = curl_multi_exec($this->mh, $still_running);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }*/

        #
        #sleep(1);
        $content =  curl_multi_getcontent($ch);
        echo $content;
        $info = curl_getinfo($ch);
        echo var_dump($info);
    }
}
?>

