<?php
// @todo Need to support POST, PUT, DELETE
/**
 * An HTTP Agent for RESTian that uses PHP's CURL to make HTTP requests.
 */
class RESTian_Php_Curl_Http_Agent extends RESTian_Http_Agent_Base {
  /**
   * Makes an HTTP request using PHP's CURL
   *
   * @param RESTian_Request $request
   * @param RESTian_Response $response
   * @return RESTian_Response
   */
  function make_request( $request, $response ) {

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

    return $response;
  }

}

