<?php

/**
 * An HTTP Agent for RESTian that uses WordPress' WP_Http class and related functions to make HTTP requests.
 */
class RESTian_Wordpress_Http_Agent extends RESTian_Http_Agent {
	/**
	 * Makes an HTTP request using WordPress' WP_Http class and related functions.
	 *
	 * @param RESTian_Request $request
	 * @param RESTian_Response $response
	 * @return RESTian_Response
	 */
	function make_request( $request, $response ) {

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

		return $response;
	}

}

