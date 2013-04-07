#RESTian

RESTian is a base class library to simplify building RESTful/Web API clients/SDKs in PHP.  

RESTian allows you to _"map"_ an API declaratively where possible and then use subclass-based _"hooks"_ to handle anything that cannot be mapped declaratively using procedural logic.

RESTian was built for use with **WordPress** but is designed to also work with **standalone PHP projects** or other PHP frameworks.

##Documentation Status
This documentation is a work in progress and currently incomplete. However, if you have ask questions not answered yet please [**submit an issue**](https://github.com/newclarity/restian/issues) to request improvement in the documentation. The better questions you ask, the more quickly we'll be able to both answer your question and update the documentation.

##Concepts

###Auth Providers
Auth Providers allow a RESTian API client/SDK to authenticate against a web APIs authentication scheme. Currently RESTian supports HTTP Basic Auth (`'basic_http'`) with two-legged OAuth 2 soon to come and any not yet implemented can be implemented via RESTian Extension and registered via `RESTian::register_auth_provider()`. 

###Content Type Parsers
Content Type Parsers process the HTTP response representation that are returned by web API calls. RESTian currently supports JSON (`'application/json'`), XML (`'application/json'`), Serialized PHP (`'application/vnd.php.serialized'`) and Plain Text (`'text/plain'`) representations and any not yet implemented can be implemented via a RESTian Extension and registered via `RESTian::register_parser()`. 

###HTTP Agents 
An HTTP Agent is the PHP code used to process HTTP requests and responses. Currently RESTian support WordPress (`'wordpress'`) and PHP CURL (`'php_curl'`). Any not yet implemented can be implemented via RESTian Extension and registered via `RESTian::register_http_agent()`. 

###Credentials
Credentials are the values provided from the user/client agent to the API to authenticate against the API. Common examples of Credentials include _"username"_ and _"password"_ or _"api_token"_. Credentials are provided to RESTian in the form of a associative array.

###Grants 
Grants are values provided by the API when a set of Credentials are authenticated. The values are often in the form of a token. An example of a Grants is the values for _"access_token"_ and _"refresh_token"_ used by OAuth 2. Grants are managed by RESTian in the form of a associative array.

###Extensions
RESTian is designed to be extensible to support practically any web API implementation required. API clients build using RESTian can register classes for Auth Providers, Content Type Parsers and HTTP Agents.

###Hooks

Hooks in RESTian context are optional methods of the `RESTian_Client` subclass. They allow for procedural processing for APIs and for authentication that RESTian does not already natively handle and that cannot be easily _"mapped"_ using a declarative approach.


##Classes
RESTian contains several different types of classes:

- Root Class
- Core Classes
- Base Classes
- Extension Classes

###Root Class
The `RESTian` class is the main root class in RESTian and it contains helper methods, extension class registration, and class factory methods for built-in and extension classes.

###Core Classes
These are the core standalone classes for RESTian which are all required is and thus all loaded if the `RESTian` class itself is loaded:
- `RESTian_Client` - Base class for an RESTian-based API client class. This is the class that any RESTian-based API client will subclass.
- `RESTian_Request` - Models an HTTP request and is used by `RESTian_Client`.
- `RESTian_Response` - Models an HTTP response and is used by `RESTian_Client`.
- `RESTian_Service` - Models a service that can be acccessed via HTTP. 
- `RESTian_Var` - Models a URL variable, i.e. either a query variable or URL template variable.

###Base Classes
These classes are designed to be subclassed to create the several different types of extensions for RESTian.

- `RESTian_Auth_Provider_Base` - Base class for an Auth Provider extension.
- `RESTian_Parser_Base` - Base class for a Content Type Parser extension.
- `RESTian_Http_Agent_Base` - Base class for a HTTP Agent extension.

###Extension Classes
These classes are subclasses of the Base classes and implement the default extensions provided by RESTian:

- `RESTian_Basic_Http_Auth_Provider` - Implements HTTP Basic Authentication.
- `RESTian_Not_Applicable_Provider` - Empty class used when authentication is not required.
- `RESTian_WordPress_Http_Agent` - Implements HTTP Agent using WordPress' HTTP functionality.
- `RESTian_Php_Curl_Http_Agent` - Implements HTTP Agent using PHP's CURL functionality.
- `RESTian_Application_Json_Parser ` - Implements JSON Content Type Parser.
- `RESTian_Application_Xml_Parser ` - Implements XML Content Type Parser.
- `RESTian_Application_Serialized_Php_Parser ` - Implements Serialized PHP Content Type Parser.
- `RESTian_Text_Plain_Parser ` - Implements Plain Text Content Type Parser.
- `RESTian_Text_Csv_Parser ` - Stub class for future implementation of Comma Separated Value (CSV) Content Type Parser.
- `RESTian_Text_Html_Parser ` - Stub class for future implementation of HTML Content Type Parser.



##Platform

RESTian was designed and built to support the development of **WordPress** plugins however it is intended to work with **standalone PHP** or other PHP frameworks.

When RESTian is used with WordPress it uses WordPress' built in and truly excellent set of HTTP functions.  When used outside of WordPress RESTian uses PHP's built-in CURL libraries. 

Given the focus of our development RESTian it is likely better tested on WordPress so help in testing on non-WordPress projects will be greatly appreciated.

##License

RESTian is currently licensed via [GPL v2.0](http://www.gnu.org/licenses/gpl-2.0.html). 

###Note to contributors
At the time of this writing we chose GPL v2.0 license but we don't actually know if that choice will limit future growth of RESTian. In the future we may choose to change the license or multiply license RESTian via one of the following licenses, or one that is substantially similar:

- MIT
- BSD
- Apache 2.0

Please be aware of this before you choose to contribute because by contributing you will be giving your consent to using any of these licenses and/or one that is substantially similar to one of these licenses. _(If we were OSS lawyers then we'd probably already know which one is the right to choose but hey, we decided to build things for a living instead of interpret what others previously built. :)_
