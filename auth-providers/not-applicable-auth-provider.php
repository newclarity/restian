<?php

/**
 *
 */
class RESTian_Not_Applicable_Provider extends RESTian_Auth_Provider_Base {
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
    return true;
  }

  /**
   * @param array $credentials
   * @return object
   */
  function set_credentials( $credentials ) {
    return $credentials;
  }

  /**
   *
   * $this->context should contain a RESTian_Request
   *
   * @see: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success
   *
   * @param array $credentials
   * @return RESTian_Response
   */
  function authenticate( $credentials ) {
    return new RESTian_Response();
  }
}
