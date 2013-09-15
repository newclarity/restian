<?php
// @todo: Need to support HTTP PUT AND DETEE
/**
 * An HTTP Agent for RESTian that uses WordPress' WP_Http class and related functions to make HTTP requests.
 */
class RESTian_WordPress_Http_Agent extends RESTian_Http_Agent_Base {
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
        $args = $this->get_args( $request );
        $result = wp_remote_get( $url, $args );
        break;
      case 'POST':
        if ( $content_type = $request->get_content_type() )
          $request->add_header( 'Content-type', $content_type );
        $url = $request->get_url();
        $args = $this->get_args( $request );
        $result = wp_remote_post( $url, $args );
        break;
      case 'PUT':
        $result = new WP_Error( -1, 'HTTP PUT not yet supported.' );
        break;
      case 'DELETE':
        $result = new WP_Error( -2, 'HTTP DELETE not yet supported.' );
        break;
    }

    if ( method_exists( $request->client, 'filter_result' ) )
      $result = $request->client->filter_result( $result, $response );

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
    $response->body = $request->client->apply_filters( 'result_body', $response->body, $response );

    $response->result = $result;

    return $response;
  }

  /**
   * @param RESTian_Request $request
   *
   * @return array
   */
  function get_args( $request ) {
    $args = array(
      'method'      => $request->http_method,
      'headers'     => $request->get_headers(),
      'body'        => $request->get_body(),
      'sslverify'   => $request->sslverify,
      'user-agent'  => $request->client->get_user_agent(),
    );
    return $args;
  }


}

