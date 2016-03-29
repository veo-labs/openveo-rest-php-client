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
   * @param String $id The client id
   * @param String $secret The client secret
   * @param String $host The OpenVeo Web Service host without the protocol part (e.g 127.0.0.1)
   * @param String|Integer $port The Web Service port if any
   */
  public function __construct($id, $secret, $host, $port = null) {
    if (empty($host) || empty($id) || empty($secret))
      throw new ClientException('Host, client id and client secret are required to create an Openveo Client');

    $this->credentials = base64_encode($id . ':' . $secret);
    $this->baseUrl = 'http://' . trim($host, '/');

    // Add port if defined
    if (!empty($port))
      $this->baseUrl .= ':' . $port;

    parent::__construct();
  }

  /**
   * Authenticates the client to the Web Service.
   *
   * @throws ClientException
   */
  protected function authenticate() {
    $url = $this->baseUrl . '/token';
    $results = parent::post($url, json_encode(array(
        'grant_type' => 'client_credentials'
        )), array(
        'Authorization: Basic ' . $this->credentials,
        'Content-Type: application/json'
    ));

    $decodedResults = $results;

    // Authentication failed
    if(isset($decodedResults->error) && isset($decodedResults->error_description))
      throw new ClientException($decodedResults->error_description);

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
  protected function isAuthenticated() {
    return !empty($this->accessToken);
  }

  /**
   * Executes a GET request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $url The url to make the call to
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function get($url, $httpHeaders = array(), $curlOptions = array()) {
    $method = 'get';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a POST request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $url The url to make the call to
   * @param String|Array $fields The data to post. Pass an array to make an http form post.
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl option to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function post($url, $fields = array(), $httpHeaders = array(), $curlOptions = array()) {
    $method = 'post';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a PUT request.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed.
   *
   * @param String $url The url to make the call to
   * @param String|Array $data The data to post
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl request
   * @return StdClass The response from curl if any
   */
  public function put($url, $data = '', $httpHeaders = array(), $curlOptions = array()) {
    $method = 'put';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a DELETE request.
   *
   * @param String $url The url to make the call to
   * @param Array $httpHeaders Extra headers to pass to curl request
   * @param Array $curlOptions Extra curl options to pass to curl handle
   * @return StdClass The response from curl if any
   */
  public function delete($url, $httpHeaders = array(), $curlOptions = array()) {
    $method = 'delete';
    return $this->executeRequest($method, func_get_args());
  }

  /**
   * Executes a REST request after making sure the client is authenticated.
   *
   * If client is not authenticated or access token has expired, a new authentication is automatically
   * performed and request is retried.
   *
   * @param String $method The HTTP method to use (either get, post, delete or put)
   * @param Array The list of arguments to pass to get / post / delete / put method
   * @throws ClientException
   */
  protected function executeRequest($method, $arguments){

    // Client is authenticated
    // Authenticate
    if (!$this->isAuthenticated())
      $this->authenticate();

    // Execute web service call
    $results = call_user_func_array(array($this, 'parent::' . $method), $arguments);
    $decodedResults = $results;

    // Token not found or expired
    // Try to get a new access token
    if (isset($decodedResults->error) &&
       isset($decodedResults->error_description) &&
       ($decodedResults->error_description === 'Token not found or expired' ||
       $decodedResults->error_description === 'Token already expired')) {
      $this->accessToken = null;

      // Get a new access token
      $this->authenticate();

      // Execute web service call
      $results = call_user_func_array(array($this, 'parent::' . $method), $arguments);
      $decodedResults = $results;

      // Still in error, throw an exception
      if (isset($decodedResults->error) && isset($decodedResults->error_description))
        throw new ClientException($decodedResults->error_description);
    }

    return $decodedResults;
  }

}
