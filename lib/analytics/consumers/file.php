<?php

class Analytics_FileConsumer {

    private $file_handle;
    private $secret;
    private $options;

    /**
     * The file consumer writes track and identify calls to a file.
     * @param [type] $secret  [description]
     * @param [type] $options [description]
     */
    public function __construct($secret, $options) {

        if (!isset($options["filename"]))
            $options["filename"] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "analytics.log";

        $this->secret  = $secret;
        $this->options = $options;
        $this->file_handle = fopen($options["filename"], "a");
    }

    public function __destruct() {
        fclose($this->file_handle);
    }

    /**
     * Tracks a user action
     * @param  [string] $user_id    user id string
     * @param  [string] $event      name of the event
     * @param  [array]  $properties properties associated with the event [optional]
     * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
     * @return [boolean] whether the track call succeeded
     */
    public function track($user_id, $event, $properties, $context, $timestamp) {

        $body = array(
            "secret"     => $this->secret,
            "userId"     => $user_id,
            "event"      => $event,
            "properties" => $properties,
            "timestamp"  => $timestamp,
            "action"     => "track"
        );

        return $this->write($body);
    }

    /**
     * Tags traits about the user.
     * @param  [string] $user_id
     * @param  [array]  $traits
     * @param  [number] $timestamp  unix seconds since epoch (time()) [optional]
     * @return [boolean] whether the track call succeeded
     */
    public function identify($user_id, $traits, $context, $timestamp) {

        $body = array(
            "secret"     => $this->secret,
            "userId"     => $user_id,
            "traits"     => $traits,
            "context"    => $context,
            "timestamp"  => $timestamp,
            "action"     => "identify"
        );

        return $this->write($body);
    }

    /**
     * Writes the API call to a file as line-delimeted json
     * @param  [array]   $body post body content.
     * @return [boolean] whether the request succeeded
     */
    private function write($body) {

        $content = json_encode($body);
        $content.= "\n";

        return fwrite($this->file_handle, $content);
    }
}
?>

