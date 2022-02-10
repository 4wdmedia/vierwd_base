<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Image;

use TYPO3\CMS\Install\Configuration\Image\CustomPreset;
use TYPO3\CMS\Install\Configuration\Image\ImageFeature as ParentImageFeature;

class ImageFeature extends ParentImageFeature {

	/**
	 * disable detection
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
	 */
	protected $presetRegistry = [
		CustomPreset::class,
	];
}
