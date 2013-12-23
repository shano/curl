<?php

/**
 * Neat and tidy cURL wrapper for PHP
 *
 * @package Curl
 * @author  Alex Plekhanov
 * @link    https://github.com/alexsoft/curl
 * @license MIT
 * @version 0.3.0-rc.1
 */

namespace Alexsoft;

class Curl {
	const VERSION = '0.3.0-rc.1';

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

	protected $_response;

	protected $_url;
	protected $_method;
	protected $_data = array();
	protected $_headers = array();
	protected $_cookies = array();
	protected $_userAgent = 'alexsoft/curl';

	public function __construct($url) {
		$this->_url = $url;
	}

	public function addData(array $data) {
		$this->_data = array_merge(
			$this->_data,
			$data
		);
		return $this;
	}

	public function addHeaders(array $headers) {
		$this->_headers = array_merge(
			$this->_headers,
			$headers
		);
		return $this;
	}

	public function addCookies(array $cookies) {
		$this->_cookies = array_merge(
			$this->_cookies,
			$cookies
		);
		return $this;
	}

	public function get() {
		return $this->_request(static::GET);
	}

	public function post() {
		return $this->_request(static::POST);
	}

	public function head() {
		return $this->_request(static::HEAD);
	}

	public function put() {
		return $this->_request(static::PUT);
	}

	public function delete() {
		return $this->_request(static::DELETE);
	}

	public function options() {
		return $this->_request(static::OPTIONS);
	}

	protected function _request($method) {
		$this->_resource = curl_init();
		$this->_method = $method;
		$this->_prepareRequest();
		$this->_response = curl_exec($this->_resource);
		curl_close($this->_resource);
		return $this->_parseResponse();
	}

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

	protected function _parseResponse() {
		if (isset($this->_response)) {
			list($responseParts['headersString'], $responseParts['body']) = explode("\r\n\r\n", $this->_response, 2);
			$responseParts['body'] = htmlspecialchars($responseParts['body']);
			$headers = explode("\r\n", $responseParts['headersString']);
			$cookies = array();
			$first = TRUE;
			if (preg_match_all( '/Set-Cookie: (.*?)=(.*?)(\n|;)/i', $responseParts['headersString'], $matches)) {
				if (!empty($matches)) {
					foreach ($matches[1] as $key => $value) {
						$cookies[$value] = $matches[2][$key];
					}
					$responseParts['cookies'] = $cookies;
				}
			}
			foreach ($headers as $header) {
				if ($first) {
					list($responseParts['protocol'], $responseParts['statusCode'], $responseParts['statusMessage']) = explode(' ', $header);
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