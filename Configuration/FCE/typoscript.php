<?php

return [
	'CType' => 'vierwd_typoscript',
	'adminOnly' => true,

	'name' => 'TypoScript',
	'description' => 'TypoScript Content Object',

	'template' => 'FCE/Typoscript.tpl',

	'tcaAdditions' => [
		['addToAllTCAtypes', 'bodytext;TypoScript;;nowrap:wizards[t3editorTypoScript]', 'vierwd_typoscript', 'after:header'],
	],
];