<?php

/**
 * Base class for a simplied Web API access for PHP with emphasis on WordPress.
 *
 * RESTian can be used without WordPress, but its design is WordPress influenced.
 *
 * Uses Exceptions ONLY to designate obvious programming errors. IOW, callers of
 * this client do NOT need to use try {} catch {} because RESTian_Client should
 * only throw exceptions when there code has well understood bugs.
 *
 * @priorart: http://guzzlephp.org/guide/service/service_descriptions.html
 *
 */
abstract class RESTian_Client {

  /**
   * @var object Enables the caller to attach itself so subclasses can access the caller. Our use case was for the WordPress plugin class.
   */
  var $caller;

  /**
   * @var array Of RESTian_Services; provides API service specific functionality
   */
  protected $_services = array();

  /**
   * @var array Of name value pairs that can be used to query an API
   */
  protected $_vars = array();

  /**
   * @var array Of named var sets
   */
  protected $_var_sets = array();

  /**
   * @var array -
   */
  protected $_defaults = array(
    'var'       => array(),
    'services'   => array(),
  );
  /**
   * @var bool Set to true once API is initialized.
   */
  protected $_intialized = false;
  /**
   * @var bool|array Properties needed for credentials
   */
  protected $_credentials = false;
  /**
   * @var bool|array Properties needed for grant
   */
  protected $_grant = false;
  /**
   * @var bool|RESTian_Service
   */
  protected $_auth_service = false;
  /**
   * @var string Description of the client used to make the request. Will default unless set.
   */
  protected $_user_agent;
  /**
   * @var string - The type of authorization, as defined by RESTian.
   *
   * In future might be RESTian-specific 'oauth_2_auth_code', 'oauth_2_password', 'oauth_1a', etc.
   *
   */
  var $auth_type = 'basic_http';
  /**
   * @var RESTian_Service for authenticating  - to be set by subclass.
   */
//  var $auth_service;
  /**
   * @var string Human readable name of the API  - to be set by subclass.
   */
  var $api_name;
  /**
   * @var string The API's base URL - to be set by the subclass calling this classes constructor.
   */
  var $base_url;
  /**
   * @var string The version of the API used - to be set by the subclass calling this classes constructor.
   */
  var $api_version;
  /**
   * @var string
   */
  var $http_agent;
  /**
   * @var bool|callable
   */
  var $cache_callback = false;
  /**
   * @var bool
   */
  var $use_cache = false;
  /**
   * @var RESTian_Request
   */
  var $request;
  /**
   * @var RESTian_Response
   */
  var $response;

  /**
   * @param object $caller
   */
  function __construct( $caller ) {
    $this->caller = $caller;
    /**
     * Set the API Version to be changed every second for development.  If not in development set in subclass.
     */
    $this->api_version = date( DATE_ISO8601, time() );
    $this->http_agent = defined( 'WP_CONTENT_DIR') && function_exists( 'wp_remote_get' ) ? 'wordpress' : 'php_curl';
  }

  /**
   * @return string
   */
  function get_user_agent() {
    if ( ! $this->_user_agent ) {
      $this->_user_agent = str_replace( '_', ' ', get_class( $this ) );
      if ( false === strpos( $this->_user_agent, 'Client' ) )
        $this->_user_agent .= " Client";
      $this->_user_agent .= ' [uses RESTian for PHP]';

      /**
       * @var RESTian_Auth_Provider_Base $auth
       */
      $auth_provider = $this->get_auth_provider();
      if ( $auth_provider->auth_version )
        $this->_user_agent .= " v{$auth_provider->auth_version}";
    }
    return $this->_user_agent;
  }
  /**
   * @return string
   */
  function get_cachable() {
    return serialize( array(
      'vars' => $this->_vars,
      'services' => $this->_services,
    ));
  }

  /**
   * @param $cached
   */
  function initialize_from( $cached ) {
    $values = unserialize( $cached );
    $this->_vars = $values['vars'];
    $this->_services = $values['services'];
  }

  /**
   * Set the grants value.
   *
   * Note: Throws away keys that are not needed.
   *
   * @param $credentials
   */
  function set_credentials( $credentials ) {
    /**
     * Extract just the array elements that are specific to the credentials.
     * @see http://stackoverflow.com/a/4240153/102699
     */
    $this->_credentials = array_intersect_key( $credentials, $this->get_auth_provider()->get_new_credentials() );
  }

  /**
   * @return array|bool
   */
  function get_credentials() {
    return $this->_credentials;
  }

  /**
   * Set the grant value.
   *
   * Note: Throws away keys that are not needed.
   *
   * @param $grant
   */
  function set_grant( $grant ) {
    /**
     * Extract just the array elements that are specific to the grant.
     * @see http://stackoverflow.com/a/4240153/102699
     */
    $this->_grant = array_intersect_key( $grant, $this->get_auth_provider()->get_new_grant() );
  }

  /**
   * @return array|bool
   */
  function get_grant() {
    return $this->_grant;
  }
  /**
   * Returns true if the grant passed is validated by the auth provider.
   *
   * This does NOT mean we ARE authenticated but that we should ASSUME we are and try doing calls without
   * first authenticating. This functionality is defined because the client (often a WordPress plugin) may
   * have captured auth info from a prior page load where this class did authenticate, but this class is not
   * in control of maintaining that auth info so we can only assume it is correct if the client of this code
   * tells us it is by giving us completed credentials (or maybe some other way we discover as this code evolves
   * base on new use-cases.) Another use-case where our assumption will fail is if the access_key expires or has
   * since been revoked.
   *
   * @param array|bool $grant
   * @return array|bool
   */
  function is_grant( $grant = array() ) {
    return $this->get_auth_provider()->is_grant( $grant );
  }
  /**
   * Returns true if the contained grant is validated by the auth provider.
   *
   * @return array|bool
   */
  function has_grant() {
    return $this->is_grant( $this->_grant );
  }
  /**
   * @return bool|RESTian_Service
   */
  function get_auth_service() {
    $this->initialize_client();
    if ( ! $this->_auth_service ) {
      $this->_auth_service = $this->get_service( 'authenticate' );
    }
    return $this->_auth_service;
  }

  /**
   * @return RESTian_Auth_Provider_Base
   */
  function get_auth_provider() {
    return RESTian::get_new_auth_provider( $this->auth_type, $this );
  }

  /**
   * Evaluate passed credentials to see if the auth provider considers the credentials to be valid.
   *
   * This does not authenticate, it just makes sure we have credentials that might work. IOW, if username or
   * password are empty for basic auth then clearly we don't have credentials.
   *
   * @param array|bool $credentials
   * @return array|bool
   */
  function is_credentials( $credentials ) {
    /**
     * Request will delegate to auth provider to see if it has credentials.
     */
    return $this->get_auth_provider()->is_credentials( $credentials );
  }

  /**
   * Retrieves the last error message set by the auth provider
   *
   * @return string
   */
  function get_message() {
    /**
     * Request will delegate to auth provider to return an error message.
     */
    return $this->get_auth_provider()->message;
  }

  /**
   * Evaluate if the the contained credentials are considered to be valid.
   *
   * This does not authenticate, it just makes sure we have credentials that might work. IOW, if username or
   * password are empty for basic auth then clearly we don't have credentials.
   *
   * @return array|bool
   */
  function has_credentials() {
    /**
     * Request will delegate to auth provider to see if it has credentials.
     */
    return $this->is_credentials( $this->_credentials );
  }

  /**
   * @param $defaults
   *
   * @return array
   */
  function register_service_defaults( $defaults ) {
    return $this->_register_defaults( 'services', $defaults );
  }

  /**
   * @return mixed
   */
  function get_service_defaults() {
    return $this->_get_defaults( 'services' );
  }

  /**
   * @param $defaults
   *
   * @return array
   */
  function register_var_defaults( $defaults ) {
    return $this->_register_defaults( 'vars', $defaults );
  }

  /**
   * @return mixed
   */
  function get_var_defaults() {
    return $this->_get_defaults( 'vars' );
  }

  /**
   * @param $type
   * @param $defaults
   *
   * @return array
   */
  function _register_defaults( $type, $defaults ) {
    return $this->_defaults[$type] = RESTian::parse_args( $defaults );
  }

  /**
   * @param $type
   *
   * @return mixed
   */
  function _get_defaults( $type ) {
    return $this->_defaults[$type];
  }

  /**
   * Allow a subclass to register an API service.
   *
   * @param string $service_name
   * @param array|RESTian_Service $args
   * @return RESTian_Service
   */
  function register_service( $service_name, $args ) {

    if ( is_a( $args, 'RESTian_Service' ) ) {
      $args->service_name = $service_name;
      $service = $args;
    } else {
      $service = new RESTian_Service( $service_name, $this, RESTian::parse_args( $args ) );
    }
    $this->_services[$service_name] = $service;
    return $service;
  }
  /**
   * Allow a subclass to register an API action.
   *
   * @param string $resource_name
   * @param array|RESTian_Service $args
   * @return RESTian_Service
   */
  function register_action( $resource_name, $args ) {
    $args = RESTian::parse_args( $args );
    $args['service_type'] = 'action';
    return $this->register_service( $resource_name, $args );
  }
  /**
   * Allow a subclass to register an API resource.
   *
   * @param string $resource_name
   * @param array|RESTian_Service $args
   * @return RESTian_Service
   */
  function register_resource( $resource_name, $args = array() ) {
    $args = RESTian::parse_args( $args );
    $args['service_type'] = 'resource';
    return $this->register_service( $resource_name, $args );
  }
  /**
   * @param string $var_name
   * @param array $args
   * @throws Exception
   */
  function register_var( $var_name, $args = array() ) {
    $this->_vars[$var_name] = new RESTian_Var( $var_name, RESTian::parse_args( $args ) );
  }
  /**
   * @param string $var_name
   * @return RESTian_Var
   */
  function get_var( $var_name ) {
    return $this->_vars[$var_name];
  }
  /**
   * @param string $var_set_name
   * @param array $vars
   */
  function register_var_set( $var_set_name, $vars = array() ) {
    $this->_var_sets[$var_set_name] = RESTian::parse_string( $vars );
  }
  /**
   * @param string $var_set_name
   */
  function get_var_set( $var_set_name ) {
    return $this->_var_sets[$var_set_name];
  }
  /**
   * Returns empty object of created credentials information.
   *
   * Can be overridden by subclass is credentials requirements are custom.
   *
   * @return array
   */
  function get_new_credentials() {
    $credentials = $this->get_auth_provider()->get_new_credentials();
    if ( ! $credentials )
      $credentials = array();
    return $credentials;
  }

  /**
   * Authenticate against the API.
   *
   * @param bool|array $credentials
   * @return RESTian_Response
   */
  function authenticate( $credentials = false ) {
    if ( ! $credentials )
      $credentials = $this->_credentials;

    if ( ! $this->_credentials )
      $this->_credentials = $credentials;

    $this->_auth_service = $this->get_auth_service();

    /**
     * @var RESTian_Auth_Provider_Base
     */
    $auth_provider = $this->get_auth_provider();

    $this->request = new RESTian_Request( null, array(
      'credentials' => $credentials,
      'service' => $this->_auth_service,
    ));

    if ( ! $this->is_credentials( $credentials ) ) {
      $response = new RESTian_Response( array(
        'request' => $this->request,
      ));
      $response->set_error( 'NO_AUTH', 'Credentials not provided. Please enter your credentials.' );
    } else {
      /**
       * @var RESTian_Response $response
       */
      $response = $this->make_request( $this->request );
      $response->authenticated = $auth_provider->authenticated( $response );
      if( ! $response->authenticated ) {
        $response->set_error( 'BAD_AUTH', $auth_provider->message );
      } else {
        $auth_provider->capture_grant( $response );
      }
    }
    return $response;
  }
  /**
   * @param string|RESTian_Service $resource_name
   * @param array $vars
   * @param array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  function get_resource( $resource_name, $vars = null, $args = null ) {
    $service = $this->get_service( $resource_name );
    if ( 'resource' != $service->service_type ) {
      throw new Exception( 'Service type must be "resource" to use get_resource(). Consider using call_service() or invoke_action() instead.' );
    }
    return $this->call_service( $service, 'GET', $vars, $args );
  }
  /**
   * @param string|RESTian_Service $resource_name
   * @param array $body
   * @param array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  function post_resource( $resource_name, $body = null, $args = null ) {
    $service = $this->get_service( $resource_name );
    if ( 'resource' != $service->service_type ) {
      throw new Exception( 'Service type must be "resource" to use post_resource(). Consider using call_service() or invoke_action() instead.' );
    }
    return $this->call_service( $service, 'POST', $body, $args );
  }
  /**
   * @param string|RESTian_Service $action_name
   * @param array $vars
   * @param array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  function invoke_action( $action_name, $vars = null, $args = null ) {
    $service = $this->get_service( $action_name );
    if ( 'action' != $service->service_type ) {
      throw new Exception( 'Service type must be "action" to use invoke_action(). Consider using call_service() or get_resource() instead.' );
    }
    return $this->call_service( $service, 'DO', $vars, $args );
  }
  /**
   * @param string|RESTian_Service $service
   * @param string $method HTTP methods 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD' and RESTian method 'DO'
   * @param null|array $vars
   * @param null|array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  function call_service( $service, $method = 'GET', $vars = null, $args = null ) {
    $this->initialize_client();
    $args['service'] = is_object( $service ) ? $service : $this->get_service( $service );
    $request = new RESTian_Request( $vars, $args );
    /**
     * @todo This will need to be updated when we have a use-case where actions require 'POST'
     * @todo ...or maybe we'll evolve RESTian to deprecate actions?
     */
    $request->http_method = 'DO' == $method ? 'GET' : $method;

    if ( isset( $args['credentials'] ) ) {
      $this->_credentials = $args['credentials'];
    }
    $request->set_credentials( $this->_credentials );

    if ( isset( $args['grant'] ) ) {
      $this->_grant = $args['grant'];
    }
    $request->set_grant( $this->_grant );

    return $this->process_response( $this->make_request( $request ), $vars, $args );
  }

  /**
   * @param RESTian_Request $request
   * @return RESTian_Response $response
   */
  function make_request( $request ) {
    $auth_provider = $this->get_auth_provider();
    $auth_provider->prepare_request( $request );
    if ( method_exists( $request->client, 'prepare_request' ) )
      $request->client->prepare_request( $this->request );
    if ( method_exists( $auth_provider, 'make_request' ) ) {
      $response = $auth_provider->make_request( $request );
    } else {
      $response = $request->make_request();
    }
    return $response;
  }
  /**
   * Stub that allows subclass to process request, if needed.
   *
   * @param RESTian_Response $response
   * @param null|array $vars
   * @param null|array $args
   * @return RESTian_Response $response
   */
  function process_response( $response, $vars = array(), $args = array() ) {
    return $response;
  }
  /**
   * Subclass if needed.
   *
   * @param RESTian_Request $request
   */
  function prepare_request( $request ){
  }
  /**
   * Call subclass to register all the services and params.
   *
   * Must subclass.
   */
  function initialize(){
    $this->_subclass_exception( 'must define initialize().' );
  }
  /**
   * Subclass if needed.
   *
   */
  function initialize_client() {
    if ( $this->_intialized )
      return;
    $this->_intialized = true;

    $cached = $this->cache_callback ? call_user_func( $this->cache_callback, $this ) : false;
    if ( $cached ) {
      $this->initialize_from( $cached );
    } else {
      /**
       * Call initialize() in the subclass.
       */
      $this->initialize();
      /**
       * Initialize the auth_service property.
       */
      $auth_service = $this->get_service( 'authenticate' );
      if ( ! $auth_service ) {
        /**
         * So the subclass did not create an authenticate service, use default.
         */
        $auth_service = new RESTian_Service( 'authenticate', $this, array(
          'path' => '/authenticate',
        ));
        $this->register_service( 'authenticate', $auth_service );
      }
      if ( $this->cache_callback )
        call_user_func( $this->cache_callback, $this, $this->get_cachable() );
    }
  }
  /**
   * Override in subclass in the case caching of client serialization is wanted
   */
  function get_cached() {
    return false;
  }

  /**
   * Override in subclass in the case caching of client serialization is wanted
   */
  function cache( $cachable ) {
  }
  /**
   * Get the service object based on its name
   *
   * @param string|RESTian_Service $service
   * @return bool|RESTian_Service
   * @throws Exception
   */
  function get_service( $service ) {
    if ( ! is_a( $service, 'RESTian_Service' ) ) {
      $this->initialize_client();
      if ( ! isset( $this->_services[$service] ) ) {
        $service = false;
      } else {
        $service = $this->_services[$service];
        $service->context = $this;
      }
    }
    return $service;
  }
  /**
   * Subclass if needed.
   *
   * @param $error_code
   * @return string
   */
  function get_error_message( $error_code ) {
    return false;
  }
  /**
   * Returns the URL for a given service.
   *
   * Subclass if needed.
   *
   * @param RESTian_Service $service
   * @return bool|string
   */
  function get_service_url( $service ) {
    if ( is_string( $service ) )
      $service = $this->get_service( $service );
    $service_url = rtrim( $this->base_url, '/' ) . '/' . ltrim( $service->path, '/' );
    return $service_url;
  }

  /**
   * Used to throw an exception when not properly subclassed.
   *
   * @param $message
   * @throws Exception
   */
  protected function _subclass_exception( $message ) {
    throw new Exception( 'Class ' . get_class($this) . ' [subclass of ' . __CLASS__ . '] ' . $message );
  }
}
