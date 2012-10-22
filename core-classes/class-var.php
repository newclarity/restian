<?php

class RESTian_Var {
  /**
   * @var string Actual name of URL query argument.  Defaults to same value as $this->name.
   */
  var $name;
  /**
   * @var string if specified, indicates what this param is used for. Basically it's a description.
   */
  var $requires = false;
  /**
   * @var bool|array List of other parameter names that make this parameter invalid when they appear.
   * If in "name=value" format then only invalid when the parameter appears with the specified value.
   */
  var $not_vars = false;
  /**
   * @var bool|array List of options that are valid for this parameter.
   */
  var $options = false;
  /**
   * @var bool|string Specifies how this param is used; as part of the URL 'path', in the URL 'query', or 'both'.
   */
  var $usage = 'query';
  /**
   * @var bool|string Specifies the data type expected. Currently just 'string' or 'number'.
   */
  var $type = 'string';
  /**
   * @var bool|array List of Transforms that can be applied to value, i.e. fill[/] replaces whitespace with '/'
   */
  var $transforms = array();
  function __construct( $var_name, $args = array() ) {
    /**
     * If a string, could be 'foo' or 'var=foo' maybe
     */
    if ( is_string( $args ) ) {
      /**
       * If just 'foo' set $var = 'foo'
       */
      if ( true === strpos( $args, '=' ) ) {
        $args = array( 'var' => $args );
      } else {
        /**
         * If 'var=foo' turn into array( 'var'=>'foo' )
         */
        parse_str( $args, $args );
      }
    }

    if ( ! is_object( $args ) && ! is_array( $args ) ) {
      throw new Exception( 'Must pass a string, array or object for $args when creating a new ' . __CLASS__ . '.' );
    }

    $this->name = $var_name;
    /**
     * Copy properties in from $args, if they exist.
     */
    foreach( $args as $property => $value )
      if ( property_exists(  $this, $property ) )
        $this->$property = $value;

    /*
     * Convert strings to arrays.
     */
    if ( is_string( $this->options ) )
      $this->options = RESTian::parse_string( $this->options );

    if ( is_string( $this->not_vars ) )
      $this->not_vars = RESTian::parse_string( $this->not_vars );

    if ( is_string( $this->transforms ) )
      $this->transforms = RESTian::parse_transforms( $this->transforms );

  }
  function apply_transforms( $value ) {
    $new_value = $value;
    foreach( $this->transforms as $transform => $data ) {
      switch( $transform ) {
        case 'fill':
          $new_value = preg_replace( '#\s+#', $data, $new_value );
          break;
      }
    }
    return $new_value;
  }
}
