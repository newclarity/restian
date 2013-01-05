<?php

/**
 * A RESTian_Response represents the return values from an HTTP request
 */
class RESTian_Response {

  /**
   * @var int
   */
  var $status_code = 200;

  /**
   * @var bool
   */
  var $message = false;

  /**
   * @var bool|array False if no error, of code (string) and message that external clients can use to branch
   * appropriatly after a call and/or to provide error messages for their users.
   *
   * Provides a dependable format error code for clients to take action on when using RESTian.
   *
   * 'code' => High level error codes for the caller.
   *
   *  'NO_AUTH' - No username and password passed
   *  'BAD_AUTH' - Username and password combination rejected by Revostock
   *  'API_FAIL' - Problem communicating with the API
   *  'NO_BODY' - No response body returned when one was expected.
   *  'BAD_SYNTAX' - The response body contains malformed XML, JSON, etc.
   *  'UNKNOWN' - Unexpected HTTP response code
   *
   * 'message' => Human readable to explain the $error_code.
   */
  private $_error = false;

  /**
   * @var object|array A structured version of the body, is applicable.
   */
  var $data;
  /**
   * @var bool
   */
  var $body = false;
  /**
   * @var object
   */
  /**
   * @var RESTian_Http_Agent_Base Encapsules the specifics for the HTTP agent: PHP's curl ('php_curl') or WordPress' ('wordpress').
   * Contains raw returned data (data) and error results (error_num and error_msg) when an error occurs.
   */
  var $http_error = false;
  /**
   * @var mixed
   */
  var $result;
  /**
   * @var RESTian_Request
   */
  var $request;
  /**
   * @var bool
   */
  var $authenticated = false;
  /**
   * @var array
   */
  var $grant = array();

  /**
   * @param array $args
   */
  function __construct( $args = array() ) {
    /**
     * Copy properties in from $args, if they exist.
     */
    foreach( $args as $property => $value )
      if ( property_exists(  $this, $property ) )
        $this->$property = $value;

  }

  /**
   * @param $number
   * @param $message
   */
  function set_http_error( $number, $message ) {
    $this->http_error = array( $number, $message );
  }

  /**
   * @return bool|RESTian_Http_Agent_Base
   */
  function is_http_error() {
    return $this->http_error;
  }

  /**
   *
   * @return bool|object
   */
  function get_error() {
    return $this->_error;
  }

  /**
   *
   * @return bool|object
   */
  function has_error() {
    return is_object( $this->_error );
  }

  /**
   * @param $code
   * @param bool|string|RESTian_Service $message
   */
  function set_error( $code, $message = false ) {
    if ( false === $message )
      $message = $code;
    $this->_error = (object)array(
      'code' => $code,
      'message' => ! is_object( $message ) ? $message : $message->get_error_message( $code ),
    );
  }

}
