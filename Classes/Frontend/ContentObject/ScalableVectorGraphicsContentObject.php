<?php
namespace Vierwd\VierwdBase\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Robert Vock <robert.vock@4wdmedia.de>, 4WD MEDIA
 *
 *  All rights reserved
 *
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Output svg as inline html
 *
 * @author Robert Vock <robert.vock@4wdmedia.de>
 */
class ScalableVectorGraphicsContentObject extends \TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject {

	private static $fullSvg;

	private static $usedSvgs = array();

	/**
	 * Rendering the cObject, SVG
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Empty string (the cObject only sets internal data!)
	 * @throws \Exception if the SVG is invalid
	 */
	public function render($conf = array()) {
		if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
			return '';
		}

		if ($conf['output']) {
			if (self::$fullSvg) {
				return '';
				return self::$fullSvg->saveXml(self::$fullSvg->documentElement, LIBXML_NOEMPTYTAG);
			}
			return;
		}

		$excludeFromConcatenation = isset($conf['excludeFromConcatenation.']) ? $this->cObj->stdWrap($conf['excludeFromConcatenation'], $conf['excludeFromConcatenation.']) : $conf['excludeFromConcatenation'];
		$excludeFromConcatenation = true;

		if (!self::$fullSvg) {
			$fullSvg = self::$fullSvg = new \DOMDocument;
			$svg = $fullSvg->createElementNs('http://www.w3.org/2000/svg', 'svg');
			$svg->setAttribute('hidden', 'hidden');
			$fullSvg->appendChild($svg);
		}

		$width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
		$height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
		$src = isset($conf['src.']) ? $this->cObj->stdWrap($conf['src'], $conf['src.']) : $conf['src'];
		$value = isset($conf['value.']) ? $this->cObj->stdWrap($conf['value'], $conf['value.']) : $conf['value'];

		$identifier = '';
		if ($src) {
			$file = GeneralUtility::getFileAbsFileName($src);
			if (!file_exists($file)) {
				return '';
			}
			$value = trim(file_get_contents($file));
			$identifier = 'svg-' . pathinfo($file, PATHINFO_FILENAME);
		} else {
			$identifier = 'svg-' . md5($value);
		}

		if (!isset(self::$usedSvgs[$identifier])) {
			$document = new \DOMDocument;

			if (!@$document->loadXml($value)) {
				throw new \Exception('Could not load SVG: ' . $src);
			}

			// always add the file name as class name of the root element
			if ($document->documentElement->hasAttribute('class')) {
				$document->documentElement->setAttribute('class', $document->documentElement->getAttribute('class') . ' ' . $identifier);
			} else {
				$document->documentElement->setAttribute('class', $identifier);
			}

			$symbol = self::$fullSvg->createElement('symbol');
			foreach ($document->documentElement->attributes as $name => $value) {
				$symbol->setAttribute($name, $value->nodeValue);
			}
			$symbol->setAttribute('id', $identifier);
			foreach ($document->documentElement->childNodes as $child) {
				$child = self::$fullSvg->importNode($child, true);
				$symbol->appendChild($child);
			}

			self::$fullSvg->documentElement->appendChild($symbol);

			self::$usedSvgs[$identifier] = $symbol;
		} else {
			$symbol = self::$usedSvgs[$identifier];
		}

		$document = new \DOMDocument;
		$svg = $document->createElementNs('http://www.w3.org/2000/svg', 'svg');
		$document->appendChild($svg);

		if (!$excludeFromConcatenation) {
			$use = $document->createElement('use');
			$use->setAttribute('xlink:href', '#' . $identifier);
			$svg->appendChild($use);
		} else {
			$svg->setAttribute('class', $identifier);

			// use the element directly
			foreach ($symbol->childNodes as $child) {
				$child = $document->importNode($child, true);
				$svg->appendChild($child);
			}
		}

		if ($symbol->hasAttribute('width')) {
			$svg->setAttribute('width', $symbol->getAttribute('width'));
		}
		if ($symbol->hasAttribute('height')) {
			$svg->setAttribute('height', $symbol->getAttribute('height'));
		}
		if ($symbol->hasAttribute('viewBox')) {
			$svg->setAttribute('viewBox', $symbol->getAttribute('viewBox'));
		}
		if ($symbol->hasAttribute('preserveAspectRatio')) {
			$svg->setAttribute('preserveAspectRatio', $symbol->getAttribute('preserveAspectRatio'));
		}

		// make sure there are no short-tags
		$value = $document->saveXml($document->documentElement, LIBXML_NOEMPTYTAG);

		if (isset($conf['stdWrap.'])) {
			$value = $this->cObj->stdWrap($value, $conf['stdWrap.']);
		}
		return $value;
	}

}
