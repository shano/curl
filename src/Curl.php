<?php

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
}