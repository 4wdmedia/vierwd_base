<?php
defined('TYPO3') || die();

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
	// remove some reports to prevent unneeded report-mails
	$reportProviders =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'];

	if (is_array($reportProviders['fal'])) {
		// FAL Status reports missing files. Currently there is no possibility to remove those marked-as-missing files:
		// https://forge.typo3.org/issues/59312
		// Solution: Disable Report
		$reportProviders['fal'] = array_filter($reportProviders['fal'], function($class) {
			return $class != \TYPO3\CMS\Reports\Report\Status\FalStatus::class;
		});
	}

	if (is_array($reportProviders['system'])) {
		// System Status reports some php settings as warnings which we cannot change.
		// Examples include max_execution_time and xdebug.max_nesting_level
		$reportProviders['system'] = array_filter($reportProviders['system'], function($class) {
			return $class != \TYPO3\CMS\Install\Report\EnvironmentStatusReport::class;
		});
	}

	unset($reportProviders);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_vierwdbase_hyphenation');

\Vierwd\VierwdBase\Hooks\ContentElements::addFCEs('vierwd_base');

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'VierwdBase',
		'tools', // Make module a submodule of 'tools'
		'translationstate', // Submodule key
		'', // Position
		[
			\Vierwd\VierwdBase\Controller\TranslationStatusController::class => 'index,export',
		],
		[
			'access' => 'admin',
			'icon'   => 'EXT:vierwd_base/Resources/Public/Icons/translation-status.svg',
			'labels' => 'LLL:EXT:vierwd_base/Resources/Private/Language/locallang_mod.xlf',
			'name'   => 'tools_TranslationStatus',
		]
	);
}
