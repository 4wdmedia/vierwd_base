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

use DOMDocument;
use DOMXPath;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Database\ConnectionPool;

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
		if (isset($params['file.'])) {
			$file = $this->cObj->stdWrap($file, $params['file.']);
		}

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
		if (empty($params['meta.']) && empty($params['link.'])) {
			return $content;
		}

		$metaTags = is_array($params['meta.']) ? $params['meta.'] : [];
		$linkTags = is_array($params['link.']) ? $params['link.'] : [];

		$endingSlash = $GLOBALS['TSFE']->xhtmlVersion ? ' /' : '';

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

	public function postProcessHTML($params, TypoScriptFrontendController $TSFE) {
		if (!empty($TSFE->config['config']['disableAllHeaderCode'])) {
			// do not process content, if all headers are disabled. Probably plain text variant
			return;
		}

		if (isset($TSFE->config['config']['tx_vierwd.'], $TSFE->config['config']['tx_vierwd.']['postProcessHTML']) && !$TSFE->config['config.']['tx_vierwd.']['postProcessHTML']) {
			return;
		}


		$document = new DOMDocument('1.0', 'utf-8');
		// Ignore errors caused by HTML5 Doctype
		libxml_use_internal_errors(true);
		$scriptBlocks = [];
		$commentBlocks = [];
		$content = $TSFE->content;

		// This regex is like #<!--.*?-->#si, but with much better performance
		// https://stackoverflow.com/questions/50539908/regular-expression-preg-backtrack-limit-error-when-extracting-really-long-text-n/50547822#50547822
		$content = preg_replace_callback('#<!--([^-]*(?:(?!--)[^>]*)*)(*SKIP)-->#si', function($matches) use (&$commentBlocks) {
			$commentBlocks[] = $matches[0];
			return '<!--COMMENT_BLOCK_' . (count($commentBlocks) - 1) . '-->';
		}, $content);
		if ($content === null) {
			$pcreMessages = get_defined_constants(true);
			$pcreMessages = array_flip($pcreMessages['pcre']);
			throw new \Exception('Could not extract comments: ' . $pcreMessages[preg_last_error()], 1528186643);
		}

		// This regex is like <script[^>]*>.*?</script>#si, but with much better performance
		// https://stackoverflow.com/questions/50539908/regular-expression-preg-backtrack-limit-error-when-extracting-really-long-text-n/50547822#50547822
		$content = preg_replace_callback('#<script[^>]*>([^<]*(?:<\/(?!script)[^>]*)*)(*SKIP)</script>#si', function($matches) use (&$scriptBlocks) {
			$scriptBlocks[] = $matches[0];
			return '<!--HYPHENATION_SCRIPT_BLOCK_' . (count($scriptBlocks) - 1) . '-->';
		}, $content);
		if ($content === null) {
			$pcreMessages = get_defined_constants(true);
			$pcreMessages = array_flip($pcreMessages['pcre']);
			throw new \Exception('Could not extract scripts: ' . $pcreMessages[preg_last_error()], 1528186643);
		}

		// DOMDocument needs old meta-charset declaration. Otherwise saving will encode entities
		$content = str_replace('<meta charset="utf-8">', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);
		if (strpos($content, '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"') === false) {
			$content = '<?xml encoding="utf-8">' . $content;
		}
		$document->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL);
		libxml_use_internal_errors(false);

		$this->addHyphenation($document);
		$this->addNoopener($document);

		$TSFE->content = $document->saveHTML();
		$TSFE->content = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', '<meta charset="utf-8">', $TSFE->content);
		$TSFE->content = str_replace('<?xml encoding="UTF-8">', '', $TSFE->content);
		$TSFE->content = preg_replace_callback('#<!--HYPHENATION_SCRIPT_BLOCK_(\d+)-->#', function($matches) use (&$scriptBlocks) {
			return $scriptBlocks[$matches[1]];
		}, $TSFE->content);
		$TSFE->content = preg_replace_callback('#<!--COMMENT_BLOCK_(\d+)-->#', function($matches) use (&$commentBlocks) {
			return $commentBlocks[$matches[1]];
		}, $TSFE->content);

		// Update Content-Length Header, if it is set
		// Condition taken from TypoScriptFrontendController::processOutput
		if (
			(!isset($TSFE->config['config']['enableContentLengthHeader']) || $TSFE->config['config']['enableContentLengthHeader'])
			&& !$TSFE->beUserLogin
			&& !$GLOBALS['TYPO3_CONF_VARS']['FE']['debug']
			&& !$TSFE->config['config']['debug'] && !$TSFE->doWorkspacePreview()
			&& !headers_sent()
		) {
			header('Content-Length: ' . strlen($TSFE->content));
		}
	}

	/**
	 * get all hyphenation words
	 */
	protected function getHyphenationWords() {
		if (TYPO3_version <= '8.5.0') {
			$hyphenationRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('hyphenation', 'tx_vierwdbase_hyphenation', '1=1');
		} else {
			$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_vierwdbase_hyphenation');
			$queryBuilder->select('*')->from('tx_vierwdbase_hyphenation');
			$hyphenationRows = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
		}

		$configuration = implode("\n", array_map(function($hyphenationRow) {
			return $hyphenationRow['hyphenation'];
		}, $hyphenationRows));
		$words = array_map('trim', explode("\n", $configuration));

		return $words;
	}

	private function addHyphenation(DOMDocument $document) {
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
				if ($node->nodeType === XML_TEXT_NODE) {
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
	private function addNoopener(DOMDocument $document) {
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
