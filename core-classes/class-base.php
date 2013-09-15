<?php

class RESTian_Base {

  /**
   * Adds a filter hook for an object
   *
   * 	$this->add_filter( 'filter_result' );
   * 	$this->add_filter( 'filter_result', 11 );
   * 	$this->add_filter( 'filter_result', 'special_func' );
   * 	$this->add_filter( 'filter_result', 'special_func', 11 );
   * 	$this->add_filter( 'filter_result', array( __CLASS__, 'class_func' ) );
   * 	$this->add_filter( 'filter_result', array( __CLASS__, 'class_func' ), 11 );
   * 	$this->add_filter( 'filter_result', new SpecialClass() );
   * 	$this->add_filter( 'filter_result', new SpecialClass(), 11 );
   *
   * @param string $filter_name
   * @param string|array|object|int $callable_object_or_priority Array Callable, String Callable, Class w/Named Filter or Priority
   * @param int|null $priority
   *
   * @return mixed
   */
  function add_filter( $filter_name, $callable_object_or_priority = null, $priority = null ) {
    list( $callable, $priority ) = $this->_parse_callable_args( $filter_name, $callable_object_or_priority, $priority );
    if ( defined( 'WP_CONTENT_DIR') ) {
      $filter_name = spl_object_hash( $this ) . "->{$filter_name}()";
      add_filter( $filter_name, $callable, $priority, 99 );
    } else {
      RESTian::add_filter( $filter_name, $callable, $priority );
    }
    return true;
  }
  /**
   * Adds a filter hook for an object
   *
   * 	$this->add_action( 'process_action' );
   * 	$this->add_action( 'process_action', 11 );
   * 	$this->add_action( 'process_action', 'special_func' );
   * 	$this->add_action( 'process_action', 'special_func', 11 );
   * 	$this->add_action( 'process_action', array( __CLASS__, 'class_func' ) );
   * 	$this->add_action( 'process_action', array( __CLASS__, 'class_func' ), 11 );
   * 	$this->add_action( 'process_action', new SpecialClass() );
   * 	$this->add_action( 'process_action', new SpecialClass(), 11 );
   *
   * @param string $action_name
   * @param string|array|object|int $callable_object_or_priority Array Callable, String Callable, Class w/Named Action or Priority
   * @param int|null $priority
   *
   * @return mixed
   */
  function add_action( $action_name, $callable_object_or_priority = null, $priority = null ) {
    list( $callable, $priority ) = $this->_parse_callable_args( $action_name, $callable_object_or_priority, $priority );
    if ( defined( 'WP_CONTENT_DIR') ) {
      add_action( $this->_hash_hook_name( $action_name ), $callable, $priority, 99 );
    } else {
      RESTian::add_action( $action_name, $callable, $priority );
    }
  }

  /**
   * @param string $filter_name
   * @param mixed $value
   *
   * @return mixed
   */
  function apply_filters( $filter_name, $value ) {
    if ( defined( 'WP_CONTENT_DIR') ) {
      $args = func_get_args();
      $args[0] = $this->_hash_hook_name( $filter_name );
      $value = call_user_func_array( 'apply_filters', $args );
    } else {
      $filters = RESTian::get_filters( $filter_name );
      if ( count( $filters ) ) {
        $args = func_get_args();
        array_shift( $args );
        foreach( $filters as $function ) {
          /**
           * Sort by priorty
           */
          ksort( $function );
          foreach( $function as $priority ) {
            foreach( $priority as $callable ) {
              $value = call_user_func_array( $callable, $args );
            }
          }
        }
      }
    }
    return $value;
  }

  /**
   * @param string $action_name
   *
   * @return mixed
   */
  function do_action( $action_name ) {
    if ( defined( 'WP_CONTENT_DIR') ) {
      $args = func_get_args();
      $args[0] = $this->_hash_hook_name( $action_name );
      call_user_func_array( 'do_action', $args );
    } else {
      call_user_func_array( array( $this, 'apply_filters' ), func_get_args() );
    }
  }

  /**
   * Parse args for $this->add_action() and $this->add_filter().
   *
   * @param string $filter_name
   * @param string|array|object|int $callable_object_or_priority Array Callable, String Callable, Class w/Named Action or Priority
   * @param int|null $priority
   *
   * @return array
   */
  private function _parse_callable_args( $filter_name, $callable_object_or_priority = null, $priority = null ) {
    if ( ! $callable_object_or_priority ) {
      $callable = array( $this, "_{$filter_name}" );
    } else if ( is_numeric( $callable_object_or_priority ) ) {
      $priority = $callable_object_or_priority;
    } else if ( is_string( $callable_object_or_priority ) ) {
      $callable = array( $this, $callable_object_or_priority );
    } else if ( is_array( $callable_object_or_priority ) ) {
      $callable = $callable_object_or_priority;
    } else if ( is_object( $callable_object_or_priority ) ) {
      $callable = array( $callable_object_or_priority, "_{$filter_name}" );
    }
    if ( is_null( $priority ) ) {
       $priority = 10;
    }
    return array( $callable, $priority );
  }

  private function _hash_hook_name( $hook_name ) {
    return spl_object_hash( $this ) . "->{$hook_name}()";
  }

}
