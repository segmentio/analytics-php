<?php

class Analytics_RequestConsumer {

    private $secret;
    private $mh;

    public function __construct($secret) {
        $this->secret = $secret;
        $this->mh = curl_multi_init();
        $this->async_request('track', array('X' => 'x'));
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
    private function async_request($type, $body) {

        $ch = curl_init();

        $host = 'https://segment.io';
        $path = '/v1/' . $type;

        $body['type'] = $type;
        $body['secret'] = $this->secret;
        $content = json_encode($body);
        $length = strlen($content);

        curl_setopt($ch, CURLOPT_URL, $host . $path);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);

        $still_running = null;

        curl_multi_add_handle($this->mh, $ch);
        curl_multi_exec($this->mh, $still_running);

        do {

            $info = curl_getinfo($ch);
            $size = $info['size_upload'];
            $total_time = $info['total_time'];

            foreach($info as $key => $value) {
              echo $key . ": " . $value . "\n";
            }

            echo "Total time: " . $total_time . "\n";
            echo "Running: " . $still_running . "\n";
            echo "Size: " . $size . " Length: " . $length . "\n";
            echo "\n\n\n";

        } while ($size < $length && $total_time < 0.05 && $still_running);



        echo "Request sent!\n";
    }
}
?>

