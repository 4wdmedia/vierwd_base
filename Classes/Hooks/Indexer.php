<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Indexer {

	public function hook_indexContent(&$pObj) {
		$contentBefore = $pObj->content;

		if ($pObj->config && $pObj->config['config']['spamProtectEmailAddresses_atSubst']) {
			$pObj->content = str_replace($pObj->config['config']['spamProtectEmailAddresses_atSubst'], '@', $pObj->content);
		}

		$_procObj = GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
		$_procObj->hook_indexContent($pObj);

		$pObj->content = $contentBefore;
	}
}
