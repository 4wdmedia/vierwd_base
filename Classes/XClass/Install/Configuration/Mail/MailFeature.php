<?php

namespace Vierwd\VierwdBase\XClass\Install\Configuration\Mail;

use TYPO3\CMS\Install\Configuration\Mail\CustomPreset;
use TYPO3\CMS\Install\Configuration\Mail\MailFeature as ParentMailFeature;

class MailFeature extends ParentMailFeature {

	// no detection
	protected $presetRegistry = [
		CustomPreset::class,
	];

}
