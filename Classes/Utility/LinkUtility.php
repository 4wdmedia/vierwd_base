<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

use function Safe\parse_url;

class LinkUtility {

	private static ?ContentObjectRenderer $cObj = null;

	private static ?TypoLinkCodecService $typolinkCodecService = null;

	private static ?LinkService $linkService = null;

	/**
	 * @return array{
	 *     link: ?string,
	 *     isAnchor: bool,
	 *     isDownload: bool,
	 *     isExternal: bool,
	 *     isPagelink: bool,
	 *     isPhonelink: bool,
	 *     isVideo: bool,
	 *     videoId: ?string,
	 *     urlData: array,
	 *     linkData: array,
	 *     pathData: array,
	 * }
	 */
	public static function getLinkData(string $typolink): array {
		if (self::$cObj === null) {
			self::$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		}
		if (self::$typolinkCodecService === null) {
			self::$typolinkCodecService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
		}
		if (self::$linkService === null) {
			self::$linkService = GeneralUtility::makeInstance(LinkService::class);
		}

		$link = null;

		$isAnchor = false;
		$isDownload = false;
		$isExternal = false;
		$isPagelink = false;
		$isPhonelink = false;
		$isVideo = false;
		$videoId = null;

		$urlData = [];
		$linkData = [];
		$pathData = [];

		if ($typolink) {
			$urlData = self::$typolinkCodecService->decode($typolink);
			$linkData = self::$linkService->resolve($urlData['url']);

			$isPhonelink = $linkData['type'] === 'phone' || !empty($linkData['url'])  && str_starts_with($linkData['url'], 'tel://');
			if ($isPhonelink) {
				$linkData['type'] = 'phone';
			}
			$isPagelink = $linkData['type'] === 'page';
			$isDownload = $linkData['type'] === 'file';

			$link = self::$cObj->typoLink_URL(['parameter' => $typolink]);

			if ($isPagelink) {
				$externalURL = $GLOBALS['TSFE']->sys_page->getExtURL($GLOBALS['TSFE']->sys_page->getPage($linkData['pageuid']));
				if ($externalURL) {
					$isPagelink = false;
					$link = $externalURL;
				}
			}

			$pathData = parse_url($link);
			assert(is_array($pathData));
			$isVideo = !$isDownload && $pathData && in_array(!empty($pathData['host']), ['www.youtube.com', 'vimeo.com', 'youtu.be', 'www.youtube-nocookie.com']);

			if ($isVideo) {
				$matches = null;
				if (preg_match('-youtube.com/.*v=([^&/]*)-', $link, $matches)) {
					$videoId = trim($matches[1]);
					$link = 'https://www.youtube.com/watch?v=' . $videoId;
				} else if (preg_match('#youtube-nocookie.com/v/([^&/]*)#', $link, $matches)) {
					$videoId = trim($matches[1]);
					$link = 'https://www.youtube.com/watch?v=' . $videoId;
				} else if (preg_match('-youtu.be/([^&/]*)-', $link, $matches)) {
					$videoId = trim($matches[1]);
					$link = 'https://www.youtube.com/watch?v=' . $videoId;
				}
			}

			$isExternal = !$isVideo && !$isPhonelink && (!empty($pathData['host']) || !empty($urlData['target']));

			$isAnchor = !empty($pathData['fragment']) && ($linkData['pageuid'] ?? false) == $GLOBALS['TSFE']->id;

			if (!$urlData['class']) {
				if ($isDownload) {
					$urlData['class'] = 'download';
				} else if ($isExternal) {
					$urlData['class'] = 'external-link-new-window';
				} else if ($isPagelink) {
					$urlData['class'] = 'internal-link';
				} else if (str_starts_with($link, 'mailto:')) {
					$urlData['class'] = 'mail';
				}
			}
		}

		return [
			'link' => $link,

			'isAnchor' => $isAnchor,
			'isDownload' => $isDownload,
			'isExternal' => $isExternal,
			'isPagelink' => $isPagelink,
			'isPhonelink' => $isPhonelink,
			'isVideo' => $isVideo,
			'videoId' => $videoId,

			'urlData' => $urlData,
			'linkData' => $linkData,
			'pathData' => $pathData,
		];
	}

}
