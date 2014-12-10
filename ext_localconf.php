<?php

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
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'][] = '{project.namespaceEscaped}\\Hooks\\JavascriptOptimization->jsMinify';
$TYPO3_CONF_VARS['FE']['jsCompressHandler'] = '{project.namespaceEscaped}\\Hooks\\JavascriptOptimization->jsCompressHandler';
