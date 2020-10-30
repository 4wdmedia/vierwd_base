<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Indexer {

	public function hook_indexContent(TypoScriptFrontendController &$pObj): void {
		$contentBefore = $pObj->content;

		if ($pObj->config && $pObj->config['config']['spamProtectEmailAddresses_atSubst']) {
			$pObj->content = str_replace($pObj->config['config']['spamProtectEmailAddresses_atSubst'], '@', $pObj->content);
		}

		$context = GeneralUtility::makeInstance(Context::class);
		/** @var \TYPO3\CMS\Core\Context\LanguageAspect $languageAspect */
		$languageAspect = $context->getAspect('language');

		$oldLanguageAspect = $languageAspect;
		if ($languageAspect->getId() != $languageAspect->getContentId()) {
			// Current page shows fallback content of another language.
			// Normally this would not be indexed. But if this is just another locale (de_CH and fallback to de_DE)
			// we want this to be indexed
			$indexLanguageAspect = GeneralUtility::makeInstance(LanguageAspect::class, $languageAspect->getId(), $languageAspect->getId(), $languageAspect->getOverlayType(), $languageAspect->getFallbackChain());
			$context->setAspect('language', $indexLanguageAspect);
		}

		$_procObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
		$_procObj->hook_indexContent($pObj);

		$pObj->content = $contentBefore;
		$context->setAspect('language', $oldLanguageAspect);
	}
}
