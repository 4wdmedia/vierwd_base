<?php
defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'] = ['paths' => []];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['EBT\\ExtensionBuilder\\Service\\FileGenerator'] = [
	'className' => 'Vierwd\\VierwdBase\\ExtensionBuilder\\Service\\FileGenerator',
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['EBT\\ExtensionBuilder\\Service\\Printer'] = [
	'className' => 'Vierwd\\VierwdBase\\ExtensionBuilder\\Service\\Printer',
];

// *****************************
// disable https on dev-machines
if (!empty($_SERVER['4WD_CONFIG'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay'][] = 'Vierwd\\VierwdBase\\Hooks\\NoHttps';
}

// ***************
// minify JS hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'][] = 'Vierwd\\VierwdBase\\Hooks\\JavascriptOptimization->jsMinify';
$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = 'Vierwd\\VierwdBase\\Hooks\\JavascriptOptimization->jsCompressHandler';

// ***************
// 404-PageNotFoundHandling
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = 'USER_FUNCTION:Vierwd\\VierwdBase\\Hooks\\PageNotFoundHandler->pageNotFound';
// Disable lockIP, if the server is requesting the 404-page
$GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'] = $_SERVER['SERVER_ADDR'] && $_SERVER['SERVER_ADDR'] == \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') ? false : $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'];

// ***************
// BackendLayoutDataProvider
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['vierwd_base'] = \Vierwd\VierwdBase\Backend\BackendLayoutDataProvider::class;

// ***************
// eID for TYPO3 Version
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['vierwd_typo3version'] = 'Vierwd\\VierwdBase\\Controller\\Typo3Version::main';

// disable html_errors, if this is an ajax request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	ini_set('html_errors', 'off');
}

// **************
// Add cache-buster to all publicURLs
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl, 'Vierwd\\VierwdBase\\Resource\\CacheBuster', 'getPublicUrl');

// **************
// Check for duplicte files after upload
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd, 'Vierwd\\VierwdBase\\Resource\\DuplicateFiles', 'checkForDuplicateFiles');

// **************
// Replace encoded mail addresses during indexing
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('indexed_search')) {
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = [];
	}
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = array_filter($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'], function($class) {
		return $class !== 'TYPO3\\CMS\\IndexedSearch\\Indexer';
	});
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'Vierwd\\VierwdBase\\Hooks\\Indexer';
}

// ****************
// Force progressive jpegs
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions'] = [
	'className' => 'Vierwd\\VierwdBase\\Imaging\\GraphicalFunctions',
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder'] = [
	'className' => 'Vierwd\\VierwdBase\\Imaging\\GifBuilder',
];

// ****************
// X-Class for SVGContentObject
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject'] = [
	'className' => 'Vierwd\\VierwdBase\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject',
];

// ****************
// Enable Browser Sync as Post-Processing (better performance than USER_INT)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['vierwd_base-browserSync'] = 'Vierwd\\VierwdBase\\Hooks\\BrowserSync->enable';

// **************
// Filter files/folders
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'][] = ['Vierwd\\VierwdBase\\Resource\\FilterFiles', 'filterFilesCallback'];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver'] = [
	'className' => 'Vierwd\\VierwdBase\\Resource\\LocalDriver',
];

// **************
// Enable error handler
$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'] = 'Vierwd\\VierwdBase\\Error\\ErrorHandler';

// **************
// Automatically set BaseUrl
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][] = 'Vierwd\\VierwdBase\\Hooks\\BaseURL->setLocalBaseUrl';

// **************
// Allow Shift-Reload even without admin login in local context
if (!empty($_SERVER['4WD_CONFIG']) && !empty($_SERVER['HTTP_CACHE_CONTROL']) && strtolower($_SERVER['HTTP_CACHE_CONTROL']) === 'no-cache') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'][] = function($params) {
		$params['disableAcquireCacheData'] = true;
	};
}

// **************
// Make save-and-close the default action
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][] = 'Vierwd\\VierwdBase\\Backend\\GetButtonsHook->adjustSaveAndClose';

// **************
// Improve handling of parallel requests
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'][] = 'Vierwd\\VierwdBase\\Frontend\\PageLoadedFromCache->stallTempPage';

// **************
// Fix incorrect umlauts during editing of data
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'Vierwd\\VierwdBase\\Hooks\\DataHandler';

// **************
// Hyphenate words in generated html
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['tx_vierwd'] = 'Vierwd\\VierwdBase\\Hooks\\Utility->postProcessHTML';

// **************
// Expired Preview links throw an exception. The only solution is to delete the cookie
// This is really user-unfriendly
// @see https://forge.typo3.org/issues/18761
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'] = [];
	}
	array_unshift($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'], 'Vierwd\\VierwdBase\\Hooks\\VersioningHooks->removeExpiredPreviewCookie');
}

// *****************
// Add TCA from FCEs
$signalSlotDispatcher->connect(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'tcaIsBeingBuilt', 'Vierwd\\VierwdBase\\Hooks\\ContentElements', 'addTCA');

// *****************
// Warn when no editor has access to edit some content elements
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][] = 'Vierwd\\VierwdBase\\Hooks\\CheckBackendGroups';

// Database connection with correct utf8mb4 charset
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\Schema\\ConnectionMigrator'] = [
	'className' => 'Vierwd\\VierwdBase\\Database\\Schema\\ConnectionMigrator',
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Vierwd\VierwdBase\Form\FormDataProvider\RichtextConfiguration::class] = [
	'depends' => [
		\TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
	],
];

// ***************
// Add custom content Elements. Configure in Configuration/FCE/*.php
\Vierwd\VierwdBase\Hooks\ContentElements::addFCEs('vierwd_base', true);

$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vierwd_base']);
if (!empty($extConf['forceMyISAM'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\Schema\\SqlReader'] = [
		'className' => 'Vierwd\\VierwdBase\\Database\\ForceMyISAM',
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
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Frontend\\Page\\PageGenerator']['generateMetaTags'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Frontend\\Page\\PageGenerator']['generateMetaTags'] = array_filter($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Frontend\\Page\\PageGenerator']['generateMetaTags'], function($method) {
		return !in_array($method, ['TYPO3\\CMS\\Seo\\HrefLang\\HrefLangGenerator->generate', 'TYPO3\\CMS\\Seo\\Canonical\\CanonicalGenerator->generate']);
	});
}
