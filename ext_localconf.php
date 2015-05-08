<?php

if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
	throw new \Exception('Base extension needs at least PHP 5.4');
}
require_once PATH_site . '/vendor/autoload.php';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'] = array('paths' => array());

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['EBT\\ExtensionBuilder\\Service\\FileGenerator'] = array(
	'className' => 'Vierwd\\VierwdBase\\ExtensionBuilder\\Service\\FileGenerator',
);

// ****************
// X-Class for StorageRepository to force case-sensitive FileSystem
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\StorageRepository'] = array(
	'className' => 'Vierwd\\VierwdBase\\Resource\\StorageRepository',
);

// *****************************
// disable https on dev-machines
if (!empty($_SERVER['4WD_CONFIG'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay'][] = 'Vierwd\\VierwdBase\\Hooks\\NoHttps';
}

// ***************
// clear all caches
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'Vierwd\\VierwdBase\\Hooks\\ClearCache';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'Vierwd\\VierwdBase\\Hooks\\ClearCache->clear_cacheCmd';

// ***************
// minify JS hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'][] = 'Vierwd\\VierwdBase\\Hooks\\JavascriptOptimization->jsMinify';
$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = 'Vierwd\\VierwdBase\\Hooks\\JavascriptOptimization->jsCompressHandler';

// ***************
// 404-PageNotFoundHandling
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = 'USER_FUNCTION:Vierwd\\VierwdBase\\Hooks\\PageNotFound->pageNotFound';
// Disable lockIP, if the server is requesting the 404-page
$GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'] = $_SERVER['SERVER_ADDR'] && $_SERVER['SERVER_ADDR'] == \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') ? false : $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'];

// ***************
// Check in Reports, if newest TYPO3 Version is used
$TYPO3_CONF_VARS['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['vierwd'] = array(
	'Vierwd\\VierwdBase\\Reports\\LatestTYPO3',
);

// ***************
// BackendLayoutDataProvider
$TYPO3_CONF_VARS['SC_OPTIONS']['BackendLayoutDataProvider'][$_EXTKEY] = 'Vierwd\\VierwdBase\\Backend\\BackendLayoutDataProvider';

// ***************
// eID for TYPO3 Version
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['vierwd_typo3version'] = 'EXT:' . $_EXTKEY . '/typo3version.php';

// **************
// Add Error command controller
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Vierwd\\VierwdBase\\Command\\CronjobCommandController';

// disable html_errors, if this is an ajax request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	ini_set('html_errors', 'off');
}

// **************
// Add cache-buster to all publicURLs
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl, 'Vierwd\\VierwdBase\\Resource\\CacheBuster', 'getPublicUrl');

// **************
// Replace encoded mail addresses during indexing
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('indexed_search')) {
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = array();
	}
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] = array_filter($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'], function($class) {
		return $class !== 'TYPO3\\CMS\\IndexedSearch\\Indexer';
	});
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'Vierwd\\VierwdBase\\Hooks\\Indexer';
}

// ****************
// Force progressive jpegs
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions'] = array(
	'className' => 'Vierwd\\VierwdBase\\Imaging\\GraphicalFunctions',
);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder'] = array(
	'className' => 'Vierwd\\VierwdBase\\Imaging\\GifBuilder',
);

// ****************
// X-Class for SVGContentObject
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject'] = array(
	'className' => 'Vierwd\\VierwdBase\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject',
);
