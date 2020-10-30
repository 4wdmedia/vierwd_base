<?php

namespace Vierwd\VierwdBase\Frontend\ContentObject;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

use Vierwd\SvgInliner\SvgInliner;

/**
 * Output svg as inline html
 */
class ScalableVectorGraphicsContentObject extends AbstractContentObject {

	/**
	 * @var \Vierwd\SvgInliner\SvgInliner;
	 */
	protected static $svgInliner = null;

	/**
	 * Rendering the cObject, SVG
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Empty string (the cObject only sets internal data!)
	 */
	public function render($conf = []) {
		if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
			return '';
		}

		if (self::$svgInliner === null) {
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

			if (isset($_SERVER['VIERWD_CONFIG'])) {
				// check if filename is written correctly
				if (!glob($file . '*')) {
					throw new \Exception('SVG not found ' . $src . "\nMaybe incorrect case of filename?", 1581686825);
				}
			}
			$value = trim(file_get_contents($file));
			$class .= ' svg-' . pathinfo($file, PATHINFO_FILENAME);
		}

		$identifier = 'svg-' . md5($value);

		$options = [
			'width' => $width,
			'height' => $height,
			'class' => $class,
			'identifier' => $identifier,
		];

		$allowedOptions = [
			'excludeFromConcatenation',
			'ignoreDuplicateIds',
			'removeComments',
		];
		foreach ($allowedOptions as $optionName) {
			if (isset($conf[$optionName])) {
				$options[$optionName] = $conf[$optionName];
			}
		}

		if (isset($conf['additionalOptions']) && is_array($conf['additionalOptions'])) {
			$options += $conf['additionalOptions'];
		}

		$value = self::$svgInliner->renderSVG($value, $options);

		if (isset($conf['stdWrap.'])) {
			$value = $this->cObj->stdWrap($value, $conf['stdWrap.']);
		}
		return $value;
	}
}
