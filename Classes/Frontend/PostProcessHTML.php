<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

use Masterminds\HTML5;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class PostProcessHTML implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$response = $handler->handle($request);

		$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		assert(is_array($extConf));
		if ($extConf['cachedPostprocessing'] ?? true) {
			return $response;
		}

		$TSFE = $request->getAttribute('frontend.controller');
		if (!$TSFE) {
			return $response;
		}
		$content = (string)$response->getBody();
		$content = $this->postProcessHTML($content, $TSFE);
		$body = new Stream('php://temp', 'wb+');
		$body->write($content);
		$body->rewind();
		return $response->withBody($body);
	}

	public function processCached(AfterCacheableContentIsGeneratedEvent $event): void {
		$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		assert(is_array($extConf));
		if (!($extConf['cachedPostprocessing'] ?? true)) {
			return;
		}
		$TSFE = $event->getController();
		$TSFE->content = $this->postProcessHTML($TSFE->content, $TSFE);
	}

	public function postProcessHTML(string $content, TypoScriptFrontendController $TSFE): string {
		if (!$content) {
			return '';
		}

		try {
			$disableAllHeaderCode = ArrayUtility::getValueByPath($TSFE->config, 'config/disableAllHeaderCode');
			if ($disableAllHeaderCode) {
				return $content;
			}
		} catch (MissingArrayPathException $th) {
			// ignore
		}

		try {
			$postProcessHTML = ArrayUtility::getValueByPath($TSFE->config, 'config/tx_vierwd./postProcessHTML');
			if (!$postProcessHTML) {
				return $content;
			}
		} catch (MissingArrayPathException $th) {
			// ignore
		}

		$html5 = new HTML5(['disable_html_ns' => true]);
		$document = $html5->loadHTML($content);

		$this->addHyphenation($document, $TSFE);
		$this->addNoopener($document, $TSFE);

		return $html5->saveHTML($document);
	}

	/**
	 * get all hyphenation words
	 */
	protected function getHyphenationWords(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_vierwdbase_hyphenation');
		$queryBuilder->select('*')->from('tx_vierwdbase_hyphenation');
		$hyphenationRows = $queryBuilder->executeQuery()->fetchAllAssociative();

		$configuration = implode("\n", array_map(function($hyphenationRow) {
			return $hyphenationRow['hyphenation'];
		}, $hyphenationRows));
		$words = array_map('trim', explode("\n", $configuration));

		return $words;
	}

	private function addHyphenation(DOMDocument $document, TypoScriptFrontendController $TSFE): void {
		// @phpstan-ignore-next-line
		if (isset($TSFE->config['config']['tx_vierwd.'], $TSFE->config['config']['tx_vierwd.']['hyphenation']) && !$TSFE->config['config.']['tx_vierwd.']['hyphenation']) {
			return;
		}

		$hyphenationWords = $this->getHyphenationWords();
		if ($hyphenationWords && $TSFE->content) {
			$replacements = [];
			$shy          = html_entity_decode('&shy;', 0, 'UTF-8');
			foreach ($hyphenationWords as $word) {
				$searchWord = trim(str_replace(['#', '|', '•', $shy], '', $word));
				if (is_numeric($searchWord)) {
					// Do not add shy within long numbers
					continue;
				}
				$replacements[$searchWord] = trim(str_replace(['#', '|', '•'], $shy, $word));
			}

			uksort($replacements, function($word1, $word2) {
				return strlen($word2) - strlen($word1);
			});

			$searchWords  = array_keys($replacements);
			$replaceWords = array_values($replacements);

			$body = $document->getElementsByTagName('body')->item(0);

			$XPath = new DOMXPath($document);
			$nodes = $XPath->evaluate('.//text()', $body);
			assert($nodes instanceof DOMNodeList);
			foreach ($nodes as $node) {
				assert($node instanceof DOMNode);
				if ($node->nodeType === XML_TEXT_NODE && $node->parentNode && $node->parentNode->nodeName !== 'script' && $node->parentNode->nodeName !== 'style') {
					$node->nodeValue = str_replace($searchWords, $replaceWords, $node->nodeValue ?? '');
				}
			}
		}
	}

	/**
	 * add rel=noopener to all external links.
	 *
	 * @see https://developers.google.com/web/tools/lighthouse/audits/noopener
	 */
	private function addNoopener(DOMDocument $document, TypoScriptFrontendController $TSFE): void {
		// @phpstan-ignore-next-line
		if (isset($TSFE->config['config']['tx_vierwd.'], $TSFE->config['config']['tx_vierwd.']['noopener']) && !$TSFE->config['config.']['tx_vierwd.']['noopener']) {
			return;
		}

		$body = $document->getElementsByTagName('body')->item(0);

		$XPath = new DOMXPath($document);
		$nodes = $XPath->evaluate('.//a[@target="_blank"][not(contains(@rel, "noopener"))]', $body);
		assert($nodes instanceof DOMNodeList);
		foreach ($nodes as $link) {
			assert($link instanceof DOMElement);
			$rel = $link->getAttribute('rel');
			$rel = trim($rel . ' noopener');
			$link->setAttribute('rel', $rel);
		}
	}

}
