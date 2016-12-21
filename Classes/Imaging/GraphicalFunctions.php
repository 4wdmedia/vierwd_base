<?php

namespace Vierwd\VierwdBase\Imaging;

/**
 * Overwrite GraphicalFunctions to force progressive jpegs
 */
class GraphicalFunctions extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions {

	public function init() {
		parent::init();
		$this->cmds['jpg'] .= ' -interlace Plane';
		$this->cmds['jpeg'] = $this->cmds['jpg'];
	}

	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = array(), $mustCreate = TRUE) {
		// Note: mustCreate has another default value

		$ext = $newExt ?: strtolower(pathinfo($imagefile, PATHINFO_EXTENSION));
		if ($params && in_array($ext, ['jpeg', 'jpg'])) {
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

		return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
	}
}
