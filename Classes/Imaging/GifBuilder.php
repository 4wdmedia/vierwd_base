<?php

namespace Vierwd\VierwdBase\Imaging;

/**
 * Overwrite GifBuilder to force progressive jpegs
 */
class GifBuilder extends \TYPO3\CMS\Frontend\Imaging\GifBuilder {

	use GraphicalFunctionsTrait;

	public function __construct() {
		parent::__construct();
		$this->init();
	}

}
