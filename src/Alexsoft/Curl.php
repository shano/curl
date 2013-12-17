<?php
namespace Alexsoft;

class Curl {
	const VERSION = '0.1.2';

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const OPTIONS = 'OPTIONS';

	/**
	 * cURL descriptor
	 * @var resource
	 */
	protected $_request;

	/**
	 * String of cURL response
	 * @var string
	 */
	protected $_response;

	/**
	 * User agent for requests
	 * @var string
	 */
	protected $_userAgent;

	public function __construct() {
		$this->setUserAgent('alexsoft/curl v' . static::VERSION);
	}

	public function setUserAgent($userAgent) {
		$this->_userAgent = $userAgent;
		return $this;
	}

	public function get($url, $data = NULL, $headers = NULL, $cookies = NULL) {
		return $this->request(
			$url,
			$data,
			static::GET,
			$headers,
			$cookies
		);
	}

	public function head($url, $data = NULL, $headers = NULL, $cookies = NULL) {
		return $this->request(
			$url,
			$data,
			static::HEAD,
			$headers,
			$cookies
		);
	}

	public function post($url, $data = NULL, $headers = NULL, $cookies = NULL) {
		return $this->request(
			$url,
			$data,
			static::POST,
			$headers,
			$cookies
		);
	}

	public function request($url, $data = NULL, $method, $headers = NULL, $cookies = NULL) {
		// Set data for GET queries
		if ($method === static::GET && isset($data)) {
			$url = trim($url, '/') . '?';
			$url .= is_array($data) ? http_build_query($data) : $data;
		}

		$this->_request = curl_init();

		// Set options
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_POST => $method === static::POST,
			CURLOPT_HEADER => TRUE,
			CURLOPT_NOBODY => $method === static::HEAD,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => $this->_userAgent,
			CURLOPT_SSL_VERIFYPEER => FALSE
		);

		if (!in_array($method, array(static::GET, static::HEAD, static::POST))) {
			$options[CURLOPT_CUSTOMREQUEST] = $method;
		}

		// Set data for not GET queries
		if (isset($data) && $method !== static::GET) {
			$options[CURLOPT_POSTFIELDS]
				= is_array($data) ? http_build_query($data) : $data;
		}

		// Set headers if needed
		if (isset($headers)) {
			$headersToSend = array();
			foreach ($headers as $key => $value) {
				$headersToSend[] = "{$key}: {$value}";
			}
			$options[CURLOPT_HEADER] = $headersToSend;
		}

		// Set cookies if needed
		if (isset($cookies)) {
			$cookiesToSend = array();
			foreach ($cookies as $key => $value) {
				$cookiesToSend[] = "{$key}={$value}";
			}
			$options[CURLOPT_COOKIE] = implode('; ', $cookiesToSend);
		}

		curl_setopt_array($this->_request, $options);
		$this->_response = curl_exec($this->_request);
		curl_close($this->_request);
		return $this->_parseResponse();
	}

	/**
	 * Parses the response string
	 * @return array|NULL
	 */
	protected function _parseResponse() {
		if (isset($this->_response)) {
			list($responseParts['headersString'], $responseParts['body']) = explode("\r\n\r\n", $this->_response, 2);
			$responseParts['body'] = htmlspecialchars($responseParts['body']);
			$headers = explode("\r\n", $responseParts['headersString']);
			$cookies = array();
			$first = TRUE;
			foreach ($headers as $header) {
				if ($first) {
					list($responseParts['protocol'], $responseParts['statusCode'], $responseParts['statusMessage']) = explode(' ', $header);
					$first = FALSE;
				} else {
					$tmp = (explode(': ', $header));
					if ($tmp[0] === 'Set-Cookie') {
						$c = substr($tmp[1], 0, strpos($tmp[1], ';'));
						$c = explode('=', $c, 2);
						$responseParts['cookies'][$c[0]] = $c[1];
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