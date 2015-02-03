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