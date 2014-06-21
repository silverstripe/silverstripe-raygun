<?php
require_once BASE_PATH . '/vendor/autoload.php';
require_once THIRDPARTY_PATH . '/Zend/Log/Writer/Abstract.php';

class RaygunLogWriter extends Zend_Log_Writer_Abstract {

	/**
	 * @config
	 * @var string The API Key for your application, given on the Raygun 'Application Settings' page
	 */
	private static $api_key;

	/**
	 * @var String
	 */
	protected $apiKey;

	/**
	 * @var \Raygun4php\RaygunClient
	 */
	protected $client;

	/**
	 * @param String $apiKey
	 */
	function __construct($apiKey) {
		$this->apiKey = $apiKey;
	}

	function _write($message) {
		// keep track of the current user (if available) so we can identify it in Raygun
		if(Member::currentUserID()) {
			$this->getClient()->SetUser(Member::currentUser()->Email);
		}

		// Reverse-engineer the SilverStripe-repackaged exception
		if(preg_match('/^Uncaught ([A-Za-z0-9_]+):(.*)$/', $message['message']['errstr'], $matches)
				&& ($matches[1] == 'Exception' || is_subclass_of($matches[1], 'Exception'))) {

			$message['message']['errstr'] = $matches[1] . ': ' . $matches[2];
			$ex = new ReportedException($message['message']);
			$this->exception_handler($ex);

		// Regular error handling
		} else {
			// errno param can't be empty for Raygun, as it uses \ErrorException to create the error
			if(empty($message['message']['errno'])) {
				$message['message']['errno'] = 0;
			}

			$this->error_handler($message['message']['errno'], $message['message']['errstr'], $message['message']['errfile'], $message['message']['errline'], array($message['priorityName']));
		}
	}

	public static function factory($config) {
		return Injector::inst()->create('RaygunLogWriter', $config['app_key']);
	}

	function exception_handler($exception) {
		$this->getClient()->SendException($exception);
	}

	function error_handler($errno, $errstr, $errfile, $errline, $tags ) {
		if($errno === '') $errno = 0; // compat with ErrorException
		$this->getClient()->SendError($errno, $errstr, $errfile, $errline, $tags);
	}

	function shutdown_function() {
		$error = error_get_last();
		if($error && ($error['type'] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_PARSE)) != 0) {
			$this->error_handler($error['type'], $error['message'], $error['file'], $error['line'], null);
		}
	}

	/**
	 * @return \Raygun4php\RaygunClient
	 */
	function getClient() {
		if(!$this->client) {
			$this->client = new \Raygun4php\RaygunClient($this->apiKey);
		}

		return $this->client;
	}

	/**
	 * @param \Raygun4php\RaygunClient
	 */
	function setClient($client) {
		$this->client = $client;
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
