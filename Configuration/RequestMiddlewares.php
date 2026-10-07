<?php

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$vierwdMiddlewares = [
	'frontend' => [
		'vierwd/base/404-before-site-redirect' => [
			'target' => \Vierwd\VierwdBase\Frontend\Middleware\PageNotFoundBeforeSiteRedirect::class,
			'before' => [
				'typo3/cms-frontend/base-redirect-resolver',
			],
		],
		'vierwd/base/language-get-var' => [
			'target' => \Vierwd\VierwdBase\Frontend\Middleware\LanguageGetVar::class,
			'after' => [
				'typo3/cms-frontend/site',
			],
			'before' => [
				'vierwd/base/404-before-site-redirect',
				'typo3/cms-frontend/page-resolver',
			],
		],
		'vierwd/base/typo3-version' => [
			'target' => \Vierwd\VierwdBase\Frontend\Middleware\Typo3Version::class,
			'before' => [
				'typo3/cms-frontend/eid',
			],
		],
	],
];

$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
if (!$extConf['cachedPostprocessing']) {
	$vierwdMiddlewares['frontend']['vierwd/base/postprocess-html'] = [
		'target' => \Vierwd\VierwdBase\Frontend\PostProcessHTML::class,
		'after' => [
			'typo3/cms-frontend/prepare-tsfe-rendering',
		],
		'before' => [
			'typo3/cms-frontend/content-length-headers',
		],
	];
}

if (!empty($_SERVER['VIERWD_CONFIG'])) {
	$vierwdMiddlewares['frontend']['vierwd/base/browser-sync'] = [
		'target' => \Vierwd\VierwdBase\Frontend\Middleware\BrowserSync::class,
		'after' => [
			'typo3/cms-frontend/content-length-headers',
		],
		'before' => [
			'typo3/cms-frontend/output-compression',
		],
	];
}

return $vierwdMiddlewares;
