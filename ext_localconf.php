<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['EBT\\ExtensionBuilder\\Service\\FileGenerator'] = array(
	'className' => 'Vierwd\\VierwdBase\\ExtensionBuilder\\Service\\FileGenerator',
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\StorageRepository'] = array(
	'className' => 'Vierwd\\VierwdBase\\Resource\\StorageRepository',
);

if (!empty($_SERVER['4WD_CONFIG'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay'][] = 'Vierwd\\VierwdBase\\Hooks\\NoHttps';
}