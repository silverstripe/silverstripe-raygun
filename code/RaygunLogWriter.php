<?php

require_once '../vendor/autoload.php';
require_once '../framework/thirdparty/Zend/Log/Writer/Abstract.php';

class RaygunLogWriter extends Zend_Log_Writer_Abstract {
	protected $client;
	protected $postException;

	function __construct($appKey) {
		$this->client = new \Raygun4php\RaygunClient($appKey);
	}

	function _write($message) {
		$this->error_handler($message['message']['errno'], $message['message']['errstr'], $message['message']['errfile'], $message['message']['errline']);
	}

	static function factory($config) {
		return new RaygunLogWriter($config['app_key']);
	}

	function exception_handler($exception) {
		$this->client->SendException($exception);

		if($callback = $this->postException) {
			$callback($exception);
		}
	}

	function setPostExceptionCallback($postException) {
		$this->postException = $postException;
	}

	function error_handler($errno, $errstr, $errfile, $errline ) {
		$this->client->SendError($errno, $errstr, $errfile, $errline);
	}
}