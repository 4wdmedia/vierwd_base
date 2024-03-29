<?php

return [
	'ctrl' => [
		'title'	=> 'Hyphenation',
		'label' => 'hyphenation',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'dividers2tabs' => true,
		'searchFields' => 'hyphenation',
		'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_note.svg',
		'security' => [
			'ignorePageTypeRestriction' => true,
		],
	],
	'types' => [
		'1' => ['showitem' => 'hyphenation'],
	],
	'palettes' => [
		'1' => ['showitem' => ''],
	],
	'columns' => [
		'hyphenation' => [
			'exclude' => 0,
			'label' => 'Hyphenation',
			'config' => [
				'type' => 'text',
				'cols' => 30,
				'rows' => 20,
				'eval' => 'trim'
			],
		],
	],
];
