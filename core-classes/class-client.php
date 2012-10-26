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
   * @var array Properties needed for credentials
   */
  protected $_credentials = false;
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
   */
  function __construct() {
    /**
     * Set the API Version to be changed every second for development.  If not in development set in subclass.
     */
    $this->api_version = date( DATE_ISO8601, time() );
    $this->http_agent = defined( 'WP_CONTENT_DIR') && function_exists( 'wp_remote_get' ) ? 'wordpress' : 'php_curl';
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
   * @param $credentials
   */
  function set_credentials( $credentials ) {
    $this->_credentials = $credentials;
  }

  /**
   * @return array|bool
   */
  function get_credentials() {
    return $this->_credentials;
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
  function register_resource( $resource_name, $args ) {
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
    $credentials = $this->get_service( 'authenticate' )->get_auth_provider( $this )->get_new_credentials();
    if ( ! $credentials )
      $credentials = array();
    return $credentials;
  }

  /**
   * Authenticate against the API.
   *
   * @param bool|array $credentials
   * @return bool
   */
  function authenticate( $credentials = false ) {
    $authenticated = false;
    $auth_service = $this->get_service( 'authenticate' );
    if ( ! $credentials )
      $credentials = $this->get_credentials();
    $request = new RESTian_Request( $auth_service, null, array( 'credentials' => $credentials ) );
    $response = new RESTian_Response( $request );
    if ( ! $request->has_credentials() ) {
      $response->set_error( 'NO_AUTH' );
    } else {
      $request = $this->prepare_request( $request );
      $request->response = $response;
      $auth_provider = $auth_service->get_auth_provider();
      $auth_provider->request = $request;
      $auth_provider->service = $auth_service;
      $response = $auth_provider->authenticate( $credentials );
    }
    return $response->authenticated;
  }
  /**
   * @param string|RESTian_Service $resource_name
   * @param array $vars
   * @param array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  function get_resource( $resource_name, $vars = null, $args = null) {
    $service = $this->get_service( $resource_name );
    if ( 'resource' != $service->service_type ) {
      throw new Exception( 'Service type must be "resource" to use get_resource(). Consider using call_service() or invoke_action() instead.' );
    }
    return $this->call_service( $service, $vars, $args );
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
    return $this->call_service( $service, $vars, $args );
  }
  /**
   * @param string|RESTian_Service $service
   * @param null|array $vars
   * @param null|array|object $args
   * @return object|RESTian_Response
   * @throws Exception
   */
  /**
   */
  function call_service( $service, $vars = null, $args = null ) {
    $this->initialize_client();
    $service = is_a( $service, 'RESTian_Service' ) ? $service : $this->get_service( $service );
    $request = new RESTian_Request( $service, $vars, $args );
    if ( ! $request->get_credentials() ) {
      $request->set_credentials( $this->_credentials );
    }
    return $this->process_response( $request->make_request(), $vars, $args );
  }
//  function subclass_declares_method( $method_name ) {
//    $reflection = new ReflectionMethod( $this, $method_name );
//    $declaring_classes = (array)$reflection->getDeclaringClass();
//    return 1 < count( $declaring_classes );
//  }
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
   * @return RESTian_Request
   */
  function prepare_request( $request ){
    return $request;
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
          'url_path' => '/authenticate',
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
   * @param $service
   * @return string
   */
  function get_error_message( $error_code, $service ) {
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
    $service_url = $this->base_url . $service->url_path;
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
