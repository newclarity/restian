<?php

class RESTian_Auth_Provider {
  /**
   * @var string RESTian-specific authorization type identifier, like 'basic_http', etc.
   */
  var $auth_type;
  /**
   * @var string To contain something like '1.0a', to be set by subclass if needed.
   */
  var $auth_version;
  /**
   * @var RESTian_Request
   */
  var $request;
  /**
   * @var RESTian_Service
   */
  var $service;
  function __construct( $args = array() ) {
    if ( isset( $args['request'] ) )
      $this->request = $args['request'];
  }
  /**
   * @return array
   */
  function get_new_credentials() {
    return array();
  }

  /**
   * @param array $credentials
   * @return bool
   */
  function has_credentials( $credentials ) {
    return false;
  }

  /**
   * @param array $credentials
   * @return array
   */
  function set_credentials( $credentials ) {
    return $credentials;
  }

  /**
   * @return RESTian_Response
   */
  function authenticate() {
  }
}
