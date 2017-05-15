<?php

namespace Vierwd\VierwdBase\Seo;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Canonical {

	public function getTag($content, array $params = []) {
		if (!empty($GLOBALS['TSFE']->no_cache) || !empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			return '';
		}

		$url = $GLOBALS['TSFE']->id;
		$parameters = '';
		if ($GLOBALS['TSFE']->cHash) {
			$GET = $_GET;
			unset($GET['cHash'], $GET['id']);
			$parameters = GeneralUtility::implodeArrayForUrl('', $GET);
		}

		if ($url) {
			$conf = [
				'parameter' => $url,
				'additionalParams' => $parameters,
				'useCacheHash' => true,
				'forceAbsoluteUrl' => true,
				'forceAbsoluteUrl.' => [
					'scheme' => 'http' . (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 's' : ''),
				],
			];
			$url = $this->cObj->typolink_url($conf);
			$url = $this->cObj->stdWrap($url, $params);
		}

		return $url;
	}
}