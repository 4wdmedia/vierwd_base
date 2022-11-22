<?php

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Image;

use TYPO3\CMS\Install\Configuration\Image\CustomPreset;
use TYPO3\CMS\Install\Configuration\Image\ImageFeature as ParentImageFeature;

class ImageFeature extends ParentImageFeature {

	// no detection
	protected $presetRegistry = [
		CustomPreset::class,
	];

}
