<?php

namespace Openveo\Client;

use Openveo\Client\RESTClient;
use Openveo\Exception\ClientException;

/**
 * Defines an OpenVeo Web Service client.
 */
class Client extends RESTClient {

  /**
   * Base url for all requests to the OpenVeo Web Service.
   * @var String
   */
  protected $baseUrl;

  /**
   * Basic base 64 encoded authentication credentials.
   * @var String
   */
  protected $credentials;

  /**
   * Access token returned by the Web Service.
   * @var String
   */
  protected $accessToken;

  /**
   * Builds a new client Web Service.
   *
   * @param String $webServiceUrl The complete url of the OpenVeo Web Service
   * @param String $id The client id
   * @param String $secret The client secret
   * @param String $certificate Path to the Web Service server trusted certificate file
   */
  public function __construct($webServiceUrl, $id, $secret, $certificate) {
    if (empty($webServiceUrl) || empty($id) || empty($secret))
      throw new ClientException('Url, client id and client secret are required to create an Openveo Client');

    $this->credentials = base64_encode($id . ':' . $secret);
    $this->baseUrl = rtrim($webServiceUrl, '/');

    parent::__construct($certificate);
  }

  /**
   * Removes access token and call parent destructor.
   */
  function __destruct() {
    $this->accessToken = null;
    parent::__destruct();
  }

  /**
   * Authenticates the client to the Web Service.
   *
   * @throws ClientException
   */
  public function authenticate() {
    $url = $this->baseUrl . '/token';
    list($decodedResults, $requestInfo) = parent::post($url, json_encode(array(
        'grant_type' => 'client_credentials'
        )), array(
        'Authorization: Basic ' . $this->credentials,
        'Content-Type: application/json'
    ));

    if ($requestInfo['http_code'] >= 400) {

      // Request failed

      if (isset($decodedResults->error) && isset($decodedResults->error_description))
        throw new ClientException($decodedResults->error_description);
      else
        throw new ClientException($this->getErrorMessage($decodedResults, $requestInfo, 'token', 'POST'));

    }

    if (!isset($decodedResults->access_token)) {
      throw new ClientException('Authentication failed');
    } else {

      // Authentication succeeded
      // Got a valid token

      $this->accessToken = $decodedResults->access_token;
      $this->httpHeaders[] = 'Authorization: Bearer ' . $this->accessToken;
    }
  }

  /**
   * Verifies if client is authenticated to the Web Service.
   *
   * @return bool true if client is authenticated, false otherwise
   */
  public function isAuthenticated() {
    return !empty($this->accessToken);
  }

  /**
   * Executes a GET request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $endPoint The web service end point to reach with query parameters
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function get($endPoint, $httpHeaders = array(), $curlOptions = array()) {
    $method = 'get';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a POST request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $endPoint The web service end point to reach with query parameters
   * @param String|Array $fields The data to post. Pass an array to make an http form post.
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl option to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function post($endPoint, $fields = array(), $httpHeaders = array(), $curlOptions = array()) {
    $method = 'post';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a PUT request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $endPoint The web service end point to reach with query parameters
   * @param String|Array $data The data to post
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function put($endPoint, $data = '', $httpHeaders = array(), $curlOptions = array()) {
    $method = 'put';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a DELETE request.
   *
   * @param String $endPoint The web service end point to reach with query parameters
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl handle
   * @return StdClass The response from curl if any
   */
  public function delete($endPoint, $httpHeaders = array(), $curlOptions = array()) {
    $method = 'delete';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Interprets response results to get a human readable error message.
   *
   * @param StdClass $results Web service response with an eventually error property
   * @param Array $requestInfo Information about the request with an "http_code" property
   * @param String $endPoint The requested end point without the base URL
   * @param String $method The HTTP method used to request the end point
   * @return String The error message
   */
  protected function getErrorMessage($results, $requestInfo, $endPoint, $method) {
    $method = strtoupper($method);

    if ($requestInfo['http_code'] === 403)
      return "You don't have the authorization to access the endpoint \"$method $endPoint\"";
    else if ($requestInfo['http_code'] === 401)
      return 'Authentication failed, verify your credentials';
    else if ($requestInfo['http_code'] === 404)
      return "Resource $endPoint not found";
    else if (isset($results->error)) {
        $error = $results->error;
        $message = !empty($error->message) ? $error->message : '';
        return "Error: $message (code={$error->code}, module={$error->module})";
    } else
      return "Unkown error (http_code={$requestInfo['http_code']})";
  }

  /**
   * Executes a REST request after making sure the client is authenticated.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed and request is retried.
   *
   * @param String $method The HTTP method to use (either get, post, delete or put)
   * @param Array The list of arguments to pass to get / post / delete / put method
   * @throws ClientException Exception thrown if request failed (HTTP code greater than or equal to 400)
   */
  protected function executeRequest($method, $arguments) {

    // Client is not authenticated
    // Authenticate
    if (!$this->isAuthenticated())
      $this->authenticate();

    // Prefix end point by the base url
    $endpoint = ltrim($arguments[0], '/');
    $arguments[0] = "{$this->baseUrl}/$endpoint";

    // Execute web service call
    list($decodedResults, $requestInfo) = call_user_func_array(array($this, "parent::$method"), $arguments);

    // Request done (meaning that transfer worked)

    if ($requestInfo['http_code'] >= 400) {
      if (isset($decodedResults->error) &&
         isset($decodedResults->error_description) &&
         ($decodedResults->error_description === 'Token not found or expired' ||
         $decodedResults->error_description === 'Token already expired')) {

        // Token not found or expired
        // Try to get a new access token

        $this->accessToken = null;

        // Get a new access token
        $this->authenticate();

        // Now that we have a new token, try the request again
        list($decodedResults, $requestInfo) = call_user_func_array(array($this, "parent::$method"), $arguments);

        // Still in error, throw an exception
        if (isset($decodedResults->error) && isset($decodedResults->error_description))
          throw new ClientException($decodedResults->error_description);
      }

      throw new ClientException($this->getErrorMessage($decodedResults, $requestInfo, $endpoint, $method));
    }

    return $decodedResults;
  }

}
