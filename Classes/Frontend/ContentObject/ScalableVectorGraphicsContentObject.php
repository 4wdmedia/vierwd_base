<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\ContentObject;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

use Vierwd\SvgInliner\SvgInliner;

/**
 * Output svg as inline html
 */
class ScalableVectorGraphicsContentObject extends AbstractContentObject {

	protected static ?SvgInliner $svgInliner = null;

	/**
	 * Rendering the cObject, SVG
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Empty string (the cObject only sets internal data!)
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function render($conf = []) {
		$cObj = $this->getContentObjectRenderer();

		if (!empty($conf['if.']) && !$cObj->checkIf($conf['if.'])) {
			return '';
		}

		if (self::$svgInliner === null) {
			self::$svgInliner = new SvgInliner(['excludeFromConcatenation' => true]);
		}

		if (!empty($conf['output'])) {
			return self::$svgInliner->renderFullSVG();
		}

		$conf['width'] = $conf['width'] ?? null;
		$conf['height'] = $conf['height'] ?? null;
		$conf['src'] = $conf['src'] ?? null;
		$conf['value'] = $conf['value'] ?? null;
		$conf['class'] = $conf['class'] ?? null;

		$width = isset($conf['width.']) ? $cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
		$height = isset($conf['height.']) ? $cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
		$src = isset($conf['src.']) ? $cObj->stdWrap($conf['src'], $conf['src.']) : $conf['src'];
		$value = isset($conf['value.']) ? $cObj->stdWrap($conf['value'], $conf['value.']) : $conf['value'];
		$class = isset($conf['class.']) ? $cObj->stdWrap($conf['class'], $conf['class.']) : $conf['class'];

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
			$value = trim((string)file_get_contents($file));
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

		$attributes = array_diff_key($conf, array_merge(['src' => 'remove', 'value' => 'remove'], $options));
		$attributes = array_filter($attributes, function($key) {
			return substr($key, -1) !== '.';
		}, ARRAY_FILTER_USE_KEY);
		$options['attributes'] = $attributes;

		if (isset($conf['additionalOptions']) && is_array($conf['additionalOptions'])) {
			$options += $conf['additionalOptions'];
		}

		$value = self::$svgInliner->renderSVG($value, $options);

		if (isset($conf['stdWrap.'])) {
			$value = $cObj->stdWrap($value, $conf['stdWrap.']);
			assert(is_string($value));
		}
		return $value;
	}

}
