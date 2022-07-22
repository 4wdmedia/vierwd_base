<?php

namespace Vierwd\VierwdBase\Imaging;

/**
 * Overwrite GraphicalFunctions to force progressive jpegs
 */
class GraphicalFunctions extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions {

	use GraphicalFunctionsTrait;

	public function __construct() {
		parent::__construct();
		$this->init();
	}

}
