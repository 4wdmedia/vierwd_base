<?php

namespace Vierwd\VierwdBase\Error;

/**
 * error handler which uses Growl to show errors during developement.
 * It also logs fatal errors
 *
 * Add with $TYPO3_CONF_VARS['SYS']['errorHandler'] = 'Vierwd\\VierwdBase\\Error\\ErrorHandler';
 *
 * @note growl must be accessible in your path
 */
class ErrorHandler extends \TYPO3\CMS\Core\Error\ErrorHandler {

	public function __construct($errorHandlerErrors) {
		parent::__construct($errorHandlerErrors);

		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error['type'] & E_ERROR) {
				// Convert type to E_USER_ERROR, because TYPO3 doesn't know about E_ERROR
				$this->handleError(E_USER_ERROR, $error['message'], $error['file'], $error['line']);
			}
		});
	}

	protected function writeLog($logMessage, $severity) {
		static $messages = array();

		parent::writeLog($logMessage, $severity);

		if (empty($_SERVER['4WD_CONFIG']) || in_array($logMessage, $messages)) {
			return;
		}
		$messages[] = $logMessage;

		// check if it's possible to show notification with terminal-notifier
		// $terminalNotifier = `which terminal-notifier`;
		// if ($terminalNotifier) {
		// 	shell_exec('terminal-notifier -title Error -sender com.google.chrome -message ' . escapeshellarg($logMessage));
		// 	return;
		// }

		// check if it's possible to show notification with growl
		$growl = trim(`which growl`);
		if ($growl) {
			shell_exec($growl . ' -nosticky '.escapeshellarg($logMessage));
		}
	}
}