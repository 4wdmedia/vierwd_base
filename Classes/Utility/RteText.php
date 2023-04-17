<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Replacement for old Bodytext.tpl.
 * Can be used in Template as follows:
 * ```
 * {\Vierwd\VierwdBase\Utility\RteText::transformText($bodytext) nofilter}
 * ```
 */
class RteText {

	private static ?ContentObjectRenderer $cObj = null;

	public static function transformText(?string $content): string {
		if (!$content) {
			return '';
		}

		if (self::$cObj === null) {
			self::$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		}

		$content = self::$cObj->parseFunc($content, null, '< lib.parseFunc_RTE');

		$readMore = LocalizationUtility::translate('text.read-more', 'VierwdBase') ?? '';
		$readLess = LocalizationUtility::translate('text.read-less', 'VierwdBase') ?? '';

		$content = preg_replace_callback('/(<p>)?\s*%MORE%(?:(?<readMore>[^%]+)%)?(?:(?<readLess>[^%]+)%)?\s*(<\/p>)(?<remainingText>.*)$/is', function(array $matches) use ($readMore, $readLess) {
			$remainingText = $matches['remainingText'];
			$readMore = $matches['readMore'] ?: $readMore;
			return implode('', [
				'<div class="textmedia__more textmedia__more--hidden">' . $remainingText . '</div>',
				'<p class="textmedia__more-link">',
					'<a class="button" href="#" data-read-more="' . htmlspecialchars($readMore) . '" data-read-less="' . htmlspecialchars($readLess) . '">',
						$readMore,
					'</a>',
				'</p>',
			]);
		}, $content);
		assert(is_string($content));

		$content = preg_replace_callback('/(?:<p>)?%VIDEO%(?<videoID>[^%]*)%(<?:\/p>)?/', function($matches): string {
			return YouTubeUtility::generatePreview($matches['videoID']);
		}, $content);
		assert(is_string($content));

		return $content;
	}

}
