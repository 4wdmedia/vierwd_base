<?php
declare(strict_types=1);

return [
	'commands' => [
		'vierwd:post-composer' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\PostComposerCommandController::class,
			'controllerCommandName' => 'run',
			'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
		],
		'vierwd:database:export' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\DatabaseCommandController::class,
			'controllerCommandName' => 'export',
			'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
		],
		'vierwd:database:import' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\DatabaseCommandController::class,
			'controllerCommandName' => 'import',
			'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
		],
		'vierwd:database:kb-import' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\DatabaseCommandController::class,
			'controllerCommandName' => 'kbImport',
		],
		'vierwd:database:last-change' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\DatabaseCommandController::class,
			'controllerCommandName' => 'lastChange',
		],
		'vierwd:list-words' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\ListWordsCommandController::class,
			'controllerCommandName' => 'run',
		],

		'vierwd:rsync:down' => [
			'vendor' => 'vierwd',
			'controller' => Vierwd\VierwdBase\Console\Command\RsyncCommandController::class,
			'controllerCommandName' => 'down',
		],
	],
];
