<?php

class RESTian_Auth_Provider {
	/**
	 * @var string RESTian-specific authorization type identifier, like 'basic_http', etc.
	 */
	var $auth_type;
	/**
	 * @var string To contain something like '1.0a', to be set by subclass if needed.
	 */
	var $auth_version;
	/**
	 * @var RESTian_Request
	 */
	var $request;
	/**
	 * @var RESTian_Service
	 */
	var $service;
	function __construct( $auth_type, $args = array() ) {
		$this->auth_type = $auth_type;
		if ( isset( $args['request'] ) )
			$this->request = $args['request'];
	}
	/**
	 * @return array
	 */
	function get_new_credentials() {
		return array();
	}

	/**
	 * @param array $credentials
	 * @return bool
	 */
	function has_credentials( $credentials ) {
		return false;
	}

	/**
	 * @param array $credentials
	 * @return array
	 */
	function set_credentials( $credentials ) {
		return $credentials;
	}

	/**
	 * @return RESTian_Response
	 */
	function authenticate() {
	}
}

class RESTian_Basic_Http_Auth_Provider extends RESTian_Auth_Provider {
	/**
	 * @return array
	 */
	function get_new_credentials() {
		return array(
			'username' => '',
			'password' => '',
		);
	}

	/**
	 * @param array $credentials
	 * @return bool
	 */
	function has_credentials( $credentials ) {
		$has_credentials = ! empty( $credentials['username'] ) && ( ! empty( $credentials['password'] ) );
		return $has_credentials;
	}

	/**
	 * @param array $credentials
	 * @return object
	 */
	function set_credentials( $credentials ) {
		$auth = base64_encode( "{$credentials['username']}:{$credentials['password']}" );
		$this->request->add_header( 'Authorization', "Basic {$auth}" );
		return $credentials;
	}

	/**
	 *
	 * $this->context should contain a RESTian_Request
	 *
	 * @see: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success
	 *
	 * @param array $credentials
	 * @return RESTian_Response
	 */
	function authenticate( $credentials ) {

		$response = $this->request->make_request();
		/*
		 * We got a 2xx status code from HTTP; a success.
		 */
		$response->authenticated = preg_match( '#^2[0-9]{2}$#', $response->status_code );
		return $response;
	}
}
