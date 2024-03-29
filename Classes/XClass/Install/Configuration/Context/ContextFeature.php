<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Context;

use TYPO3\CMS\Install\Configuration\Context\ContextFeature as ParentContextFeature;
use TYPO3\CMS\Install\Configuration\Context\CustomPreset;

class ContextFeature extends ParentContextFeature {

	/**
	 * disable detection
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
	 */
	protected $presetRegistry = [
		CustomPreset::class,
	];

}
