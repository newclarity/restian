<?php

class RESTian_Basic_Http_Auth_Provider extends RESTian_Auth_Provider_Base {

  /**
   * @return array
   */
  function get_new_credentials() {
    return array(
      'username' => '',
      'password' => '',
    );
  }

  /**
   * @return array
   */
  function get_new_grant() {
    return array(
      'authenticated' => true,
    );
  }

  /**
   * @param array $credentials
   * @return bool
   */
  function is_credentials( $credentials ) {
    return ! empty( $credentials['username'] ) && ( ! empty( $credentials['password'] ) );
  }

  /**
   * @param array $grant
   * @return bool
   */
  function is_grant( $grant ) {
    return ! empty( $grant['authenticated'] );
  }

  /**
   * @param RESTian_Request $request
   */
  function prepare_request( $request ) {
    $credentials = $request->get_credentials();
    $auth = base64_encode( "{$credentials['username']}:{$credentials['password']}" );
    $request->add_header( 'Authorization', "Basic {$auth}" );
  }

  /**
   * Test to see if the result is 204 for authentication. If yes, return true otherwise false.
   *
   * $this->context should contain a RESTian_Request
   *
   * @see: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success
   *
   * @param RESTian_Response $response
   * @return bool
   */
  function authenticated( $response ) {
    return 204 == $response->status_code;
  }
}
