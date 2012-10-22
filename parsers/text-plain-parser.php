<?php

class RESTian_Text_Plain_Parser extends RESTian_Parser_Base {
  /**
   * Returns an object or array of stdClass objects from a string containing HTML
   *
   * @param string $body
   * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed HTML string.
   */
  function parse( $body ) {
    return $body;
  }
}
