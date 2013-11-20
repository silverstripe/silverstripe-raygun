<?php

require_once '../vendor/autoload.php';
require_once THIRDPARTY_PATH . '/Zend/Log/Writer/Abstract.php';

class RaygunLogWriter extends Zend_Log_Writer_Abstract {
	protected $client;
	protected $postException;

	function __construct($appKey) {
		$this->client = new \Raygun4php\RaygunClient($appKey);
	}

	function _write($message) {
		// Reverse-engineer the SilverStripe-repackaged exception
		if(preg_match('/^Uncaught ([A-Za-z0-9_]+):(.*)$/', $message['message']['errstr'], $matches)
				&& ($matches[1] == 'Exception' || is_subclass_of($matches[1], 'Exception'))) {

			$message['message']['errstr'] = $matches[1] . ': ' . $matches[2];
			$ex = new ReportedException($message['message']);
			$this->exception_handler($ex);

		// Regular error handling
		} else {
			$this->error_handler($message['message']['errno'], $message['message']['errstr'], $message['message']['errfile'], $message['message']['errline']);
		}
	}

	static function factory($config) {
		return new RaygunLogWriter($config['app_key']);
	}

	function exception_handler($exception) {
		$this->client->SendException($exception);
	}

	function error_handler($errno, $errstr, $errfile, $errline ) {
		$this->client->SendError($errno, $errstr, $errfile, $errline);
	}

	function shutdown_function() {
		$error = error_get_last();
		if($error && ($error['type'] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_PARSE)) != 0) {
			$this->error_handler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
}

/**
 * Deal with SilverStripe's limited support for custom exeption handlers.
 * Can't be a real exception as then we can't override final methods.
 */
class ReportedException {
	protected $data;

	function __construct($data) {
		$this->data = $data;
	}

	function getMessage() {
		return $this->data['errstr'];
	}
	function getCode() {
		return $this->data['errno'];
	}
	function getTrace() {
		return $this->data['errcontext'];
	}
	function getFile() {
		return $this->data['errfile'];
	}
	function getLine() {
		return $this->data['errline'];
	}
}