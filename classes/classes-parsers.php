<?php

abstract class RESTian_Parser {
	var $request;
	var $response;

	/**
	 * @param RESTian_Request $request
	 * @param RESTian_Response $response
	 */
	function __construct( $request, $response ) {
		$this->request = $request;
		$this->response = $response;
	}
	/**
	 * Used to throw an exception when not properly subclassed.
	 *
	 * @param string $body
	 * @return array|object
	 * @throws Exception
	 */
	function parse( $body ) {
		if (1)	// This logic here only to get PhpStorm to stop highlighting the return as an error.
			throw new Exception( 'Class ' . get_class($this) . ' [subclass of ' . __CLASS__ . '] must define a parse() method.' );
		return array();
	}
}
class RESTian_Application_Xml_Parser extends RESTian_Parser {
	/**
	 * Returns an object or array of stdClass objects from an XML string.
	 *
	 * @note Leading and trailing space are trim()ed.
	 *
	 * @todo: Determine how to capture element attributes into the return value.
	 *
	 * @see http://www.bookofzeus.com/articles/convert-simplexml-object-into-php-array/
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
/**
 * TODO: Need to test this one first.
 */
class RESTian_Application_Json_Parser extends RESTian_Parser {
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
class RESTian_Text_Plain_Parser extends RESTian_Parser {
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
/**
 * TODO: Decide exactly what an HTML parser should do.  What format of PHP objects/arrays?
 */
class RESTian_Text_Html_Parser extends RESTian_Parser {
	/**
	 * Returns an object or array of stdClass objects from a string containing HTML
	 *
	 * @param string $body
	 * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed HTML string.
	 * @throws Exception
	 */
	function parse( $body ) {
		if (1)	// This logic here only to get PhpStorm to stop highlighting the return as an error.
			throw new Exception( 'The ' . __CLASS__ . ' class has not yet been implemented.' );
	  return $body;
	}
}

/**
 * TODO: Decide exactly what a CSV parser should do.  What format of PHP objects/arrays?
 */
class RESTian_Text_Csv_Parser extends RESTian_Parser {
	/**
	 * Returns an object or array of stdClass objects from a string containing HTML
	 *
	 * @param string $body
	 * @return array|object|void A(n array of) stdClass object(s) with structure dictated by the passed HTML string.
	 * @throws Exception
	 */
	function parse( $body ) {
		if (1)	// This logic here only to get PhpStorm to stop highlighting the return as an error.
			throw new Exception( 'The ' . __CLASS__ . ' class has not yet been implemented.' );
	  return $body;
	}
}
