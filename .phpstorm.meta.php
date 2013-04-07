<?php
/**
 * Used by PhpStorm to map factory methods to classes for code completion, source code analysis, etc.
 *
 * The code is not ever actually executed and it only needed during development when coding with PhpStorm.
 *
 * @see http://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @see http://blog.jetbrains.com/webide/2013/04/phpstorm-6-0-1-eap-build-129-177/
 */

namespace PHPSTORM_META {

    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */

    $STATIC_METHOD_TYPES = array(
      RESTIan::get_new_parser('') => array(
        'application/xml'   instanceof RESTian_Application_Xml_Parser,
        'application/json'  instanceof RESTian_Application_Json_Parser,
        'text/plain'        instanceof RESTian_Text_Plain_Parser,
        'text/html'         instanceof RESTian_Text_Html_Parser,
        'text/csv'          instanceof RESTian_Text_Csv_Parser,
        'application/vnd.php.serialized' instanceof RESTian_Application_Serialized_Php_Parser,
      ),
      RESTIan::get_new_auth_provider('') => array(
        'n/a' instanceof RESTian_Not_Applicable_Provider,
        'basic_http' instanceof RESTian_Basic_Http_Auth_Provider,
      ),
      RESTIan::get_new_http_agent('') => array(
        'wordpress' instanceof RESTian_WordPress_Http_Agent,
        'php_curl' instanceof RESTian_Php_Curl_Http_Agent,
      ),
    );

}
