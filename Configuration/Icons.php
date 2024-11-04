<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Add custom Icons
$path = GeneralUtility::getFileAbsFileName('EXT:vierwd_base/Resources/Public/Icons/');
$icons = [];
foreach (new GlobIterator($path . '*.svg') as $icon) {
	$identifier = 'vierwd-' . $icon->getBasename('.svg');
	$icons[$identifier] = [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:vierwd_base/Resources/Public/Icons/' . $icon->getFilename(),
	];
}

return $icons;
