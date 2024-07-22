<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class Utility {

	/** @phpstan-ignore-next-line Uninitialized property. Give it default value or assign it in the constructor. */
	protected ContentObjectRenderer $cObj;

	public function setContentObjectRenderer(ContentObjectRenderer $cObj): void {
		$this->cObj = $cObj;
	}

	/**
	 * Utility function to output a file using TypoScript
	 *
	 * Usage:
	 * 10 = USER
	 * 10.userFunc = Vierwd\VierwdBase\Hooks\Utility->outputFile
	 * 10.file = EXT:vierwd_example/Resources/Public/static/vars.css
	 * 10.stdWrap.wrap = <style id="css-vars">|</style>
	 */
	public function outputFile(?string $content, array $params): string {
		$file = $params['file'];
		if (isset($params['file.'])) {
			$file = $this->cObj->stdWrap($file, $params['file.']);
		}

		$file = GeneralUtility::getFileAbsFileName($file);

		if (!$file || !file_exists($file)) {
			return '';
		}

		return (string)file_get_contents($file);
	}

	/**
	 * TYPO3 adds meta-tags with the name-attribute and not with the property-attribute.
	 * OpenGraph needs the property-attribute.
	 */
	public function addMetaTags(?string $content, array $params): ?string {
		if (empty($params['meta.']) && empty($params['link.'])) {
			return $content;
		}

		$metaTags = is_array($params['meta.'] ?? null) ? $params['meta.'] : [];
		$linkTags = is_array($params['link.'] ?? null) ? $params['link.'] : [];

		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$defaultAttribute = isset($params['defaultAttribute']) ? $params['defaultAttribute'] : 'name';

		foreach ($linkTags as $linkTag) {
			$pageRenderer->addHeaderData($linkTag);
		}

		$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
		$metaTags = $typoScriptService->convertTypoScriptArrayToPlainArray($metaTags);
		foreach ($metaTags as $key => $data) {
			$attribute = $defaultAttribute;
			$required = false;
			$value = '';

			if (is_array($data)) {
				$attribute = isset($data['attribute']) ? $data['attribute'] : $defaultAttribute;
				$required = !empty($data['required']);

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

						$pageRenderer->setMetaTag($attribute, (string)$key, $value);
					}
					continue;
				}

				$nodeValue = isset($data['_typoScriptNodeValue']) ? $data['_typoScriptNodeValue'] : '';
				$value = trim((string)$this->cObj->stdWrap($nodeValue, $params['meta.'][$key . '.']));
			} else if (is_scalar($data)) {
				$value = (string)$data;
			}

			if (!$value && $required) {
				continue;
			}

			$pageRenderer->setMetaTag($attribute, (string)$key, $value);
		}

		return $content;
	}

}
