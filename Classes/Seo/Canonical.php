<?php

namespace Vierwd\VierwdBase\Seo;

use TYPO3\CMS\Core\Utility\ArrayUtility;
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
			$query = GeneralUtility::_GET();
			$removeParameters = (array)$GLOBALS['TSFE']->config['config']['tx_vierwd.']['removeCanonicalUrlParameters.'];
			$removeParameters = array_filter($removeParameters);
			$queryChanged = false;
			foreach ($removeParameters as $parameter) {
				if (ArrayUtility::isValidPath($query, $parameter, '|')) {
					$query = ArrayUtility::removeByPath($query, $parameter, '|');
					$queryChanged = true;
				}
			}

			$query2 = $query;
			foreach ($query2 as $parameter => $value) {
				if (is_array($value) && !$value) {
					// empty array -> remove
					unset($query[$parameter]);
					$queryChanged = true;
				}
			}

			if ($queryChanged) {
				unset($query['cHash']);
			}

			if ($queryChanged) {
				// regenerate URL

				// First: remove L and id parameter
				$query = array_diff_key($query, ['L' => 0, 'id' => 0]);
				if (!$query) {
					// there are more parameters beside L and id. Regenerate including cHash
					$url = $GLOBALS['TSFE']->id;
				} else {
					// only L and id left. generate without cHash
					$url = $GLOBALS['TSFE']->cObj->typolink_url([
						'parameter' => $GLOBALS['TSFE']->id,
						'useCacheHash' => true,
						'additionalParams' => GeneralUtility::implodeArrayForUrl('', $query),
					]);
				}
			} else {
				$url = substr(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
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
