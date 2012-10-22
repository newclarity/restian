<?php

/**
 * TODO: Decide exactly what an HTML parser should do.  What format of PHP objects/arrays?
 */
class RESTian_Text_Html_Parser extends RESTian_Parser_Base {
  /**
   * Returns an object or array of stdClass objects from a string containing HTML
   *
   * @param string $body
   * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed HTML string.
   * @throws Exception
   */
  function parse( $body ) {
    if (1)  // This logic here only to get PhpStorm to stop highlighting the return as an error.
      throw new Exception( 'The ' . __CLASS__ . ' class has not yet been implemented.' );
    return $body;
  }
}
