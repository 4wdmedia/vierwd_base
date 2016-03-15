<?php

namespace Vierwd\VierwdBase\Seo;

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
			];
			$url = $this->cObj->typolink_url($conf);
			$url = $this->cObj->stdWrap($url, $params);
		}

		return $url;
	}
}