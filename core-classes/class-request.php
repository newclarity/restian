<?php

/**
 * A RESTian_Request represents a specific HTTP request
 */
class RESTian_Request {
  /**
   * @var object
   */
  protected $_credentials = false;
  /**
   * @var array
   */
  protected $_headers = array();
  /**
   * @var RESTian_Client
   */
  var $client;
  /**
   * @var RESTian_Service
   */
  var $service;
  /**
   * @var RESTian_Service Optional to override the one set in the API, if needed.
   */
  var $auth_service = false;
  /**
   * @var bool Specifies that SSL should not be verified by default.
   * When true it is often too problematic for WordPress plugins.
   */
  var $sslverify = false;
  /**
   * @var string Description of the client used to make the request. Will default unless set.
   */
  var $user_agent;
  /**
   * @var RESTian_Response
   */
  var $response;
  /**
   * @var array
   */
  var $vars;

  var $include_body = true;      // TODO: Change to false after we add content-type processsing of body -> data
  var $include_result = false;

  function __construct( $service, $vars = array(), $args = array() ) {

    if ( is_null( $vars ) )
      $vars = array();

    if ( is_null( $args ) )
      $args = array();
    else if ( is_string( $args ) )
      $args = RESTian::parse_args( $args );

    /**
     * Copy properties in from $args, if they exist.
     */
    foreach( $args as $property => $value )
      if ( property_exists(  $this, $property ) )
        $this->$property = $value;

    /**
     * Do these late $args cannot override them.
     */
    $this->service = $service;
    $this->client = $service->client;
    if ( isset( $args['credentials'] ) )
      $this->set_credentials( $args['credentials'] );

    $this->vars = $vars;

    if ( ! $this->user_agent ) {
      $this->user_agent = str_replace( '_', ' ', get_class($this->client) );
      if ( false === strpos( $this->user_agent, 'Client' ) )
        $this->user_agent .= " Client";
      $this->user_agent .= ' [uses RESTian for PHP]';
      $auth = RESTian::construct_auth_provider( $this->service->auth_type );
      if ( $auth->auth_version )
        $this->user_agent .= " v{$auth->auth_version}";
    }
  }
  function get_auth_service() {
    $this->client->initialize_client();
    if ( ! $this->auth_service ) {
      $this->auth_service = $this->client->get_service( 'authenticate' );
    }
    return $this->auth_service;
  }
  /**
   * Check $this->auth to determine if it contains credentials.
   *
   * Does NOT verify if credentials are valid, only that it has them.
   *
   * This class will be extended when we have a proper use-case for extension.
   *
   * @return bool
   */
  function has_credentials() {
    $auth_provider = RESTian::construct_auth_provider( $this->service->auth_type, array( 'request' => $this ) );
    return $auth_provider->has_credentials( $this->_credentials );
  }
  function get_credentials() {
    return $this->_credentials;
  }
  function set_credentials( $credentials ) {
    $this->_credentials = $credentials;
    $auth_provider = RESTian::construct_auth_provider( $this->service->auth_type, array( 'request' => $this ) );
    return $auth_provider->set_credentials( $credentials );
  }
  function add_header( $name, $value ) {
    $this->_headers[$name] = $value;
  }
  function add_headers( $headers = array() ) {
    $this->_headers = array_merge( $this->_headers, $headers );
  }
  function get_headers() {
    return $this->_headers;
  }
  function clear_headers() {
    $this->_headers = array();
  }
  protected function _get_body() {
    return null;
  }

  /**
   * Returns HTTP headers as expected by CURL.
   *
   * Returns numeric indexed array where value contains header name and header value as "{$name}: {$value}"
   *
   * @return array
   */
  function get_curl_headers() {
    $headers = array();
    foreach( $this->_headers as $name => $value )
      $headers[] = "{$name}: {$value}";
    return $headers;
  }
  function get_wp_args() {
    $wp_args = array(
      'method' => $this->service->http_method,
      'headers' => $this->get_headers(),
      'body' => $this->_get_body(),
      'sslverify' => $this->sslverify,
      'user-agent' => $this->user_agent,
    );
    return $wp_args;
  }
  function get_url() {
    $service_url = $this->client->get_service_url( $this->service );
    if ( count( $this->vars ) ) {
      $query_vars = $this->vars;
      foreach( $query_vars as $name => $value ) {
        if ( ! isset( $this->service->vars[$name] ) ) {
          /**
           * @var array $matches Get all URL path var matches into an array
           */
          preg_match_all( '#([^{]+)\{([^}]+)\}#', $this->service->url_path, $matches );
          $path_vars = array_flip( $matches[2] );
          if ( ! isset( $path_vars[$name] ) ) {
            throw new Exception( "The var \"{$name}\" is not valid for service \"{$this->service->service_name}\"" );
          } else {
            $var = $this->client->get_var( $name );
            $value = $var->apply_transforms( $value );
            $service_url = str_replace( "{{$name}}", $value, $service_url );
            unset( $query_vars[$name] );
          }
        }
      }
      $service_url .= '?' . http_build_query( $query_vars );
    }
    return $service_url;
  }
  /**
   * Returns true if RESTian can safely assume that we have authenticated in past with existing credentials.
   *
   * This does NOT mean we ARE authenticated but that we should ASSUME we are and try doing calls without
   * first authenticating. This functionality is defined because the client (often a WordPress plugin) may
   * have captured auth info from a prior page load where this class did authenticate, but this class is not
   * in control of maintaining that auth info so we can only assume it is correct if the client of this code
   * tells us it is by giving us completed credentials (or maybe some other way we discover as this code evolves
   * base on new use-cases.) Another use-case where our assumption will fail is if the access_key expires or has
   * since been revoked.
   *
   * @return bool
   */
  function assume_authenticated() {
    return $this->has_credentials();
  }
  /**
   * Call the API.
   *
   * On success (HTTP status == 200) $this->error_code and $this->error_msg will be false.
   * On failure (HTTP status != 200) $this->error_code and $this->error_msg will contain error information.
   *
   * On success or failure,  $this->response will contain the response captured by HTTP agent
   * except when username and password are not passed as part of auth.
   *
   * @return object|RESTian_Response
   */
  function make_request() {
    $response = new RESTian_Response( $this );
    if ( $this->service != $this->auth_service &&  ! $this->assume_authenticated() ) {
      $response->set_error( 'NO_AUTH', $this->service );
    } else {
      $response = RESTian::construct_http_agent( $this->client->http_agent )->make_request( $this, $response );
      if ( $response->is_http_error() ) {
        /**
         * See if we can provide more than one error type here.
         */
        $msg = 'There was a problem reaching %s when calling the %s. Please try again later or contact the site\'s administrator.';
        $response->set_error( 'API_FAIL', sprintf( $msg, $this->client->api_name, $this->service->service_name ) );
      } else {
        switch ( $response->status_code ) {
          case '200':
            /**
             * @var RESTian_Parser
             */
            $parser = RESTian::construct_parser( $this->service->content_type, $this, $response );
            $response->data = $parser->parse( $response->body );
            break;
          case '401':
            $response->set_error( 'BAD_AUTH', $this->service );
            break;
          default:
            /**
             * See if we can provide more than one error type here.
             */
            $response->set_error( 'UNKNOWN', 'Unexpected API response code: ' . $response->status_code );
            break;
        }
        if ( ! $this->include_body )
          $response->body = null;
        if ( !$this->include_result )
          $response->result = null;
      }
    }
    return $response;
  }
}

