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
			// check if interlace plane and quality is set
			if (strpos($params, '-quality') === false) {
				$params .= ' -quality ' . $this->jpegQuality;
			}

			if (strpos($params, '-interlace') === false) {
				$params .= ' -interlace Plane';
			}
		}

		return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
	}
}
