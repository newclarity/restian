<?php

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
