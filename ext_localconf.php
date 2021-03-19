<?php
defined('TYPO3_MODE') || die('Access denied.');

$extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('vierwd_base');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'] = ['paths' => []];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\EBT\ExtensionBuilder\Service\FileGenerator::class] = [
	'className' => \Vierwd\VierwdBase\ExtensionBuilder\Service\FileGenerator::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\EBT\ExtensionBuilder\Service\Printer::class] = [
	'className' => Vierwd\VierwdBase\ExtensionBuilder\Service\Printer::class,
];

// ***************
// minify JS hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'][] = \Vierwd\VierwdBase\Hooks\JavascriptOptimization::class . '->jsMinify';
$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = \Vierwd\VierwdBase\Hooks\JavascriptOptimization::class . '->jsCompressHandler';

// ***************
// 404-PageNotFoundHandling
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = 'USER_FUNCTION:' . \Vierwd\VierwdBase\Hooks\PageNotFoundHandler::class . '->pageNotFound';
// Disable lockIP, if the server is requesting the 404-page
$GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'] = $_SERVER['SERVER_ADDR'] && $_SERVER['SERVER_ADDR'] == \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') ? false : $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'];

// ***************
// BackendLayoutDataProvider
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['vierwd_base'] = \Vierwd\VierwdBase\Backend\BackendLayoutDataProvider::class;

// ***************
// eID for TYPO3 Version
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['vierwd_typo3version'] = \Vierwd\VierwdBase\Controller\Typo3Version::class . '::main';

// disable html_errors, if this is an ajax request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	ini_set('html_errors', 'off');
}

// **************
// Add cache-buster to all publicURLs
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl, \Vierwd\VierwdBase\Resource\CacheBuster::class, 'getPublicUrl');

// **************
// Check for duplicte files after upload
$signalSlotDispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd, \Vierwd\VierwdBase\Resource\DuplicateFiles::class, 'checkForDuplicateFiles');

// **************
// Replace encoded mail addresses during indexing
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('indexed_search')) {
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = [];
	}
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = array_filter($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'], function($class) {
		return $class !== \TYPO3\CMS\IndexedSearch\Indexer::class;
	});
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = \Vierwd\VierwdBase\Hooks\Indexer::class;
}

// ****************
// Force progressive jpegs
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Imaging\GraphicalFunctions::class] = [
	'className' => \Vierwd\VierwdBase\Imaging\GraphicalFunctions::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Frontend\Imaging\GifBuilder::class] = [
	'className' => \Vierwd\VierwdBase\Imaging\GifBuilder::class,
];

// ****************
// X-Class for SVGContentObject
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject::class] = [
	'className' => \Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject::class,
];

// **************
// Filter files/folders
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'][] = [\Vierwd\VierwdBase\Resource\FilterFiles::class, 'filterFilesCallback'];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class] = [
	'className' => \Vierwd\VierwdBase\Resource\LocalDriver::class,
];

// **************
// Make save-and-close the default action
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][] = \Vierwd\VierwdBase\Backend\GetButtonsHook::class . '->adjustSaveAndClose';

// **************
// Improve handling of parallel requests
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'][] = \Vierwd\VierwdBase\Frontend\PageLoadedFromCache::class . '->stallTempPage';

// **************
// Fix incorrect umlauts during editing of data
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Vierwd\VierwdBase\Hooks\DataHandler::class;

// **************
// Hyphenate words in generated html
if ($extConf['cachedPostprocessing']) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['tx_vierwd'] = \Vierwd\VierwdBase\Hooks\Utility::class . '->postProcessHTML';
} else {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['tx_vierwd'] = \Vierwd\VierwdBase\Hooks\Utility::class . '->postProcessHTML';
}

if (version_compare(TYPO3_version, '10.0.0', '<')) {
	// *****************
	// Add TCA from FCEs
	$signalSlotDispatcher->connect(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'tcaIsBeingBuilt', \Vierwd\VierwdBase\Hooks\ContentElements::class, 'addTCA');
}

// *****************
// Warn when no editor has access to edit some content elements
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][] = \Vierwd\VierwdBase\Hooks\CheckBackendGroups::class;

// Database connection with correct utf8mb4 charset
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Schema\ConnectionMigrator::class] = [
	'className' => \Vierwd\VierwdBase\Database\Schema\ConnectionMigrator::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Vierwd\VierwdBase\Form\FormDataProvider\RichtextConfiguration::class] = [
	'depends' => [
		\TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
	],
];

// ***************
// Add custom content Elements. Configure in Configuration/FCE/*.php
\Vierwd\VierwdBase\Hooks\ContentElements::addFCEs('vierwd_base', true);

if (!empty($extConf['forceMyISAM'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Schema\SqlReader::class] = [
		'className' => \Vierwd\VierwdBase\Database\ForceMyISAM::class,
	];
}

// Add Hook for Import/Export which imports the groupid for pages.
// By default groupid for pages would be set to the group of the current user.
// During initial import, our "All users"-group does not exist yet and will be generated
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php']['before_setRelation']['vierwd_base'] = \Vierwd\VierwdBase\Hooks\ImportExport::class . '->before_setRelation';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php']['before_writeRecordsPages']['vierwd_base'] = \Vierwd\VierwdBase\Hooks\ImportExport::class . '->before_writeRecordsPages';

// ***************
// Add HotspotEditor
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1536673649] = [
	'nodeName' => 'vierwdHotspot',
	'priority' => '70',
	'class' => \Vierwd\VierwdBase\Form\Element\HotspotEditorElement::class,
];

// Add gridelement types to PageTsConfig (tt_content.gridtypes.container.section_frame.addItems...)
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Vierwd\VierwdBase\Form\FormDataProvider\GridelementsContainerTypeValue::class] = [
		'depends' => [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
		],
		'before' => [
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
		],
	];

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Vierwd\VierwdBase\Form\FormDataProvider\GridelementsMergeTsConfig::class] = [
		'depends' => [
			\Vierwd\VierwdBase\Form\FormDataProvider\GridelementsContainerTypeValue::class,
			\TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
		],
		'before' => [
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
		],
	];
}

// Remove canonical URL generation and hreflang generation from TYPO3 seo extension.
// The generated links might contain malicous query strings.
// @see https://forge.typo3.org/issues/87105
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Page\PageGenerator::class]['generateMetaTags'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Page\PageGenerator::class]['generateMetaTags'] = array_filter($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Page\PageGenerator::class]['generateMetaTags'], function($method) {
		return !in_array($method, [\TYPO3\CMS\Seo\HrefLang\HrefLangGenerator::class . '->generate', \TYPO3\CMS\Seo\Canonical\CanonicalGenerator::class . '->generate']);
	});
}

if (!empty($_SERVER['VIERWD_CONFIG'])) {
	// *****************************
	// disable https on dev-machines
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay'][] = \Vierwd\VierwdBase\Hooks\NoHttps::class;

	// **************
	// Allow Shift-Reload even without admin login in local context
	if (!empty($_SERVER['HTTP_CACHE_CONTROL']) && strtolower($_SERVER['HTTP_CACHE_CONTROL']) === 'no-cache') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'][] = function($params) {
			$params['disableAcquireCacheData'] = true;
		};
	}

	// Log Deprecations
	\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TYPO3_CONF_VARS'], [
		'LOG' => [
			'TYPO3' => [
				'CMS' => [
					'deprecations' => [
						'writerConfiguration' => [
							\TYPO3\CMS\Core\Log\LogLevel::NOTICE => [
								\Vierwd\VierwdBase\Log\Writer\DeprecationWriter::class => [],
							],
						],
					],
				],
			],
		],
	]);
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['AutomaticSlugPatternMapper'] = \Vierwd\VierwdBase\Routing\Aspect\AutomaticSlugPatternMapper::class;

unset($extConf);
