<?php

namespace Vierwd\VierwdBase\Seo;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Canonical {

	public function getTag($content, array $params = []) {
		if (!empty($GLOBALS['TSFE']->no_cache)) {
			return '';
		}

		if ($GLOBALS['TSFE']->cHash) {
			$url = $GLOBALS['TSFE']->anchorPrefix;
		} else {
			$url = $GLOBALS['TSFE']->id;
		}

		if ($url) {
			$conf = [
				'parameter' => $url,
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