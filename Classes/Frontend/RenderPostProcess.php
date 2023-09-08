<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend;

use TYPO3\CMS\Core\Http\ApplicationType;

class RenderPostProcess {

	/**
	 * shorten urls for js and css files. Needs rule in .htaccess
	 */
	public function renderPostProcess(array &$params): void {
		if (!($GLOBALS['TYPO3_REQUEST'] ?? null)) {
			return;
		}
		if (!ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
			return;
		}

		$search  = [' type="text/javascript"', 'async="async"', 'defer="defer"'];
		$replace = ['', 'async', 'defer'];
		$params['jsLibs']        = str_replace($search, $replace, $params['jsLibs']);
		$params['jsFiles']       = str_replace($search, $replace, $params['jsFiles']);
		$params['jsFooterFiles'] = str_replace($search, $replace, $params['jsFooterFiles']);
		$params['jsFooterLibs']  = str_replace($search, $replace, $params['jsFooterLibs']);

		$search  = [' type="text/css"', ' media="all"'];
		$replace = ['', ''];
		$params['cssFiles'] = str_replace($search, $replace, $params['cssFiles']);
	}

}
