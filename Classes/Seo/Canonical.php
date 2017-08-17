<?php

namespace Vierwd\VierwdBase\Seo;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Canonical implements \TYPO3\CMS\Core\SingletonInterface {

	public function getTag($content, array $params = []) {
		if (!empty($GLOBALS['TSFE']->no_cache) || !empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			return '';
		}

		$url = self::getUrl();

		$url = $GLOBALS['TSFE']->cObj->stdWrap($url, $params);

		return $url;
	}

	static public function getUrl() {
		if (!empty($GLOBALS['TSFE']->no_cache)) {
			return '';
		}

		if ($GLOBALS['TSFE']->cHash) {
			$url = $GLOBALS['TSFE']->anchorPrefix;
			$urlData = parse_url($url);
			parse_str($urlData['query'], $query);
			if (isset($query['tab']) || isset($query['stage'])) {
				unset($query['tab'], $query['cHash']);
				if (!$query) {
					$url = $GLOBALS['TSFE']->id;
				} else {
					// recalc cHash
					$url = $GLOBALS['TSFE']->cObj->typolink_url([
						'useCacheHash' => true,
						'additionalParams' => GeneralUtility::implodeArrayForUrl('', $query),
					]);
				}
			}
		} else {
			$url = $GLOBALS['TSFE']->id;
		}

		if ($url) {
			$conf = [
				'parameter' => $url,
				'forceAbsoluteUrl' => true,
			];
			$url = $GLOBALS['TSFE']->cObj->typolink_url($conf);
		}

		return $url;
	}
}
