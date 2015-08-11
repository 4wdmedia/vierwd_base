<?php

// Header is a required field
$GLOBALS['TCA']['tt_content']['columns']['header']['config']['eval'] = 'trim,required';

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
