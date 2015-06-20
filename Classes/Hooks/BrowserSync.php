<?php

namespace Vierwd\VierwdBase\Hooks;

class BrowserSync {
	public function enable($params, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $TSFE) {
		if (empty($_SERVER['4WD_CONFIG'])) {
			return;
		}

		// check if the port 3000 is open
		if (!trim(`lsof -i :3000 -P | grep "^node.*3000"`)) {
			return;
		}

		$browserSync = '<script async src="http://' . $_SERVER['SERVER_NAME'] . ':3000/browser-sync/browser-sync-client.js"></script>';
		$TSFE->content = preg_replace('#</body>#', $browserSync . "\n</body>", $TSFE->content, 1, $count);
	}
}