<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Page\PageRepositoryGetPageOverlayHookInterface;

class NoHttps implements PageRepositoryGetPageOverlayHookInterface {

	public function getPageOverlay_preProcess(&$pageInput, &$lUid, PageRepository $parent) {
		if (!empty($pageInput['url_scheme'])) {
			// reset to automatic
			$pageInput['url_scheme'] = 0;
		}
	}
}
