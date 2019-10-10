<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class BrowserSync {
	public function enable($params, TypoScriptFrontendController $TSFE) {
		if (isset($TSFE->config['config']['tx_vierwd.'], $TSFE->config['config']['tx_vierwd.']['browserSync']) && !$TSFE->config['config.']['tx_vierwd.']['browserSync']) {
			return;
		}

		// check if the port 3000 is open
		// https://apple.stackexchange.com/questions/81140/why-is-lsof-on-os-x-so-ridiculously-slow
		// lsof -i tcp:3000 -P -O -l -n | grep "^node.*3000"
		if (!trim(`netstat -vanp tcp | grep 3000`)) {
			return;
		}

		$browserSync = '<script async src="http' . (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . ':3000/browser-sync/browser-sync-client.js"></script>';
		$TSFE->content = preg_replace('#</body>#', $browserSync . "\n</body>", $TSFE->content, 1, $count);
	}
}
