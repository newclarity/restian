<?php

abstract class RESTian_Parser_Base {
  var $request;
  var $response;

  /**
   * @param RESTian_Request $request
   * @param RESTian_Response $response
   */
  function __construct( $request, $response ) {
    $this->request = $request;
    $this->response = $response;
  }
  /**
   * Used to throw an exception when not properly subclassed.
   *
   * @param string $body
   * @return array|object
   * @throws Exception
   */
  function parse( $body ) {
    if (1)  // This logic here only to get PhpStorm to stop highlighting the return as an error.
      throw new Exception( 'Class ' . get_class($this) . ' [subclass of ' . __CLASS__ . '] must define a parse() method.' );
    return array();
  }
}
