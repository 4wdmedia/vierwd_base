<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Imaging;

use TYPO3\CMS\Core\Imaging\ImageMagickFile;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Overwrite GraphicalFunctions to force progressive jpegs
 */
trait GraphicalFunctionsTrait {

	public function init(): void {
		$this->cmds['jpg'] .= ' -interlace Plane';
		$this->cmds['jpeg'] = $this->cmds['jpg'];
		$this->cmds['webp'] = ' -quality 85';
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
	 */
	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = [], $mustCreate = true) {
		// Note: mustCreate has another default value

		$ext = $newExt ?: strtolower(pathinfo($imagefile, PATHINFO_EXTENSION));
		$params = (string)$params;
		if (in_array($ext, ['jpeg', 'jpg'])) {
			$append = '';
			if (preg_match('/\s-font\s*$/', $params, $matches)) {
				// TYPO3 always prepends the parameters before the filename. For some imagemagick commands,
				// the order is important and the filename needs to be infront of the parameters. As it is
				// not possible to remove the filename, we use a hack to ignore the filename: We use -font
				// as last part in the params, so the command looks like this:
				// $file PARAMS -font $file $outputFile
				// If we detect -font as last part of $params, we add quality and interlace before -font.
				$append = $matches[0];
				$params = substr($params, 0, -strlen($append));
			}
			// check if interlace plane and quality is set
			if (strpos($params, '-quality') === false) {
				$params .= ' -quality ' . $this->jpegQuality;
			}

			if (strpos($params, '-interlace') === false) {
				$params .= ' -interlace Plane';
			}

			$params .= $append;
		}
		if ($ext === 'webp') {
			// check if interlace plane and quality is set
			if (strpos($params, '-quality') === false) {
				$params .= ' -quality 85';
			}

			if (strpos($params, '-interlace') === false) {
				$params .= ' -interlace Plane';
			}
		}

		return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
	 */
	public function imageMagickExec($input, $output, $params, $frame = 0) {
		if (!$this->processorEnabled) {
			return '';
		}
		// If addFrameSelection is set in the Install Tool, a frame number is added to
		// select a specific page of the image (by default this will be the first page)
		$frame = $this->addFrameSelection ? (int)$frame : null;
		$inputFile = (string)ImageMagickFile::fromFilePath($input, $frame);
		$outputFile = CommandUtility::escapeShellArgument($output);
		if (strpos($params, '%INPUT%') !== false) {
			$params = str_replace('%INPUT%', $inputFile, $params);
		} else {
			$params .= ' ' . $inputFile;
		}
		if (strpos($params, '%OUTPUT%') !== false) {
			$params = str_replace('%OUTPUT%', $outputFile, $params);
		} else {
			$params .= ' ' . $outputFile;
		}

		$cmd = CommandUtility::imageMagickCommand('convert', $params);
		$this->IM_commands[] = [$output, $cmd];
		$ret = CommandUtility::exec($cmd);
		// Change the permissions of the file
		GeneralUtility::fixPermissions($output);
		return $ret;
	}
}
