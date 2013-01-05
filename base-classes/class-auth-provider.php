<?php
/**
 *
 */
abstract class RESTian_Auth_Provider_Base {

  /**
   * @var RESTian_Client
   */
  var $api;

  /**
   * @var string RESTian-specific authorization type identifier, like 'basic_http', etc.
   */
  var $auth_type;

  /**
   * @var string To contain something like '1.0a', to be set by subclass if needed.
   */
  var $auth_version;

  /**
   * @var string Allows auth provider to set a user readable message
   */
  var $message;

  /**
   * @param RESTian_Client $api
   */
  function __construct( $api ) {
    $this->api = $api;
  }

  /**
   * Allow auth provider to process credentials
   *
   * @param array $credentials
   * @return array
   */
  function prepare_credentials( $credentials ) {
    return $credentials;
  }

  /**
   * Allow auth provider to process grant
   *
   * @param array $grant
   * @param array $credentials
   * @return array
   */
  function prepare_grant( $grant, $credentials ) {
    return $grant;
  }

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
    $is_credentials = true;
    if ( ! $this->_has_required( $this->get_new_credentials(), $credentials ) ) {
      $is_credentials = false;
      $this->message = 'The required credentials were not provided.';
    }
    return $is_credentials;
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
   * Extract grant from the passed $auth_settings.
   *
   * @param array $auth_settings
   * @return array
   */
  function extract_grant( $auth_settings ) {
    return array_intersect_key( $auth_settings, $this->get_new_grant() );
  }

  /**
   * Extract credentials from the passed $auth_settings.
   *
   * @param array $auth_settings
   * @return array
   */
  function extract_credentials( $auth_settings ) {
    return array_intersect_key( $auth_settings, $this->get_new_credentials() );
  }


  /**
   * Test to see if the request has prerequisites required to authenticate, i.e. credentials.
   *
   * Defaults to making sure that the request has valid credentials; subclasses can modify as required.
   *
   * @param array $credentials
   * @return bool
   */
  function has_prerequisites( $credentials ) {
    return $this->is_credentials( $credentials );
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
   * Takes the response and capture the grant in the format $this->is_grant() will validate
   *
   * @param RESTian_Response $response
   */
  function capture_grant( $response ) {
    $response->grant = array( 'authenticated' => $response->authenticated );
  }

  /**
   * Tests a RESTian_Response returning true if the response was authenticated, false otherwise.
   *
   * Default is an HTTP 200 or 204 status code; subclasses can modify as required.
   *
   * @see: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success
   *
   * @param RESTian_Response $response
   * @return bool
   */
  function authenticated( $response ) {
    return preg_match( '#^(200|204)$#', $response->status_code );
  }

  /**
   * Return an error message
   *
   * @param string $code
   * @return string
   */
  function get_error_message( $code ) {
    switch ( $code ) {
      case 'NO_AUTH':
        $message = 'Either the username and/or password were not provided. This is likely a programmer error. Please contact the site\'s owner.';
        break;

      case 'BAD_AUTH':
        $message = "Your username and password combination were not recognized by the {$this->api->api_name}.";
        break;

      default:
        $message = false;
        break;
    }
    return $message;
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
    $has_required = true;
    foreach( array_keys( $pattern_array ) as $key ) {
      if ( empty( $array_to_test[$key] ) ) {
        $has_required = false;
        break;
      }
    }
    return $has_required;
  }
}
