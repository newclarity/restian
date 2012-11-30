<?php

/**
 * TODO: Need to test this one first.
 */
class RESTian_Application_Serialized_Php_Parser extends RESTian_Parser_Base {
  /**
   * Returns an object or array of stdClass objects from a string containing valid Serialized PHP
   *
   * @param string $body
   * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed Serialized PHP string.
   */
  function parse( $body ) {
    return unserialize( $body );
  }
}
