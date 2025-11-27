<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Seo;

use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

class Canonical implements SingletonInterface {

	public function getTag(?string $content, array $params = []): string {
		if (!empty($GLOBALS['TSFE']->no_cache) || !empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			return '';
		}

		$url = self::getUrl();

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$url = $cObj->stdWrap($url, $params);
		assert(is_string($url));

		return $url;
	}

	static public function getUrl(): string {
		static $url = null;
		if ($url === null) {
			$url = self::calculateUrl();
		}
		return $url;
	}

	static public function calculateUrl(): string {
		if (!empty($GLOBALS['TSFE']->no_cache)) {
			return '';
		}

		if (!empty($GLOBALS['TSFE']->page['canonical_link'])) {
			$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
			$url = $cObj->typoLink_URL([
				'returnLast' => 'url',
				'forceAbsoluteUrl' => true,
				'parameter' => $GLOBALS['TSFE']->page['canonical_link'],
			]);
			if ($url) {
				return $url;
			}
		}

		$request = $GLOBALS['TYPO3_REQUEST'];
		$pageArguments = $request->getAttribute('routing', null);
		if ($pageArguments instanceof PageArguments) {
			$queryParams = $pageArguments->getDynamicArguments();
		} else {
			$queryParams = $request->getQueryParams();
		}
		$queryParams = array_diff_key($queryParams, ['L' => 0, 'id' => 0]);

		$cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
		if (!$pageArguments['cHash'] && $queryParams) {
			$queryParams['id'] = $GLOBALS['TSFE']->id;
			if ($cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $queryParams))) {
				return '';
			}
		}

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$query = $request->getQueryParams();
		if ($query && is_array($query)) {
			$query['id'] = $GLOBALS['TSFE']->id;
			$query = $cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
			unset($query['encryptionKey']);
			unset($query['cHash']);

			$removeParameters = $GLOBALS['TSFE']->config['config']['tx_vierwd.']['removeCanonicalUrlParameters.'] ?? [];
			$removeParameters = array_filter($removeParameters);
			foreach ($removeParameters as $parameter) {
				if (ArrayUtility::isValidPath($query, $parameter, '|')) {
					$query = ArrayUtility::removeByPath($query, $parameter, '|');
				}
			}

			$query2 = $query;
			foreach ($query2 as $parameter => $value) {
				if (is_array($value) && !$value) {
					// empty array -> remove
					unset($query[$parameter]);
				}
			}

			// regenerate URL

			// First: remove L and id parameter
			$query = array_diff_key($query, ['L' => 0, 'id' => 0]);
			if (!$query) {
				// there are more parameters beside L and id. Regenerate including cHash
				$url = $cObj->typoLink_URL([
					'returnLast' => 'url',
					'forceAbsoluteUrl' => true,
					'parameter' => 't3://page?uid=' . $GLOBALS['TSFE']->id,
				]);
			} else {
				// only L and id left. generate without cHash
				$url = $cObj->typoLink_URL([
					'returnLast' => 'url',
					'forceAbsoluteUrl' => true,
					'parameter' => 't3://page?uid=' . $GLOBALS['TSFE']->id,
					'additionalParams' => GeneralUtility::implodeArrayForUrl('', $query),
				]);
			}
		} else {
			$url = $cObj->typoLink_URL([
				'returnLast' => 'url',
				'forceAbsoluteUrl' => true,
				'parameter' => 't3://page?uid=' . $GLOBALS['TSFE']->id,
			]);
		}

		return $url;
	}

}
