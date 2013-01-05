<?php
// @todo: Need to support HTTP PUT AND DETEE
/**
 * An HTTP Agent for RESTian that uses WordPress' WP_Http class and related functions to make HTTP requests.
 */
class RESTian_Wordpress_Http_Agent extends RESTian_Http_Agent_Base {
  /**
   * Makes an HTTP request using WordPress' WP_Http class and related functions.
   *
   * @param RESTian_Request $request
   * @param RESTian_Response $response
   * @return RESTian_Response
   */
  function make_request( $request, $response ) {

    switch ( $request->http_method ) {
      case 'GET':
        $url = $request->get_url();
        $args = $request->get_wp_args();
        $result = wp_remote_get( $url, $args );
        break;
      case 'POST':
        $url = $request->get_url();
        $args = $request->get_wp_args();
        $result = wp_remote_post( $url, $args );
        break;
      case 'PUT':
        $result = new WP_Error( -1, 'HTTP PUT not yet supported.' );
        break;
      case 'DELETE':
        $result = new WP_Error( -2, 'HTTP DELETE not yet supported.' );
        break;
    }
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

