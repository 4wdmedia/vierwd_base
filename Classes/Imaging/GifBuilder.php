<?php

namespace Vierwd\VierwdBase\Imaging;

/**
 * Overwrite GifBuilder to force progressive jpegs
 */
class GifBuilder extends \TYPO3\CMS\Frontend\Imaging\GifBuilder {

	public function init() {
		parent::init();
		$this->cmds['jpg'] .= ' -interlace Plane';
		$this->cmds['jpeg'] = $this->cmds['jpg'];
	}

	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = array(), $mustCreate = TRUE) {
		// mustCreate has another default value
		return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
	}
}
