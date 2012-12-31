<?php
/**
 *
 */
abstract class RESTian_Auth_Provider_Base {
  /**
   * @var string RESTian-specific authorization type identifier, like 'basic_http', etc.
   */
  var $auth_type;
  /**
   * @var string To contain something like '1.0a', to be set by subclass if needed.
   */
  var $auth_version;

  /**
   * @return array
   */
  function get_new_credentials() {
    return array();
  }

  /**
   * @return array
   */
  function get_new_grant() {
    return array();
  }

  /**
   * Determine if provided credentials represent a viable set of credentials
   *
   * Default behavior ensures that all credential elements exist, i.e. if username and password are required
   * this code ensures both username and password have a value. Subclasses can add or relax requirements using
   * their own algorithms as required.
   *
   * @param array $credentials
   * @return bool
   */
  function is_credentials( $credentials ) {
    return $this->_has_required( $this->get_new_credentials(), $credentials );
  }

  /**
   * Determine if provided grant represents a viable grant
   *
   * Default behavior ensures that all grant elements exist, i.e. if access_token and refresh_token are required
   * this code ensures both access_token and refresh_token have a value. Subclasses can add or relax requirements
   * using their own algorithms as required.
   *
   * @param array $grant
   * @return bool
   */
  function is_grant( $grant ) {
    return $this->_has_required( $this->get_new_grant(), $grant );
  }

  /**
   * Test to see if the request has prerequisites required to authenticate, i.e. credentials or grant.
   *
   * Defaults to making sure that the request has valid credentials; subclasses can modify as required.
   *
   * @param RESTian_Request $request
   * @return bool
   */
  function has_prerequisites( $request ) {
    return $this->is_credentials( $request->get_credentials() );
  }

  /**
   * Allows an auth provider to decorate a RESTian_Request prior to $request->make_request(), as required.
   *
   * @param RESTian_Request $request
   */
  function prepare_request( $request ) {
  }

  /**
   * Allows an auth provider to handle a response; returns true if handled, false otherwise.
   *
   * The auth provider does not have to handle every response.
   *
   * @param RESTian_Response $response
   * @return bool
   */
  function handle_response( $response ) {
    return false;
  }

  /**
   * Tests a RESTian_Response returning true if the response was authenticated, false otherwise.
   *
   * Default is an HTTP 200 status code; subclasses can modify as required.
   *
   * @param RESTian_Response $response
   * @return bool
   */
  function authenticated( $response ) {
    return 200 == $response->status_code;
  }

  /**
   * Tests an array to ensure it has all required elements given a pattern array.
   *
   * @examples:
   *
   *    $pattern = array( 'a' => '', 'b' => '', 'c' => '' );
   *    $array = array( 'a' => 'foo', 'b' => 'bar', 'd' => 'baz' );
   *    $this->_has_required( $pattern, $array ) // returns false because !isset($array['c'])
   *
   *    $pattern = array( 'a' => '', 'b' => '', 'c' => '' );
   *    $array = array( 'a' => 'foo', 'b' => 'bar', 'c' => 'baz', 'd' => 'bezong' );
   *    $this->_has_required( $pattern, $array ) // returns true because !isset($array['d']) is irrelevant.
   *
   * @param array $pattern_array
   * @param array $array_to_test
   *
   * @return bool
   */
  protected function _has_required( $pattern_array, $array_to_test ) {
    $not_empty = true;
    foreach( array_keys( $pattern_array ) as $key ) {
      if ( empty( $array_to_test[$key] ) ) {
        $not_empty = false;
        break;
      }
    }
    return $not_empty;
  }
}
