<?php

define( 'RESTIAN_VER', '0.1.2' );
define( 'RESTIAN_DIR', dirname( __FILE__ ) );

require( RESTIAN_DIR . '/classes/class-client.php' );
require( RESTIAN_DIR . '/classes/class-request.php' );
require( RESTIAN_DIR . '/classes/class-response.php' );
require( RESTIAN_DIR . '/classes/class-var.php' );
require( RESTIAN_DIR . '/classes/class-service.php' );
require( RESTIAN_DIR . '/classes/class-http-agent.php' );

require( RESTIAN_DIR . '/classes/classes-auth-providers.php' );
require( RESTIAN_DIR . '/classes/classes-parsers.php' );


class RESTian {
	protected static $_clients = array();
	protected static $_auth_providers = array();
	protected static $_parsers = array();
	protected static $_http_agents = array();

	/**
	 * @param $client_name
	 * @param string|array $client If string then the class name of the RESTian client. If array, $args to find it.
	 *
	 * @notes $client_name can be any of the following format:
	 *
	 * 	'local_short_code' - If all code it local
	 * 	'github_user_name/github_repo_name' - If code is on GitHub, master branch latest commit (not smart)
	 * 	'github_user_name/github_repo_name/tag_name' - If code is on GitHub, tagged commit
	 * 	'repo_host/user_name/repo_name' - If code is on GitHub or BitBucket, master branch latest commit (not smart)
	 * 	'repo_host/user_name/repo_name/tag_name' - If code is on GitHub or BitBucket, tagged commit
	 *
	 *	'repo_host' => 'github.com' or 'bitbucket.org'
	 *
	 */
	static function register_client( $client_name, $client ) {
		self::$_clients[$client_name] = $parser_class;
	}
	static function construct_client( $client_name ) {
	}

	/**
	 * Registers a result parser class based on the mime type.
	 *
	 * @see: http://www.iana.org/assignments/media-types/index.html
	 *
	 * @param string $content_type valid mime type of RESTian shortcut (xml,json,html,plain,csv)
	 * @param string $parser_class Name of class implementing the parser
	 */
	static function register_parser( $content_type, $parser_class ) {
		self::$_parsers[self::expand_content_type( $content_type )] = $parser_class;
	}
	/**
	 * Constructs a new Parser instance
	 *
	 * @param string $content_type
	 * @param RESTian_Request $request
	 * @param RESTian_Response $response
	 * @param array $args
	 * @return RESTian_Parser
	 */
	static function construct_parser( $content_type, $request, $response, $args = array() ) {
		$content_type = self::expand_content_type( $content_type );
		if ( isset( self::$_parsers[$content_type] ) ) {
			$parser_class = self::$_parsers[$content_type];
		} else {
			list( $major, $minor ) = array_map( 'UCfirst', explode( '/', "{$content_type}/" ) );
			$parser_class = empty( $minor ) ? "RESTian_{$major}_Parser" : "RESTian_{$major}_{$minor}_Parser";
			self::$_parsers[$content_type] = $parser_class;
		}
		return new $parser_class( $request, $response );
	}
	/**
	 * Registers a result parser class based on the mime type.
	 *
	 * @see: http://www.iana.org/assignments/media-types/index.html
	 *
	 * @param string $auth_type valid auth type for RESTian: basic_http, etc.
	 * @param string $auth_class Name of class implementing the auth provider
	 */
	static function register_auth_provider( $auth_type, $auth_class ) {
		self::$_auth_providers[] = $auth_class;
	}
	/**
	 * Constructs a new Auth Provider instance
	 *
	 * @param string $auth_type RESTian-specific type of auth providers
	 * @param array $args
	 * @return RESTian_Auth_Provider
	 */
	static function construct_auth_provider( $auth_type, $args = array() ) {
		if ( isset( self::$_auth_providers[$auth_type] ) ) {
			$auth_class = self::$_auth_providers[$auth_type];
		} else {
			$auth_type = str_replace( '-', '_', $auth_type );
			$auth_class = implode( '_', array_map( 'UCfirst', explode( '_', "{$auth_type}_" ) ) );
			$auth_class = "RESTian_{$auth_class}Auth_Provider";
		}
		return new $auth_class( $auth_type, $args );
	}

	/**
	 * Registers an EXTERNAL HTTP Agent type
	 *
	 * @param string $agent_type RESTian-specific type of HTTP agent
	 * @param string $class_name Name of class that defines this HTTP agent
	 * @param string $filepath Full local file path for the file containing the class.
	 * @return RESTian_Http_Agent
	 */
	static function register_http_agent( $agent_type, $class_name = false, $filepath = false ) {
		/**
		 * Hardcode the predefined agent types this way because it appears this is most performant approach
		 * and most efficient use of memory vs. pre-registering them.
		 * Predefined types ignore class_name and filepath.
		 */
		$internal = true;
		switch ( $agent_type ) {
			case 'wordpress':
				$agent = array(
					'class_name'=> 'RESTian_Wordpress_Http_Agent',
					'filepath' 	=> RESTIAN_DIR . '/http-agents/wordpress-http-agent.php',
				);
				break;
			case 'php_curl':
				$agent = array(
					'class_name'=> 'RESTian_Php_Curl_Http_Agent',
					'filepath' 	=> RESTIAN_DIR . '/http-agents/php-curl-http-agent.php',
				);
				break;
			default:
				$internal = false;
				/**
				 * Or if an externally default http agent, do this:
				 */
				$agent = array(
					'class_name'=> $class_name,
					'filepath' 	=> $filepath,
				);
				break;
		}
		if ( $internal ) {
			if ( $class_name )
				$agent['class_name'] = $class_name;

			if ( $filepath )
				$agent['filepath'] = $filepath;
		}
		self::$_http_agents[$agent_type] = $agent;
	}

	/**
	 * Constructs a new HTTP Agent instance
	 *
	 * @param string $agent_type RESTian-specific type of HTTP agent
	 * @param array $args Array of values to path to the HTTP agent constructor, if needed
	 * @return RESTian_Http_Agent
	 */
	static function construct_http_agent( $agent_type, $args = array() ) {
		if ( isset( self::$_http_agents[$agent_type] ) ) {
			$class_name = self::$_http_agents[$agent_type]['class_name'];
		} else {
			self::register_http_agent( $agent_type );
			$agent = self::$_http_agents[$agent_type];
			require_once( $agent['filepath'] );
			$class_name = $agent['class_name'];
		}
		return new $class_name( $args );
	}

	/**
	 * Expands the following shortcut content types to their valid mime type:
	 *
	 * @see: http://www.iana.org/assignments/media-types/index.html
	 *
	 * 	xml 	=> application/xml
	 * 	json 	=> application/json
	 * 	html 	=> text/html
	 * 	plain => text/plain
	 * 	csv 	=> text/csv
	 *
	 * @param string $content_type
	 * @return string
	 */
	static function expand_content_type( $content_type ) {
		if ( false !== strpos( 'jx', $content_type[0] ) )  {
			$content_type = preg_replace( '#^(json|xml)$#', 'application/$1', $content_type );
		} else if ( false !== strpos( 'htc', $content_type[0] ) ) {
			$content_type = preg_replace( '#^(html|text|csv)$#', 'text/$1', $content_type );
		}
		return $content_type;
	}

	/**
	 * Parses a string of arguments using a data format optimized for the use-case.
	 *
	 * The data format is similar to URL query string format but it uses vertical bars ('|') as seperators
	 * instead of ampersands ('&') because ampersands are frequently used in URLs. Names without no equals
	 * sign ('=') following are set to boolean true. Names prefixed with exclamation point ('!') will negate
	 * the value they would otherwise have, so a name with an exclamation point and no equals sign will
	 * be set to boolean false which is the primary use case for the ('!') syntax.
	 *
	 * @example 'foo|!bar|baz=zoom' parses to
	 *
	 * 		array(
	 * 			'foo' => true,
	 * 			'bar' => false,
	 * 			'baz' => 'zoom',
	 *  	)
	 *
	 * @param $args
	 * @return array
	 */
	static function parse_args( $args ) {
		if ( is_string( $args ) ) {
			$args = explode( '|', $args );
			$new_args = array();
			foreach( $args as $arg ) {
				list( $name, $value ) = array_map( 'trim', explode( '=', "{$arg}=" ) );
				if ( 0 == strlen( $value ) ) {
					$value = true;
				} else if ( preg_match( '#^(true|false)$#', $value ) ) {
					$value = 't' == $value[0];
				}
				if ( '!' ==$name[0] ) {
					/**
					 * If $name begins with '!' then we want to NOT it's value.
					 * If no values was passed (i.e. "!include_body" was the value) then
					 * then that will set 'include_body' => false which was the goal of
					 * adding this syntax sugar.
					 */
					$name = substr( $name, 1 );
					$value = ! $value;
				}
				$new_args[$name] = $value;
			}
			$args = $new_args;
		}
		return $args;
	}
	/**
	 * Takes a like this 'color,type=all,size' and translates into an array that looks like this:
	 *
	 * 		array(
	 * 			'color' => true,
	 * 			'type'  => 'all'
	 * 			'size'  => true
	 * 		);
	 *
	 * @param string $string
	 * @param string $separator
	 * @return array
	 */
	static function parse_string( $string, $separator = ',' ) {
		$array = array_fill_keys( explode( $separator, $string ), true );
		foreach( $array as $name => $value ) {
			unset( $array[$name] );
			if ( preg_match( '#=#', $name ) ) {
				list( $name, $value ) = explode( '=', $name );
			}
			$array[$name] = $value;
		}
		return $array;
	}
	/**
		 * Parses comma seperated transforms like this:
		 *
		 * 		fill[/],replace[a][b],trim
		 *
		 * To this:
		 *
		 *		array(
		 *			'fill' => '/'
		 *		 	'replace' => array( 'a','b' ),
		 *		 	'trim' => true,
		 * 		)
		 *
		 * @param string $string
		 * @param string $separator
		 * @return array
		 */
	static function parse_transforms( $string, $separator = ',' ) {
			$transforms = RESTian::parse_string( $string, $separator );
			$new_transforms = array();
			foreach( array_keys( $transforms ) as $name ) {
				if ( ! preg_match( '#\[#', $name ) ) {
					$new_transforms[$name] = true;
				} else {
					$data = explode( '[', $name );
					$name = array_shift( $data );
					foreach( $data as $index => $value ) {
						$data[$index] = trim( $value, '][' );
					}
					$new_transforms[$name] = 1 == count( $data ) ? $data[0] : $data;
				}
			}
			return $new_transforms;
		}
}
