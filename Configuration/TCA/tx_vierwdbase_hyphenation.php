<?php

return array(
	'ctrl' => array(
		'title'	=> 'Hyphenation',
		'label' => 'hyphenation',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'searchFields' => 'hyphenation',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('core') . 'Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_note.svg'
	),
	'interface' => array(
		'showRecordFieldList' => 'hyphenation',
	),
	'types' => array(
		'1' => array('showitem' => 'hyphenation'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
		'hyphenation' => array(
			'exclude' => 0,
			'label' => 'Hyphenation',
			'config' => array(
				'type' => 'text',
				'cols' => 30,
				'rows' => 20,
				'eval' => 'trim'
			),
		),
	),
);