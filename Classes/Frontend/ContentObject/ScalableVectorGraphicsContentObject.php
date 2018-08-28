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

use Vierwd\SvgInliner\SvgInliner;

/**
 * Output svg as inline html
 *
 * @author Robert Vock <robert.vock@4wdmedia.de>
 */
class ScalableVectorGraphicsContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * @var Vierwd\SvgInliner\SvgInliner;
	 */
	protected static $svgInliner;

	/**
	 * Rendering the cObject, SVG
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Empty string (the cObject only sets internal data!)
	 * @throws \Exception if the SVG is invalid
	 */
	public function render($conf = []) {
		if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
			return '';
		}

		if (!self::$svgInliner) {
			self::$svgInliner = new SvgInliner(['excludeFromConcatenation' => true]);
		}

		if ($conf['output']) {
			return self::$svgInliner->renderFullSVG();
		}

		$width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
		$height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
		$src = isset($conf['src.']) ? $this->cObj->stdWrap($conf['src'], $conf['src.']) : $conf['src'];
		$value = isset($conf['value.']) ? $this->cObj->stdWrap($conf['value'], $conf['value.']) : $conf['value'];
		$class = isset($conf['class.']) ? $this->cObj->stdWrap($conf['class'], $conf['class.']) : $conf['class'];

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

		$options = [
			'width' => $width,
			'height' => $height,
			'class' => $class,
			'identifier' => $identifier,
		];

		if (isset($conf['excludeFromConcatenation'])) {
			$options['excludeFromConcatenation'] = $conf['excludeFromConcatenation'];
		}

		$value = self::$svgInliner->renderSVG($value, $options);

		if (isset($conf['stdWrap.'])) {
			$value = $this->cObj->stdWrap($value, $conf['stdWrap.']);
		}
		return $value;
	}
}
