<?php
declare(strict_types=1);

return [
	'vierwd:post-composer' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\PostComposerCommand::class,
		'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
	],
	'vierwd:database:export' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\Database\ExportCommand::class,
		'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
	],
	'vierwd:database:import' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\Database\ImportCommand::class,
		'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
	],
	'vierwd:database:kb-import' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\Database\KbImportCommand::class,
	],
	'vierwd:database:last-change' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\Database\LastChangeCommand::class,
	],
	'vierwd:list-words' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\ListWordsCommand::class,
	],

	'vierwd:rsync:down' => [
		'vendor' => 'vierwd',
		'class' => Vierwd\VierwdBase\Console\Command\RsyncCommand::class,
	],
];
