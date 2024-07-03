<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

class TypolinkIcons {

	public function addLinkIcon(AfterLinkIsGeneratedEvent $event): void {
		$linkResult = $event->getLinkResult();
		$tagAttributes = $linkResult->getAttributes();

		if (empty($tagAttributes['class'])) {
			return;
		}

		$classes = GeneralUtility::trimExplode(' ', $tagAttributes['class'], true);

		$svgMapping = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_vierwdbase.']['linkIcons.'];
		if (!$svgMapping) {
			return;
		}

		if (in_array('external-link-new-window', $classes)) {
			$linkResult = $linkResult->withAttribute('target', '_blank');
			$rel = GeneralUtility::trimExplode(' ', $tagAttributes['rel'], true);
			$rel[] = 'noopener';
			$linkResult = $linkResult->withAttribute('rel', implode(' ', $rel));
		}

		$firstWord = $remaining = '';
		$linkText = $linkResult->getLinkText();
		if ($linkText) {
			if (preg_match('/^(\s*\w.*?)(\b.*)$/u', $linkText, $matches)) {
				$firstWord = $matches[1];
				$remaining = $matches[2];
			}
		}

		$cObj = $event->getContentObjectRenderer();
		foreach ($svgMapping as $class => $svg) {
			if (in_array($class, $classes)) {
				$svg = str_replace(["\n", "\r"], '', $cObj->cObjGetSingle('SVG', [
					'src' => $svg,
				]));
				$linkText = $firstWord ? '<span class="text-nowrap">' . $svg . $firstWord . '</span>' . $remaining : $svg . $linkText;
				$linkResult = $linkResult->withLinkText($linkText);
				break;
			}
		}
		$event->setLinkResult($linkResult);
	}

}
