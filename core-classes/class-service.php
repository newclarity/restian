<?php


/**
 * A RESTian_Service represents a service that can be called on an API.
 *
 * Often a service is defined as a URL endpoint plus an HTTP method.
 * RESTian_Client subclasses are responsible to register the API services that they need to call.
 *
 */
class RESTian_Service {
  /**
   * @var string - Name of this service in lowercase.
   */
  var $service_name;
  /**
   * @var string Type of service - 'resource', 'service'
   */
  var $service_type = 'service';
  /**
   * @var string
   */
  var $path = '/';
  /**
   * @var string Specifies content type expected using RESTian defined content types.
   */
  var $content_type = 'json';
  /**
   * @var RESTian_Client - Reference back to the API that is managing these services.
   */
  var $client;
  /**
   * @var bool|array List of other variable names that are required for this parameter to be valid.
   */
  var $requires = false;
  /**
   * @var bool|array List of other variable names that make this variable invalid when they appear.
   * If in "name=value" format then only invalid when the parameter appears with the specified value.
   */
  var $not_vars = false;
  /**
   * @var array List of valid parameter variables
   */
  var $vars = false;

  /**
   * @param string $service_name
   * @param RESTian_Client $client
   * @param array $args
   * @throws Exception
   */
  function __construct( $service_name, $client, $args = array() ) {
    $this->service_name = strtolower( $service_name );
    $this->client = $client;

    /**
     * Set any defaults not set
     */
    foreach( $this->client->get_service_defaults() as $name => $value ) {
      if ( ! isset( $args[$name] ) ) {
        $args[$name] = $value;
      }
    }
    /**
     * Copy properties in from $args, if they exist.
     */
    foreach( $args as $property => $value )
      if ( property_exists(  $this, $property ) )
        $this->$property = $value;

    /*
     * Transform from shortcut names like json and xml to valid mime type names application/json and application/xml.
     * @see: http://www.iana.org/assignments/media-types/index.html
     */
    $this->content_type = RESTian::expand_content_type( $this->content_type );

    /*
     * Convert strings to arrays.
     */
    if ( is_string( $this->requires ) )
      $this->requires = RESTian::parse_string( $this->requires );

    /*
     * TODO: Note=> 'not_vars' is not yet tested.
      */
    if ( is_string( $this->not_vars ) )
      $this->not_vars = RESTian::parse_string( $this->not_vars );
    if ( is_string( $this->vars ) )
      $this->vars = RESTian::parse_string( $this->vars );

    if ( isset( $args['var_set'] ) ) {
      $var_set_vars =  $client->get_var_set( $args['var_set'] );
      $this->vars = $this->vars ? array_merge( $var_set_vars, $this->vars ) : $var_set_vars;
    }
  }

  /**
   * @param $code
   *
   * @return string
   */
  function get_error_message( $code ) {
    /**
     * See if the API handles this error.
      */
    $message = $this->client->get_error_message( $code );
    if ( ! $message ) {
      $message = $this->client->get_auth_provider()->get_error_message( $code );
    }
    return $message;
  }

}

