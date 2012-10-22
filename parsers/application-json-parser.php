<?php

/**
 * TODO: Need to test this one first.
 */
class RESTian_Application_Json_Parser extends RESTian_Parser_Base {
  /**
   * Returns an object or array of stdClass objects from a string containing valid JSON
   *
   * @param string $body
   * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed JSON string.
   */
  function parse( $body ) {
    return json_decode( $body );
  }
}
