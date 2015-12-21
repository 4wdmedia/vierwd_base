<?php

namespace Vierwd\VierwdBase\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Robert Vock <robert.vock@4wdmedia.de>, FORWARD MEDIA
 *
 *  All rights reserved
 *
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package vierwd_base
 */
class Utility {

	/**
	 * Utility function to output a file using TypoScript
	 *
	 * Usage:
	 * 10 = USER
	 * 10.userFunc = Vierwd\VierwdBase\Hooks\Utility->outputFile
	 * 10.file = EXT:vierwd_example/Resources/Public/static/vars.css
	 * 10.stdWrap.wrap = <style id="css-vars">|</style>
	 */
	public function outputFile($content, $params) {
		$file = $params['file'];

		$file = GeneralUtility::getFileAbsFileName($file);

		if (!$file || !file_exists($file)) {
			return '';
		}

		return file_get_contents($file);
	}

	/**
	 * TYPO3 adds meta-tags with the name-attribute and not with the property-attribute.
	 * OpenGraph needs the property-attribute.
	 */
	public function addMetaTags($content, $params) {
		if (empty($params['meta.']) || !is_array($params['meta.'])) {
			return $content;
		}

		$endingSlash = $GLOBALS['TSFE']->xhtmlVersion ? ' /' : '';

		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$defaultAttribute = isset($params['defaultAttribute']) ? $params['defaultAttribute'] : 'name';

		$typoScriptService = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Service\TypoScriptService::class);
		$metaTags = $typoScriptService->convertTypoScriptArrayToPlainArray($params['meta.']);
		foreach ($metaTags as $key => $data) {
			$attribute = isset($data['attribute']) ? $data['attribute'] : $defaultAttribute;
			$required = !empty($data['required']);

			if (is_array($data)) {
				// check if all keys are numeric
				$onlyNumericKeys = !array_filter(array_keys($data), function($key) {
					return !is_integer($key);
				});

				if ($onlyNumericKeys) {
					// process the keys and output the tag multiple times
					ksort($data);
					foreach ($data as $subkey => $value) {
						if (is_array($value)) {
							$nodeValue = isset($value['_typoScriptNodeValue']) ? $value['_typoScriptNodeValue'] : '';
							$value = $this->cObj->stdWrap($nodeValue, $params['meta.'][$key . '.'][$subkey . '.']);
						}

						if (!$value && $required) {
							continue;
						}

						$pageRenderer->addMetaTag(
							'<meta ' . $attribute . '="' . $key . '" content="' . htmlspecialchars($value) . '"' . $endingSlash . '>'
						);
					}
					continue;
				}

				$nodeValue = isset($data['_typoScriptNodeValue']) ? $data['_typoScriptNodeValue'] : '';
				$value = trim($this->cObj->stdWrap($nodeValue, $params['meta.'][$key . '.']));
			} else {
				$value = $data;
			}

			if (!$value && $required) {
				continue;
			}

			$pageRenderer->addMetaTag(
				'<meta ' . $attribute . '="' . $key . '" content="' . htmlspecialchars($value) . '"' . $endingSlash . '>'
			);
		}

		return $content;
	}
}
