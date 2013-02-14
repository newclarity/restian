<?php

class RESTian_Application_Xml_Parser extends RESTian_Parser_Base {
  /**
   * Returns an object or array of stdClass objects from an XML string.
   *
   * @note Leading and trailing space are trim()ed.
   *
   * @todo: Determine how to capture element attributes into the return value.
   *
   * @see http://www.bookofzeus.com/articles/convert-simplexml-object-into-php-array/
   * @see http://php.net/manual/en/function.simplexml-load-string.php
   * @see http://hakre.wordpress.com/2013/02/12/simplexml-type-cheatsheet/
   *
   * @param string $body
   * @return array|object A(n array of) stdClass object(s) with structure dictated by the passed XML string.
   */
  function parse( $body ) {
    if ( empty( $body ) )
      return array();

    $is_array = false;
    $xml = is_string( $body ) ? new SimpleXMLElement( $body ): $body;
    $data = array();
    /**
     * @var SimpleXMLElement $element
     */
    foreach ($xml as $element) {
      $tag = $element->getName();
      $e = get_object_vars( $element );
      if ( ! empty( $e ) ) {
        $subset = $element instanceof SimpleXMLElement ? $this->parse( $element ) : $e;
      } else {
        $subset = trim( $element );
      }
      if ( ! isset( $data[$tag] ) ) {
        $data[$tag] = $subset;
      } else {
        if ( is_array( $data[$tag] ) ) {
          $data[$tag][] = $subset;
        } else {
          /**
           * Convert to an an array because we are seeing duplicate tags.
           */
          $data[$tag] = array( $data[$tag], $subset );
          $is_array = true;
        }
      }
    }
    return $is_array ? $data : (object)$data;
  }
}
