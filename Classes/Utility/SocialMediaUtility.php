<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class SocialMediaUtility {

	static public function getSocialMediaPages(int $folderUid): array {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

		$socialMediaPages = $GLOBALS['TSFE']->sys_page->getMenu($folderUid, '*', 'sorting', 'AND hidden=0');
		foreach ($socialMediaPages as $uid => $socialMediaPage) {
			if ($socialMediaPage['doktype'] == PageRepository::DOKTYPE_LINK && $socialMediaPage['url']) {
				$href = $socialMediaPage['url'];
			} else {
				$href = $cObj->createUrl(['parameter' => $socialMediaPage['uid']]);
			}
			$socialMediaPages[$uid]['icon'] = preg_replace('/\W+/u', '-', mb_strtolower($socialMediaPage['title']));
			$socialMediaPages[$uid]['href'] = $href;
		}
		return $socialMediaPages;
	}

}
