<?php

// Header is a required field
$GLOBALS['TCA']['tt_content']['columns']['header']['config']['eval'] = 'trim,required';

// Disable file Upload in content-elements
$GLOBALS['TCA']['tt_content']['columns']['image']['config']['appearance']['fileUploadAllowed'] = false;

// add TypoScript wizard
$wizards =& $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards'];
$wizards['t3editorTypoScript'] = [
	'enableByTypeConfig' => '1',
	'icon' => 'wizard_table.gif',
	'module' => [
		'name' => 'wizard_table',
	],
	'params' => [
		'format' => 'ts',
		'style' => 'width:98%; height: 60%;',
	],
	'title' => 't3editor',
	'type' => 'userFunc',
	'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
];

unset($wizards);

// Sort Content Elements
\Vierwd\VierwdBase\Hooks\ContentElements::$oldProcFunc = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'];
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'Vierwd\\VierwdBase\\Hooks\\ContentElements->processCType';

// make sure, the image element also uses the media-field
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'assets', 'image', 'replace:image');

// Section frame
if (empty($GLOBALS['TCA']['tt_content']['columns']['section_frame'])) {
	$GLOBALS['TCA']['tt_content']['columns']['section_frame'] = [
		'exclude' => true,
		'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:section_frame',
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => [['Automatisch', '0']],
		],
	];

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'frames', 'section_frame');
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:gridElements, tx_gridelements_container, tx_gridelements_columns');
}


// Add TCA from FCEs
// we cannot use the signal "tcaIsBeingBuilt" in TYPO3 v10, because it throws an exception.
// Adding TCA here might be a bit too early, if there are extensions which are installed after the base extension which additionally modify the TCA
\Vierwd\VierwdBase\Hooks\ContentElements::addTCA($GLOBALS['TCA']);
