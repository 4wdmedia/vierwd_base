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
		return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
	}
}
