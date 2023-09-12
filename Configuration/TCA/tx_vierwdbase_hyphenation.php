<?php

return [
	'ctrl' => [
		'title'	=> 'Hyphenation',
		'label' => 'hyphenation',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'searchFields' => 'hyphenation',
		'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_note.svg',
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
