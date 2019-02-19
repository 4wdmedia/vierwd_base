<?php

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Context;

use TYPO3\CMS\Install\Configuration\Context\ContextFeature as ParentContextFeature;
use TYPO3\CMS\Install\Configuration\Context\CustomPreset;

class ContextFeature extends ParentContextFeature {

	// no detection
	protected $presetRegistry = [
		CustomPreset::class,
	];
}
