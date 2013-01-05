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
   * Takes the response and packages the grant in the format $this->is_grant() will validate
   *
   * @param RESTian_Response $response
   * @return array
   */
  function package_grant( $response ) {
    return array( 'authenticated' => $response->authenticated );
  }

  /**
   * @param RESTian_Request $request
   */
  function prepare_request( $request ) {
    $credentials = $request->get_credentials();
    $auth = base64_encode( "{$credentials['username']}:{$credentials['password']}" );
    $request->add_header( 'Authorization', "Basic {$auth}" );
  }

}
