<?php

namespace Vierwd\VierwdBase\Hooks;

class BaseURL {

	public function setLocalBaseUrl(array &$params, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $TSFE) {
		if (empty($_SERVER['4WD_CONFIG'])) {
			return;
		}

		if (!isset($params['config'], $params['config']['baseURL'])) {
			return;
		}

		$urlParts = parse_url($params['config']['baseURL']);
		if (empty($urlParts['host'])) {
			return;
		}

		$host = $urlParts['host'];
		if (!is_link($_SERVER['4WD_CONFIG'] . '/domains/' . $host)) {
			return;
		}

		// the current domain has a symlink with our configuration. Update the baseURL

		if (substr($_SERVER['SERVER_NAME'], 0, strlen($host)) === $host) {
			// just use the server name
			$urlParts['host'] = $_SERVER['SERVER_NAME'];
		} else {
			$machineName = $_SERVER['USER'];
			$urlParts['host'] = $host . '.' . $machineName;
		}

		// local development is always http
		$urlParts['scheme'] = 'http';

		$params['config']['baseURL'] = \TYPO3\CMS\Core\Utility\HttpUtility::buildUrl($urlParts);
	}
}