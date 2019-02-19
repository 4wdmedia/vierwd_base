<?php

return [
	'frontend' => [
		'vierwd/base/404-before-site-redirect' => [
			'target' => \Vierwd\VierwdBase\Frontend\Middleware\PageNotFoundBeforeSiteRedirect::class,
			'before' => [
				'typo3/cms-frontend/base-redirect-resolver',
			],
		],
	],
];