<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Mail;

use TYPO3\CMS\Install\Configuration\Mail\CustomPreset;
use TYPO3\CMS\Install\Configuration\Mail\MailFeature as ParentMailFeature;

class MailFeature extends ParentMailFeature {

	/**
	 * disable detection
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
	 */
	protected $presetRegistry = [
		CustomPreset::class,
	];

}
