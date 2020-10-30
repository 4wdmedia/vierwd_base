<?php

namespace Vierwd\VierwdBase\XClass\IndexedSearch;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Indexer as ParentIndexer;

class Indexer extends ParentIndexer {

	public function indexTypo3PageContent() {
		if ($GLOBALS['TSFE']->config && $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst']) {
			$this->conf['content'] = str_replace($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'], '@', $this->conf['content']);
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

		parent::indexTypo3PageContent();

		$context->setAspect('language', $oldLanguageAspect);
	}
}
