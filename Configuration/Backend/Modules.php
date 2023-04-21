<?php
declare(strict_types = 1);

use Vierwd\VierwdBase\Controller\TranslationStatusController;

return [
	'tools_VierwdBaseTranslationStatus' => [
		'parent' => 'tools',
		// 'position' => ['after' => 'web_info'],
		'access' => 'admin',
		'workspaces' => 'live',
		'iconIdentifier' => 'vierwd-translation-status',
		'path' => '/module/tools/VierwdBaseTranslationStatus',
		'labels' => 'LLL:EXT:vierwd_base/Resources/Private/Language/locallang_mod.xlf',
		'extensionName' => 'VierwdBase',
		'controllerActions' => [
			TranslationStatusController::class => [
				'index',
				'export',
			],
		],
	],
];
