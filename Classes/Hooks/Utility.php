<?php

namespace Vierwd\VierwdBase\Hooks;

use DOMDocument;
use DOMXPath;

use Masterminds\HTML5;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Utility {

	/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
	public $cObj;

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

		$metaTags = is_array($params['meta.']) ? $params['meta.'] : [];
		$linkTags = is_array($params['link.']) ? $params['link.'] : [];

		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$defaultAttribute = isset($params['defaultAttribute']) ? $params['defaultAttribute'] : 'name';

		foreach ($linkTags as $linkTag) {
			$pageRenderer->addHeaderData($linkTag);
		}

		$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
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

						$pageRenderer->setMetaTag($attribute, $key, $value);
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

			$pageRenderer->setMetaTag($attribute, $key, $value);
		}

		return $content;
	}

	public function postProcessHTML(array $params, TypoScriptFrontendController $TSFE): void {
		if (!empty($TSFE->config['config']['disableAllHeaderCode'])) {
			// do not process content, if all headers are disabled. Probably plain text variant
			return;
		}

		if (isset($TSFE->config['config']['tx_vierwd.'], $TSFE->config['config']['tx_vierwd.']['postProcessHTML']) && !$TSFE->config['config.']['tx_vierwd.']['postProcessHTML']) {
			return;
		}

		if (!$TSFE->content) {
			return;
		}

		$content = $TSFE->content;

		$html5 = new HTML5(['disable_html_ns' => true]);
		$document = $html5->loadHTML($content);

		$this->addHyphenation($document);
		$this->addNoopener($document);

		$TSFE->content = $html5->saveHTML($document);
	}

	/**
	 * get all hyphenation words
	 */
	protected function getHyphenationWords(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_vierwdbase_hyphenation');
		$queryBuilder->select('*')->from('tx_vierwdbase_hyphenation');
		$hyphenationRows = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

		$configuration = implode("\n", array_map(function($hyphenationRow) {
			return $hyphenationRow['hyphenation'];
		}, $hyphenationRows));
		$words = array_map('trim', explode("\n", $configuration));

		return $words;
	}

	private function addHyphenation(DOMDocument $document): void {
		if (isset($GLOBALS['TSFE']->config['config']['tx_vierwd.'], $GLOBALS['TSFE']->config['config']['tx_vierwd.']['hyphenation']) && !$GLOBALS['TSFE']->config['config.']['tx_vierwd.']['hyphenation']) {
			return;
		}

		$hyphenationWords = $this->getHyphenationWords();
		if ($hyphenationWords && $GLOBALS['TSFE']->content) {
			$replacements = [];
			$shy          = html_entity_decode('&shy;', 0, 'UTF-8');
			foreach ($hyphenationWords as $word) {
				$replacements[trim(str_replace(['#', '|', '•', $shy], '', $word))] = trim(str_replace(['#', '|', '•'], $shy, $word));
			}

			uksort($replacements, function($word1, $word2) {
				return strlen($word2) - strlen($word1);
			});

			$searchWords  = array_keys($replacements);
			$replaceWords = array_values($replacements);

			$body = $document->getElementsByTagName('body')->item(0);

			$XPath = new DOMXPath($document);
			$nodes = $XPath->evaluate('.//text()', $body);
			foreach ($nodes as $node) {
				if ($node->nodeType === XML_TEXT_NODE && $node->parentNode->nodeName !== 'script' && $node->parentNode->nodeName !== 'style') {
					$node->nodeValue = str_replace($searchWords, $replaceWords, $node->nodeValue);
				}
			}
		}
	}

	/**
	 * add rel=noopener to all external links.
	 *
	 * @see https://developers.google.com/web/tools/lighthouse/audits/noopener
	 */
	private function addNoopener(DOMDocument $document): void {
		if (isset($GLOBALS['TSFE']->config['config']['tx_vierwd.'], $GLOBALS['TSFE']->config['config']['tx_vierwd.']['noopener']) && !$GLOBALS['TSFE']->config['config.']['tx_vierwd.']['noopener']) {
			return;
		}

		$body = $document->getElementsByTagName('body')->item(0);

		$XPath = new DOMXPath($document);
		$nodes = $XPath->evaluate('.//a[@target="_blank"][not(contains(@rel, "noopener"))]', $body);
		foreach ($nodes as $link) {
			$rel = $link->getAttribute('rel');
			$rel = trim($rel . ' noopener');
			$link->setAttribute('rel', $rel);
		}
	}

}
