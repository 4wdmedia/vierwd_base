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
	'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main'
];

unset($wizards);

// Sort Content Elements
\Vierwd\VierwdBase\Hooks\ContentElements::$oldProcFunc = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'];
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'Vierwd\\VierwdBase\\Hooks\\ContentElements->processCType';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames', 'textmedia', 'replace:layout');


// Add content elements
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] = array_merge(
	$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'],
	array(
		'image' => 'mimetypes-x-content-image',
		'text' => 'mimetypes-x-content-text'
	)
);
array_splice(
	$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'],
	2,
	0,
	array(
		array(
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.1',
			'text',
			'content-text'
		),
		array(
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.3',
			'image',
			'content-image'
		)
	)
);

$GLOBALS['TCA']['tt_content']['palettes']['imageblock'] = array(
	'showitem' => '
		imageorient;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel,
		imagecols;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel,
		--linebreak--,
		image_noRows;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_noRows_formlabel,
		imagecaption_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecaption_position_formlabel
	',
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
	'tt_content',
	'image_settings',
	'imagewidth;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth_formlabel,
		imageheight;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight_formlabel,
		imageborder;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder_formlabel,
		--linebreak--,
		image_compression;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_compression_formlabel,
		image_effects;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_effects_formlabel,'
);

// Field arrangement for CE "text"
$GLOBALS['TCA']['tt_content']['types']['text']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides'])) {
	$GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides'] = array();
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext'])) {
	$GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext'] = array();
}
$baseDefaultExtrasOfBodytext = '';
if (!empty($GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'])) {
	$baseDefaultExtrasOfBodytext = $GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'] . ':';
}
$GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'richtext:rte_transform[mode=ts_css]';

// Field arrangement for CE "image"
$GLOBALS['TCA']['tt_content']['types']['image']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
		image,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks;imagelinks,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.image_settings;image_settings,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imageblock;imageblock,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
';


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