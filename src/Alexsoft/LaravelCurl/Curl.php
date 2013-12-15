<?php
namespace Alexsoft\LaravelCurl;

class Curl {
	const VERSION = '0.0.1';

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const CONNECT = 'CONNECT';
	const OPTION = 'OPTION';
	const PATCH = 'PATCH';

	protected $_request;
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

	public function post() {

	}

	public function request($url, $data = NULL, $method, $headers = NULL, $cookie = NULL) {
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
		$result = curl_exec($this->_request);
		$this->parseResponse($result);
		curl_close($this->_request);
	}

	public function parseResponse($result) {
		list($responseParts['headers'], $responseParts['body'])
			= explode("\r\n\r\n", $result, 2);
		// var_dump($responseParts['headers']);
		echo "<pre>";
		foreach (explode("\r\n", $responseParts['headers']) as $r) {
			var_dump(explode(" ", $r));
		}
		
		// echo "<pre>";
		// var_dump(explode(' ', $t));
		// var_dump(explode("\r\n", $responseParts['headers']));
		echo "</pre>";


		// var_dump($t);
		// var_dump(explode("\r\n", $t[0]));
	}
}