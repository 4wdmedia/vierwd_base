<?php

namespace Vierwd\VierwdBase\Error;

/**
 * error handler which uses Growl to show errors during developement.
 *
 * Add with $TYPO3_CONF_VARS['SYS']['errorHandler'] = 'Vierwd\\VierwdBase\\Errors\\ErrorHandler';
 *
 * @note growl must be accessible in your path
 */
class ErrorHandler extends \TYPO3\CMS\Core\Error\ErrorHandler {

	protected function writeLog($logMessage, $severity) {
		static $messages = array();

		parent::writeLog($logMessage, $severity);

		if (in_array($logMessage, $messages)) {
			return;
		}
		$messages[] = $logMessage;

		// check if it's possible to show notification with terminal-notifier
		$terminalNotifier = `which terminal-notifier`;
		if ($terminalNotifier) {
			shell_exec('terminal-notifier -title Error -sender com.google.chrome -message ' . escapeshellarg($logMessage));
			return;
		}

		// check if it's possible to show notification with growl
		$growl = trim(`which growl`);
		if ($growl) {
			shell_exec($growl . ' -nosticky '.escapeshellarg($logMessage));
		}
	}
}