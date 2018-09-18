<?php

namespace Openveo\Client;

use Openveo\Exception\RESTClientException;

/**
 * Defines a REST Curl client with common GET, POST, PUT, DELETE HTTP methods.
 */
abstract class RestClient {

  /**
   * Curl handle.
   * @var CURL handle
   */
  public $handle;

  /**
   * List of Curl options for all requests.
   * @var Array
   */
  public $curlOptions;

  /**
   * List of headers sent with all requests.
   * @var Array
   */
  protected $httpHeaders;

  /**
   * Builds a new REST client.
   *
   * @param String $certificate Path to the Web Service server trusted certificate file
   */
  protected function __construct($certificate = null) {
    $curlCookieJar = tempnam(sys_get_temp_dir(), 'cookies_');

    $this->curlOptions = array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_COOKIESESSION => false,
      CURLOPT_COOKIEJAR => $curlCookieJar,
      CURLOPT_COOKIEFILE => $curlCookieJar,
      CURLOPT_HEADER => false,
      CURLOPT_CONNECTTIMEOUT => 1,
      CURLOPT_TIMEOUT => 10
    );

    if (!empty($certificate)) $this->curlOptions[CURLOPT_CAINFO] = $certificate;

    $this->httpHeaders = array(
      'Accept: application/json'
    );
  }

  /**
   * Removes CURL cookie file when destructing the client.
   */
  function __destruct() {
    unset($this->handle);
    @unlink($this->curlOptions[CURLOPT_COOKIEJAR]);
  }

  /**
   * Executes the curl request.
   *
   * @param String $url The url to call
   * @param Array $httpHeaders Extra HTTP headers
   * @param Array $curlOptions Extra curl options
   * @return StdClass Response from curl
   * @throws RestClientException
   */
  protected function execCurl($url, $httpHeaders = array(), $curlOptions = array()) {
    $responseObject = null;

    // Initialize curl session
    $this->handle = curl_init();

    // Merge headers with extra headers and options with extra options
    $httpHeaders = $httpHeaders + $this->httpHeaders;
    $curlOptions = $curlOptions + $this->curlOptions;

    // Set request headers and url
    $curlOptions[CURLOPT_HTTPHEADER] = $httpHeaders;
    $curlOptions[CURLOPT_URL] = $url;

    // Set curl options
    if (!curl_setopt_array($this->handle, $curlOptions))
      throw new RestClientException('Error setting cURL request options');

    if ($this->handle) {
      $responseObject = curl_exec($this->handle);
      $responseObject = json_decode($responseObject);

      $requestInfo = curl_getinfo($this->handle);
      $error = curl_error($this->handle);
      curl_close($this->handle);

      if (empty($requestInfo['http_code']))
        throw new RestClientException('Can`t reach the server (' . $error . ')');
    }

    return $responseObject;
  }

  /**
   * Performs a GET request.
   *
   * @param String $url The url to call
   * @param Array $httpHeaders Extra HTTP headers
   * @param Array $curlOptions Extra curl options
   * @return StdClass The response from curl if any
   */
  public function get($url, $httpHeaders = array(), $curlOptions = array()) {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = 'GET';
    return $this->execCurl($url, $httpHeaders, $curlOptions);
  }

  /**
   * Performs a POST request.
   *
   * @param String $url The url to call
   * @param String|Array $fields The data to post. Pass an array to make an http form post.
   * @param Array $httpHeaders Extra HTTP headers
   * @param Array $curlOptions Extra curl options
   */
  public function post($url, $fields = array(), $httpHeaders = array(), $curlOptions = array()) {
    $curlOptions[CURLOPT_POST] = true;
    $curlOptions[CURLOPT_POSTFIELDS] = $fields;

    if (is_array($fields)) {
      $curlOptions[CURLOPT_HTTPHEADER] = array(
          'Content-Type: multipart/form-data'
      );
    }

    return $this->execCurl($url, $httpHeaders, $curlOptions);
  }

  /**
   * Performs a PUT request.
   *
   * @param String $url The url to call
   * @param String|Array $data The data to post
   * @param Array $httpHeaders Extra HTTP headers
   * @param Array $curlOptions Extra curl options
   * @return StdClass The response from curl if any
   */
  public function put($url, $data = '', $httpHeaders = array(), $curlOptions = array()) {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
    $curlOptions[CURLOPT_POSTFIELDS] = $data;
    return $this->execCurl($url, $httpHeaders, $curlOptions);
  }

  /**
   * Performs a DELETE request.
   *
   * @param String $url The url to call
   * @param Array $httpHeaders Extra HTTP headers
   * @param Array $curlOptions Extra curl options
   * @return StdClass The response from curl if any
   */
  public function delete($url, $httpHeaders = array(), $curlOptions = array()) {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    return $this->execCurl($url, $httpHeaders, $curlOptions);
  }

}
