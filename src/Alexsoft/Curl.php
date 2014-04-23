<?php

/**
 * Neat and tidy cURL wrapper for PHP
 *
 * @package Curl
 * @author  Alex Plekhanov
 * @link    https://github.com/alexsoft/curl
 * @license MIT
 * @version 0.4.0-dev
 */

namespace Alexsoft;

class Curl {
	const VERSION = '0.4.0-dev';

	const GET = 'GET';
	const POST = 'POST';
	const HEAD = 'HEAD';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const OPTIONS = 'OPTIONS';

	/**
	 * cURL handle
	 * @var resource
	 */
	protected $_resource;

	/**
	 * Response string from curl_exec
	 * @var string
	 */
	protected $_response;

	/**
	 * URL to query
	 * @var string
	 */
	protected $_url;

	/**
	 * HTTP Verb
	 * @var string
	 */
	protected $_method;

    /**
     * Response header size
     * @var string
     */
    protected $_headerSize;

	/**
	 * Key => value of data to send
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Key => value of headers to send
	 * @var array
	 */
	protected $_headers = array();

	/**
	 * Key => value of cookies to send
	 * @var array
	 */
	protected $_cookies = array();

	/**
	 * User agent for query
	 * @var string
	 */
	protected $_userAgent = 'alexsoft/curl';

	/**
	 * Array of available HTTP Verbs
	 * @var array
	 */
	protected  $_availableMethods = array();

	/**
	 * @param $url string URL for query
	 */
	public function __construct($url) {
		$this->_availableMethods = array(static::GET, static::POST, static::HEAD, static::PUT, static::DELETE, static::OPTIONS);
		$this->_url = $url;
	}

	/**
	 * Add data for sending
	 * @param array $data
	 * @return $this
	 */
	public function addData(array $data) {
		$this->_data = array_merge(
			$this->_data,
			$data
		);
		return $this;
	}

	/**
	 * Add headers for sending
	 * @param array $headers
	 * @return $this
	 */
	public function addHeaders(array $headers) {
		$this->_headers = array_merge(
			$this->_headers,
			$headers
		);
		return $this;
	}

	/**
	 * Add cookies for sending
	 * @param array $cookies
	 * @return $this
	 */
	public function addCookies(array $cookies) {
		$this->_cookies = array_merge(
			$this->_cookies,
			$cookies
		);
		return $this;
	}

	/**
	 * Methods which can be called:
	 * get(), post(), head(), put(), delete(), options()
	 * @param $name
	 * @param $arguments
	 * @return array|NULL
	 * @throws \Exception
	 */
	function __call($name, $arguments)
	{
		if (in_array(mb_strtoupper($name), $this->_availableMethods)) {
			return $this->_request(mb_strtoupper($name));
		} else {
			throw new \Exception('Method ' . mb_strtoupper($name) . ' is not supported');
		}
	}

	/**
	 * @param $method string method of query
	 * @return array|NULL
	 */
	protected function _request($method) {
		$this->_resource = curl_init();
		$this->_method = $method;
		$this->_prepareRequest();
		$this->_response = curl_exec($this->_resource);
		curl_close($this->_resource);
        $this->_headerSize = curl_getinfo($this->_resource, CURLINFO_HEADER_SIZE);
		return $this->_parseResponse();
	}

	/**
	 * Method which sets all the data, headers, cookies
	 * and other options for the query
	 */
	protected function _prepareRequest() {
		// Set data for GET queries
		if ($this->_method === static::GET && !empty($this->_data)) {
			$url = trim($this->_url, '/') . '?';
			$url .= http_build_query($this->_data);
		} else {
			$url = $this->_url;
		}

		// Set options
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_POST => $this->_method === static::POST,
			CURLOPT_HEADER => TRUE,
			CURLOPT_NOBODY => $this->_method === static::HEAD,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => $this->_userAgent,
			CURLOPT_SSL_VERIFYPEER => FALSE
		);

		if (!in_array($this->_method, array(static::GET, static::HEAD, static::POST))) {
			$options[CURLOPT_CUSTOMREQUEST] = $this->_method;
		}

		// Set data for not GET queries
		if (!empty($this->_data) && $this->_method !== static::GET) {
			$options[CURLOPT_POSTFIELDS] = http_build_query($this->_data);
		}

		// Set headers if needed
		if (!empty($this->_headers)) {
			$headersToSend = array();
			foreach ($this->_headers as $key => $value) {
				$headersToSend[] = "{$key}: {$value}";
			}
			$options[CURLOPT_HTTPHEADER] = $headersToSend;
		}

		// Set cookies if needed
		if (!empty($this->_cookies)) {
			$cookiesToSend = array();
			foreach ($this->_cookies as $key => $value) {
				$cookiesToSend[] = "{$key}={$value}";
			}
			$options[CURLOPT_COOKIE] = implode('; ', $cookiesToSend);
		}

		curl_setopt_array($this->_resource, $options);
	}

	/**
	 * Method which parses cURL response
	 * @return array|NULL
	 */
	protected function _parseResponse() {
		if (isset($this->_response)) {
            if(isset($this->_headerSize)) {
                $responseParts['headersString'] = trim(substr($this->_response, 0, $this->_headerSize));
                $responseParts['body'] = substr($this->_response, $this->_headerSize);
            } else {
                list($responseParts['headersString'], $responseParts['body']) = explode("\r\n\r\n", $this->_response, 2);
            }
			$responseParts['body'] = htmlspecialchars($responseParts['body']);
			$headers = explode("\r\n", $responseParts['headersString']);
			$cookies = array();
			$first = TRUE;
            $continue = FALSE;
			if (preg_match_all( '/Set-Cookie: (.*?)=(.*?)(\n|;)/i', $responseParts['headersString'], $matches)) {
				if (!empty($matches)) {
					foreach ($matches[1] as $key => $value) {
						$cookies[$value] = $matches[2][$key];
					}
					$responseParts['cookies'] = $cookies;
				}
			}
			foreach ($headers as $header) {
                if ($first || $continue) {
                    if($header != '') {
                        list($responseParts['protocol'], $responseParts['statusCode'], $responseParts['statusMessage']) = explode(' ', $header);
                        $continue = ($responseParts['statusMessage'] == 'Continue');
                    }
                    $first = FALSE;
                } else {
					$tmp = (explode(': ', $header));
					if ($tmp[0] === 'Set-Cookie') {
						continue;
					} else {
						$responseParts['headersArray'][$tmp[0]] = $tmp[1];
					}
				}
			}
			return $responseParts;
		} else {
			return NULL;
		}
	}
}