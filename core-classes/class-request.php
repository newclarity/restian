<?php

/**
 * A RESTian_Request represents a specific HTTP request
 */
class RESTian_Request {
  /**
   * @var bool|array
   */
  protected $_credentials = false;
  /**
   * @var bool|array
   */
  protected $_grant = false;
  /**
   * @var array
   */
  protected $_headers = array();
  /**
   * @var RESTian_Service Optional to override the one set in the API, if needed.
   */
  protected $_auth_service = false;
  /**
   * @var RESTian_Client
   */
  var $client;
  /**
   * @var RESTian_Service
   */
  var $service;
  /**
   * @var string
   */
  var $http_method = 'GET';
  /**
   * @var bool Specifies that SSL should not be verified by default.
   * When true it is often too problematic for WordPress plugins.
   */
  var $sslverify = false;
  /**
   * @var RESTian_Response
   */
  var $response;
  /**
   * @var array
   */
  var $vars;
  /**
   * @var string
   */
  var $body;

  /**
   * @TODO These should be moved to RESPONSE, not be in REQUEST.
   */
  var $include_body = true;      // TODO: Change to false after we add content-type processsing of body -> data
  var $include_result = false;

  /**
   * @param array $vars
   * @param array $args
   */
  function __construct( $vars = array(), $args = array() ) {

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
    if ( isset( $this->service->client ) ) {
      $this->client = $this->service->client;
    }

    if ( isset( $args['credentials'] ) ) {
      $this->set_credentials( $args['credentials'] );
    }

    if ( isset( $args['grant'] ) ) {
      $this->set_grant( $args['grant'] );
    }

    if ( isset( $args['headers'] ) ) {
      $this->add_headers( $args['headers'] );
    }

    if ( is_array( $vars ) ) {
      $this->vars = $vars;
    } else {
      $this->body = $vars;
    }

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
    $auth_provider = $this->client->get_auth_provider();
    return $auth_provider->is_credentials( $this->_credentials );
  }

  /**
   * @return bool|array
   */
  function get_credentials() {
    return $this->_credentials;
  }

  /**
   * @param $credentials
   *
   * @return mixed
   */
  function set_credentials( $credentials ) {
    $this->_credentials = $credentials;
  }

  /**
   * @return bool|array
   */
  function get_grant() {
    return $this->_grant;
  }

  /**
   * @param $grant
   *
   * @return mixed
   */
  function set_grant( $grant ) {
    $this->_grant = $grant;
  }

  /**
   * @return bool|RESTian_Service
   */
  function get_auth_service() {
    $this->client->initialize_client();
    if ( ! $this->_auth_service ) {
      $this->_auth_service = $this->client->get_auth_service();
    }
    return $this->_auth_service;
  }
  /**
   * @param $name
   * @param $value
   */
  function add_header( $name, $value ) {
    $this->_headers[$name] = $value;
  }

  /**
   * @param array $headers
   */
  function add_headers( $headers = array() ) {
    $this->_headers = array_merge( $this->_headers, $headers );
  }

  /**
   * @return array
   */
  function get_headers() {
    return $this->_headers;
  }

  /**
   *
   */
  function clear_headers() {
    $this->_headers = array();
  }

  /**
   * @return bool|string
   */
  function get_body() {
    $body = false;
    if ( 'GET' != $this->http_method )
      $body = $this->body ? $this->body : http_build_query( $this->vars );
    return $body;
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

  /**
   * @return array
   */
  function get_wp_args() {
    $wp_args = array(
      'method' => $this->http_method,
      'headers' => $this->get_headers(),
      'body' => $this->get_body(),
      'sslverify' => $this->sslverify,
      'user-agent' => $this->client->get_user_agent(),
    );
    return $wp_args;
  }

  /**
   * @return bool|string
   * @throws Exception
   */
  function get_url() {
    $service_url = $this->client->get_service_url( $this->service );
    if ( count( $this->vars ) ) {
      $query_vars = $this->vars;
      foreach( $query_vars as $name => $value ) {
        /**
         * @var array $matches Get all URL path var matches into an array
         */
        preg_match_all( '#([^{]+)\{([^}]+)\}#', $this->service->path, $matches );
        $path_vars = array_flip( $matches[2] );
        if ( isset( $path_vars[$name] ) ) {
          $var = $this->client->get_var( $name );
          $value = $var->apply_transforms( $value );
          $service_url = str_replace( "{{$name}}", $value, $service_url );
          unset( $query_vars[$name] );
        }
      }
      $service_url .= '?' . http_build_query( $query_vars );
    }
    return $service_url;
  }

  /**
   * @param $vars
   * @param $template
   *
   * @return bool|string
   */
  private function _to_application_form_url_encoded( $vars, $template = '' ) {
      $query_vars = $this->vars;
      foreach( $query_vars as $name => $value ) {
        /**
         * @var array $matches Get all URL path var matches into an array
         */
        preg_match_all( '#([^{]+)\{([^}]+)\}#', $this->service->path, $matches );
        $path_vars = array_flip( $matches[2] );
        if ( isset( $path_vars[$name] ) ) {
          $var = $this->client->get_var( $name );
          $value = $var->apply_transforms( $value );
          $template = str_replace( "{{$name}}", $value, $template );
          unset( $query_vars[$name] );
        }
      }
    $result = http_build_query( $query_vars );
    return $result;
  }
  /**
   * Returns true if RESTian can safely assume that we have authenticated in past with existing credentials.
   *
   * This does NOT mean we ARE authenticated but that we should ASSUME we are and try doing calls without
   * first authenticating. This functionality is defined because the client (often a WordPress plugin) may
   * have captured grant info from a prior page load where this class did authenticate, but this class is not
   * in control of maintaining that grant info so we can only assume it is correct if the client of this code
   * tells us it is by giving us grant info that the auth provider validates. Another use-case where our assumption
   * will fail is if the access_key expires or has since been revoked.
   *
   * @return bool
   */
  function has_grant() {
    return $this->client->get_auth_provider()->is_grant( $this->_grant );
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
    $response = new RESTian_Response( array( 'request' => $this ) );
    $api = $this->client;
    /**
     * Assign request & response to API so they are accessible inside the auth_provider
     */
    $api->request = $this;
    $api->response = $response;
    $auth_provider = $api->get_auth_provider();
    if ( $this->service != $this->_auth_service && ! $auth_provider->has_prerequisites( $this->get_credentials() ) ) {
      $response->set_error( 'NO_AUTH', $this->service );
    } else {
      $response = RESTian::get_new_http_agent( $this->client->http_agent )->make_request( $this, $response );
      if ( $response->is_http_error() ) {
        /**
         * See if we can provide more than one error type here.
         */
        $msg = 'There was a problem reaching %s when calling the %s. Please try again later or contact the site\'s administrator.';
        $response->set_error( 'API_FAIL', sprintf( $msg, $this->client->api_name, $this->service->service_name ) );
      } else {
        if ( 'authenticate' == $response->request->service->service_name ) {
          $handled = $auth_provider->authenticated( $response );
        } else {
          $handled = $auth_provider->handle_response( $response );
        }
        if ( ! $handled ) {
          // @todo Add more HTTP status code responses as we better understand the use-cases.
          switch ( $response->status_code ) {
            case '200':
              /**
               * @var RESTian_Parser_Base $parser
               */
              $parser = RESTian::get_new_parser( $this->service->content_type, $this, $response );
              if ( $parser instanceof RESTian_Parser_Base )
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

