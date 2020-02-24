<?php

return [
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
	],
];
