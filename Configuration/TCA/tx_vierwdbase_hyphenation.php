<?php

return [
	'ctrl' => [
		'title'	=> 'Hyphenation',
		'label' => 'hyphenation',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/mimetypes/mimetypes-x-sys_note.svg',
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
