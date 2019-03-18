<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class BaseURL {

	public function setLocalBaseUrl(array &$params, TypoScriptFrontendController $TSFE) {
		if (empty($_SERVER['4WD_CONFIG'])) {
			return;
		}

		if (!isset($params['config'], $params['config']['baseURL'])) {
			return;
		}

		$host = preg_replace('/\.[^.]+$/', '', $_SERVER['SERVER_NAME']);
		if (!is_link($_SERVER['4WD_CONFIG'] . '/domains/' . $host)) {
			return;
		}

		// the current domain has a symlink with our configuration. Update the baseURL

		$params['config']['baseURL'] = 'http://' . $_SERVER['SERVER_NAME'] . '/';
	}
}