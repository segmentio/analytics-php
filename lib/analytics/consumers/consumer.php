<?php
abstract class Analytics_Consumer {

  /**
   * Tracks a user action
   * @param  [string] $user_id    user id string
   * @param  [string] $event      name of the event
   * @param  [array]  $properties properties associated with the event
   * @param  [string] $timestamp  iso8601 of the timestamp
   * @return [boolean] whether the track call succeeded
   */
  abstract public function track($user_id, $event, $properties, $context,
                                  $timestamp);

  /**
   * Tags traits about the user.
   * @param  [string] $user_id
   * @param  [array]  $traits
   * @param  [string] $timestamp   iso8601 of the timestamp
   * @return [boolean] whether the track call succeeded
   */
  abstract public function identify($user_id, $traits, $context, $timestamp);
}
?>