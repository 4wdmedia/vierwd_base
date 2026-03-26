<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use JSMin;

use TYPO3\CMS\Core\Http\ApplicationType;

use function Safe\file_get_contents;
use function Safe\filesize;

/**
 * Class which handles JavascriptOptimizations.
 *
 * Javascript will be minified via JSMin, if it is included via page.includeJS*.
 * Javascript files will be inlined if they are smaller than 2000bytes.
 * Additional TypoScript config enabled: config.compressJs = 1 and config.concatenateJs = 1
 * @TODO https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-108055-RemovedFrontendAssetConcatenationAndCompression.html#breaking-108055-removed-frontend-asset-concatenation-and-compression
 * @TODO https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-108055-RemovedPageRendererRelatedHooksAndMethods.html#breaking-108055-removed-pagerenderer-related-hooks-and-methods
 * This needs to be converted to a pre-render hook:
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']
 */
class JavascriptOptimization {

	public function jsPreProcess(array &$params): void {
		if (!($GLOBALS['TYPO3_REQUEST'] ?? null)) {
			return;
		}
		if (!ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
			return;
		}

		$this->inlineSmallFiles($params, 'jsFiles', 'jsInline');
		$this->inlineSmallFiles($params, 'jsFooterFiles', 'jsFooterInline');

		$params['jsInline'] = $this->minifyInlineJS($params['jsInline']);
		$params['jsFooterInline'] = $this->minifyInlineJS($params['jsFooterInline']);
	}

	private function inlineSmallFiles(array &$params, string $fileKey, string $inlineKey): void {
		foreach ($params[$fileKey] as $key => $properties) {
			[$file] = explode('?', $properties['file']);
			if (!file_exists($file)) {
				continue;
			}
			$size = filesize($file);

			if ($size > 2000) {
				continue;
			}
			// the file is smaller than 2000 byte. inline it for better performance (will use one less request)
			unset($params[$fileKey][$key]);

			$content = file_get_contents($file);

			$params[$inlineKey][$key] = [
				'code' => $content,
				'compress' => false,
				'section' => $properties['section'],
				'forceOnTop' => $properties['forceOnTop'] ?? false,
				'useNonce' => true,
			];
		}
	}

	private function minifyInlineJS(array $inlineJS): array {
		return array_map(function(array $properties): array {
			if ($properties['compress'] ?? false) {
				$properties['code'] = JSMin::minify($properties['code'] ?? '');
			}
			return $properties;
		}, $inlineJS);
	}

}
