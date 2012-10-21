<?php

/**
 * A RESTian_Http_Agent represents the internal type of agent that calls HTTP:
 *
 * 	'wp_remote_get' for WordPress wp_remote_get()
 * 	'php_curl' for PHP's CURL
 *
 * Having more than one agent allows RESTian to be used outside WordPress.
 *
 */
class RESTian_Http_Agent {
	var $agent_type;
	var $error_num = false;
	var $error_msg = false;
	function __construct( $agent_type ) {
		$this->agent_type = $agent_type;
	}
	/**
	 * Provides a generic HTTP GET method that can call WordPress' wp_remove_get() if available, or CURL if not.
	 *
	 * @param RESTian_Request $request
	 * @param RESTian_Response $response
	 * @return RESTian_Response
	 */
	function make_request( $request, $response ) {
		/**
		 * TODO: Create a Class Factory for this for wp_remote_get and php_curl
		 */
		if ( 'wp_remote_get' == $this->agent_type ) {

			$result = wp_remote_get( $request->get_url(), $request->get_wp_args() );

			if ( is_wp_error( $result ) ) {
				/**
				 * These errors likely won't be 100% compatible with the errors from CURL when standalone
				 */
				$error_num = $result->get_error_code();
				$response->set_http_error( $error_num, $result->get_error_message( $error_num ) );
			} else {
				$response->message = wp_remote_retrieve_response_message( $result );
			}

			$response->status_code = wp_remote_retrieve_response_code( $result );
			$response->body = wp_remote_retrieve_body( $result );
			$response->result = $result;

		} else {

			$ch = curl_init();

			curl_setopt_array(
				$ch, array(
					CURLOPT_URL => $request->get_url(),
					CURLOPT_USERAGENT => $request->user_agent,
					CURLOPT_HTTPHEADER => $request->get_curl_headers(),
					CURLOPT_POST => false,
					CURLOPT_HEADER => false,
					CURLOPT_TIMEOUT => '30',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => $request->sslverify,
					CURLOPT_SSL_VERIFYHOST => ( true === $request->sslverify ) ? 2 : false,
			));
			if ( $request->include_body ) {
				// TODO: Add content-type processsing of body -> data so we don't have to normally store body
				$response->body = trim( curl_exec( $ch ) );
			}

			$info = curl_getinfo($ch);
			$response->status_code = $info['http_code'];

			if ( 0 != curl_errno( $ch ) )
				$response->set_http_error( curl_errno( $ch ), curl_error( $ch ) );

			if ( $request->include_result ) {
				$response->result = (object)array(
					'info' => $info,
					'version' => curl_version(),
					'error' => curl_error( $ch ),
					'errno' => curl_errno( $ch ),
				);
			}
			curl_close($ch);
		}
		return $response;
	}

}

