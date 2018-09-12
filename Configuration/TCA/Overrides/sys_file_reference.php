<?php

// Disable file Upload in content-elements
$GLOBALS['TCA']['sys_file_reference']['columns']['uid_local']['config']['appearance']['fileUploadAllowed'] = false;

$GLOBALS['TCA']['sys_file_reference']['columns']['hotspot'] = [
	'exclude' => 0,
	'label' => 'Hotspot',
	'config' => [
		'type' => 'text',
		'renderType' => 'vierwdHotspot',
	],
];
