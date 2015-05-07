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
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class which handles JavascriptOptimizations.
 *
 * Javascript will be minified via JSMin, if it is included via page.includeJS*.
 * Javascript files will be inlined if they are smaller than 2000bytes.
 * Additional TypoScript config enabled: config.compressJs = 1 and config.concatenateJs = 1
 *
 * @package vierwd_base
 */
class JavascriptOptimization {
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
	public function jsMinify($params) {
		if (empty($params['script'])) {
			return '';
		}
		// autoloaded via composer
		return \JSMin::minify($params['script']);
	}

	public function minifyJsFiles(array $jsFiles) {
		$filesAfterMinification = array();
		foreach ($jsFiles as $fileName => $fileOptions) {
			// If compression is enabled
			if ($fileOptions['compress']) {
				$compressedFilename = $this->minifyJsFile($fileOptions['file']);
				$fileOptions['compress'] = FALSE;
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
	public function minifyJsFile($filename) {
		// generate the unique name of the file
		$filenameAbsolute = PATH_site . $filename;
		if (@file_exists($filenameAbsolute)) {
			$fileStatus = stat($filenameAbsolute);
			$unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'] . '-min';
		} else {
			$unique = $filenameAbsolute . '-min';
		}
		$pathinfo = PathUtility::pathinfo($filename);
		$targetFile = 'typo3temp/compressor/' . $pathinfo['filename'] . '-' . md5($unique) . '.js';
		// only create it, if it doesn't exist, yet
		if (!file_exists(PATH_site . $targetFile)) {
			$contents = GeneralUtility::getUrl($filenameAbsolute);
			$contents = GeneralUtility::minifyJavaScript($contents, $error);
			GeneralUtility::writeFile(PATH_site . $targetFile, $contents);
		}
		return $targetFile;
	}

	/**
	 * inline JS which is smaller than 2000 bytes (meaning smaller than one request)
	 */
	public function jsCompressHandler($params, \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer) {
		// Traverse the arrays, compress files
		if (count($params['jsInline'])) {
			foreach ($params['jsInline'] as $name => $properties) {
				if ($properties['compress']) {
					$error = '';
					$params['jsInline'][$name]['code'] = GeneralUtility::minifyJavaScript($properties['code'], $error);
					if ($error) {
						// $pageRenderer->compressError .= 'Error with minify JS Inline Block "' . $name . '": ' . $error . LF;
					}
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

			$content = substr($file, -5) == '.gzip' ? $this->gzdecode(file_get_contents($file)) : file_get_contents($file);

			if (preg_match('-\n//# sourceMappingURL=([^\n]*)$-s', $content, $matches)) {
				// adjust sourceMappingURL, if present
				$map = dirname($file) . '/' . $matches[1];
				if (file_exists($map)) {
					$replace = substr($matches[0], 0, -strlen($matches[1])) . $map;
				} else {
					$replace = '';
				}

				$content = preg_replace('-\n//# sourceMappingURL=([^\n]*)$-s', $replace, $content);
			}

			if ($properties['section'] == $pageRenderer::PART_FOOTER) {
				$pageRenderer->addJsFooterInlineCode($file, $content, false);
			} else {
				$pageRenderer->addJsInlineCode($file, $content, false);
			}
		}
	}

	/**
	 * Returns instance of \TYPO3\CMS\Core\Resource\ResourceCompressor
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceCompressor
	 */
	protected function getCompressor() {
		if ($this->compressor === NULL) {
			$this->compressor = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceCompressor');
		}
		return $this->compressor;
	}

	/**
	 * PHP 5.3 compatibility. Gzdecode is PHP >= 5.4
	 */
	protected function gzdecode($data) {
		if (function_exists('gzdecode')) {
			return gzdecode($data);
		} else {
			return gzinflate(substr($data, 10, -8));
		}
	}
}
