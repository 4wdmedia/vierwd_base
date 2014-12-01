<?php

namespace Vierwd\VierwdBase\Hooks;

class NoHttps implements \TYPO3\CMS\Frontend\Page\PageRepositoryGetPageOverlayHookInterface {

	public function getPageOverlay_preProcess(&$pageInput, &$lUid, \TYPO3\CMS\Frontend\Page\PageRepository $parent) {
		if (!empty($pageInput['url_scheme'])) {
			// reset to automatic
			$pageInput['url_scheme'] = 0;
		}
	}
}