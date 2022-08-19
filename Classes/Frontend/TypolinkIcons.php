<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TypolinkIcons {

	public function addLinkIcon(array $params, ContentObjectRenderer $cObj): void {
		$tagAttributes =& $params['tagAttributes'];

		if (empty($tagAttributes['class'])) {
			return;
		}

		$classes = GeneralUtility::trimExplode(' ', $tagAttributes['class'], true);

		$svgMapping = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_vierwdbase.']['linkIcons.'];
		if (!$svgMapping) {
			return;
		}

		if (in_array('external-link-new-window', $classes)) {
			$tagAttributes['target'] = '_blank';
			$rel = GeneralUtility::trimExplode(' ', $tagAttributes['rel'], true);
			$rel[] = 'noopener';
			$tagAttributes['rel'] = implode(' ', $rel);
		}

		$firstWord = $remaining = '';
		if ($params['linktxt']) {
			if (preg_match('/^(\s*\w.*?)(\b.*)$/u', $params['linktxt'], $matches)) {
				$firstWord = $matches[1];
				$remaining = $matches[2];
			}
		}

		foreach ($svgMapping as $class => $svg) {
			if (in_array($class, $classes)) {
				$svg = str_replace(["\n", "\r"], '', $cObj->cObjGetSingle('SVG', [
					'src' => $svg,
				]));
				$params['linktxt'] = $firstWord ? ('<span class="text-nowrap">' . $svg . $firstWord . '</span>' . $remaining) : $svg . $params['linktxt'];
				break;
			}
		}

		$finalTagAttributes = array_merge($tagAttributes, GeneralUtility::get_tag_attributes($params['finalTagParts']['aTagParams']));
		$params['finalTag'] = '<a ' . GeneralUtility::implodeAttributes($finalTagAttributes) . '>';
	}

}
