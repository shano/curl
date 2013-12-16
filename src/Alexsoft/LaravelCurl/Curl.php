<?php
namespace Alexsoft\LaravelCurl;

class Curl {
	const VERSION = '0.1.1';

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const CONNECT = 'CONNECT';
	const OPTION = 'OPTION';
	const PATCH = 'PATCH';

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
		$this->setUserAgent('alexsoft/laravel-curl');
	}

	public function setUserAgent($userAgent) {
		$this->_userAgent = $userAgent;
		return $this;
	}

	public function get($url, $data = NULL, $headers = NULL, $cookie = NULL) {
		return $this->request(
			$url,
			$data,
			static::GET,
			$headers,
			$cookie
		);
	}

	public function head($url, $data = NULL, $headers = NULL, $cookie = NULL) {
		return $this->request(
			$url,
			$data,
			static::HEAD,
			$headers,
			$cookie
		);
	}

	public function request($url, $data = NULL, $method, $headers = NULL, $cookie = NULL) {
		if ($method === static::GET && isset($data)) {
			$url = trim($url, '/');
			$url .= is_array($data) ? http_build_query($data) : $data;
		}

		$this->_request = curl_init();

		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HEADER => TRUE,
			CURLOPT_NOBODY => $method === static::HEAD,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => $this->_userAgent,
			CURLOPT_SSL_VERIFYPEER => FALSE
		);

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
						$c = explode('=', $tmp[1]);
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