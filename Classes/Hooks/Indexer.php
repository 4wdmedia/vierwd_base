<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Indexer {

	public function hook_indexContent(&$pObj) {
		$contentBefore = $pObj->content;

		if ($pObj->config && $pObj->config['config']['spamProtectEmailAddresses_atSubst']) {
			$pObj->content = str_replace($pObj->config['config']['spamProtectEmailAddresses_atSubst'], '@', $pObj->content);
		}

		$oldLanguageContent = $pObj->sys_language_content;
		if ($pObj->sys_language_uid != $pObj->sys_language_content) {
			// Current page shows fallback content of another language.
			// Normally this would not be indexed. But if this is just another locale (de_CH and fallback to de_DE)
			// we want this to be indexed
			$pObj->sys_language_content = $pObj->sys_language_uid;
		}

		$_procObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
		$_procObj->hook_indexContent($pObj);

		$pObj->content = $contentBefore;
		$pObj->sys_language_content = $oldLanguageContent;
	}
}
