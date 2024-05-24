<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use JSMin;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class which handles JavascriptOptimizations.
 *
 * Javascript will be minified via JSMin, if it is included via page.includeJS*.
 * Javascript files will be inlined if they are smaller than 2000bytes.
 * Additional TypoScript config enabled: config.compressJs = 1 and config.concatenateJs = 1
 */
class JavascriptOptimization {

	// Copied from PageRenderer, because it was changed to protected
	protected const PART_FOOTER = 2;

	protected ?ResourceCompressor $compressor = null;

	/**
	 * since TYPO3 6.0 minifyJs does not work.
	 * Funny story: jsMinify has a non-free license because it includes
	 * "The Software shall be used for Good, not Evil."
	 *
	 * Solution: Use jsmin included via composer
	 *
	 * @see http://forge.typo3.org/issues/31832
	 * @see http://wonko.com/post/jsmin-isnt-welcome-on-google-code
	 */
	public function jsMinify(array $params): string {
		if (empty($params['script'])) {
			return '';
		}
		// autoloaded via composer
		return JSMin::minify($params['script']);
	}

	public function minifyJsFiles(array $jsFiles): array {
		$filesAfterMinification = [];
		foreach ($jsFiles as $fileName => $fileOptions) {
			// If compression is enabled
			if ($fileOptions['compress']) {
				$compressedFilename = $this->minifyJsFile($fileOptions['file']);
				$fileOptions['compress'] = false;
				$fileOptions['file'] = $compressedFilename;
				$filesAfterMinification[$compressedFilename] = $fileOptions;
			} else {
				$filesAfterMinification[$fileName] = $fileOptions;
			}
		}
		return $filesAfterMinification;
	}

	/**
	 * Compresses a javascript file
	 *
	 * @param string $filename Source filename, relative to requested page
	 * @return string Filename of the compressed file, relative to requested page
	 */
	public function minifyJsFile(string $filename): string {
		// generate the unique name of the file
		$filenameAbsolute = Environment::getPublicPath() . '/' . $filename;
		$unique = $filenameAbsolute . '-min';
		if (@file_exists($filenameAbsolute)) {
			$fileStatus = stat($filenameAbsolute);
			if ($fileStatus !== false) {
				$unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'] . '-min';
			}
		}
		/** @var array $pathinfo */
		$pathinfo = PathUtility::pathinfo($filename);
		$targetFile = 'typo3temp/assets/compressor/' . $pathinfo['filename'] . '-' . md5($unique) . '.js';
		// only create it, if it doesn't exist, yet
		if (!file_exists(Environment::getPublicPath() . '/' . $targetFile)) {
			$contents = GeneralUtility::getUrl($filenameAbsolute);
			if (!is_string($contents)) {
				$contents = '';
			}
			$contents = GeneralUtility::makeInstance(ResourceCompressor::class)->compressJavaScriptSource($contents);
			// make sure the folder exists
			if (!is_dir(Environment::getPublicPath() . '/' . 'typo3temp/assets/compressor/')) {
				GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/' . 'typo3temp/assets/compressor/');
			}
			GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $targetFile, $contents);
		}
		return $targetFile;
	}

	/**
	 * inline JS which is smaller than 2000 bytes (meaning smaller than one request)
	 */
	public function jsCompressHandler(array $params, PageRenderer $pageRenderer): void {
		// Traverse the arrays, compress files
		if (count($params['jsInline'])) {
			foreach ($params['jsInline'] as $name => $properties) {
				if ($properties['compress']) {
					$params['jsInline'][$name]['code'] = GeneralUtility::makeInstance(ResourceCompressor::class)->compressJavaScriptSource($properties['code']);
				}
			}
		}
		$params['jsLibs'] = $this->minifyJsFiles($params['jsLibs']);
		$params['jsFiles'] = $this->minifyJsFiles($params['jsFiles']);
		$params['jsFooterFiles'] = $this->minifyJsFiles($params['jsFooterFiles']);

		$params['jsLibs'] = $this->getCompressor()->compressJsFiles($params['jsLibs']);
		$params['jsFiles'] = $this->getCompressor()->compressJsFiles($params['jsFiles']);
		$params['jsFooterFiles'] = $this->getCompressor()->compressJsFiles($params['jsFooterFiles']);

		foreach ($params['jsFiles'] as $key => $properties) {
			// do not use $properties. it may contain a gzipped file
			$file = $key;
			if (!file_exists($file)) {
				continue;
			}
			$size = filesize($file);

			if ($size > 2000) {
				continue;
			}
			// the file is smaller than 2000 byte. inline it for better performance (will use one less request)
			unset($params['jsFiles'][$key]);

			$content = (string)file_get_contents($file);
			if (substr($file, -5) === '.gzip') {
				$content = (string)gzdecode($content);
			}

			if (preg_match('-\n//# sourceMappingURL=([^\n]*)$-s', $content, $matches)) {
				// adjust sourceMappingURL, if present
				$map = dirname($file) . '/' . $matches[1];
				if (file_exists($map)) {
					$replace = substr($matches[0], 0, -strlen($matches[1])) . $map;
				} else {
					$replace = '';
				}

				$content = (string)preg_replace('-\n//# sourceMappingURL=([^\n]*)$-s', $replace, $content);
			}

			if ($properties['section'] == self::PART_FOOTER) {
				$pageRenderer->addJsFooterInlineCode($file, $content, false);
			} else {
				$pageRenderer->addJsInlineCode($file, $content, false);
			}
		}
	}

	protected function getCompressor(): ResourceCompressor {
		if ($this->compressor === null) {
			$this->compressor = GeneralUtility::makeInstance(ResourceCompressor::class);
		}
		return $this->compressor;
	}

}
