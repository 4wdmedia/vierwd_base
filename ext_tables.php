<?php

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
	// remove some reports to prevent unneeded report-mails
	$reportProviders =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'];

	// FAL Status reports missing files. Currently there is no possibility to remove those marked-as-missing files:
	// https://forge.typo3.org/issues/59312
	// Solution: Disable Report
	$reportProviders['fal'] = array_filter($reportProviders['fal'], function($class) {
		return $class != 'TYPO3\\CMS\\Reports\\Report\\Status\\FalStatus';
	});

	// System Status reports some php settings as warnings which we cannot change.
	// Examples include max_execution_time and xdebug.max_nesting_level
	$reportProviders['system'] = array_filter($reportProviders['system'], function($class) {
		return $class != 'TYPO3\\CMS\\Install\\Report\\EnvironmentStatusReport';
	});

	unset($reportProviders);
}

// Add custom Icons
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$path = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:' . $_EXTKEY . '/Resources/Public/Icons/');
foreach (new \GlobIterator($path . '*.svg') as $icon) {
	$iconRegistry->registerIcon('vierwd-' . $icon->getBasename('.svg'), \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, [
		'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/' . $icon->getFilename(),
	]);
}
