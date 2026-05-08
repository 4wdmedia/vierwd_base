<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use function Safe\preg_replace;

class SocialMediaUtility {

	static public function getSocialMediaPages(int $folderUid): array {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

		$pageRepository = GeneralUtility::makeInstance(PageRepository::class);
		$socialMediaPages = $pageRepository->getMenu($folderUid, '*', 'sorting', 'AND hidden=0');
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
