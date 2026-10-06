<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Safe\file_get_contents;
use function Safe\json_decode;

class BaseUtility {

	public static function getCssVars(string $path = 'default'): array {
		static $cssVars = null;

		if (!$cssVars) {
			if ($path === 'default') {
				$path = 'EXT:' . self::getExtensionName() . '/Resources/Private/Css/vars.json';
			}
			$cssVars = GeneralUtility::getFileAbsFileName($path);
			$cssVars = file_get_contents($cssVars);
			$cssVars = json_decode($cssVars, true);
			if (!is_array($cssVars)) {
				throw new \Exception('Css Vars is not an array', 1788187788);
			}
			if (isset($cssVars['breakpoints'])) {
				$cssVars['breakpoints'] = array_map(intval(...), $cssVars['breakpoints']);
				arsort($cssVars['breakpoints']);
			}
			if (isset($cssVars['containerMaxWidths'])) {
				$cssVars['containerMaxWidths'] = array_map(intval(...), $cssVars['containerMaxWidths']);
				arsort($cssVars['containerMaxWidths']);
			}
		}

		return $cssVars;
	}

	private static function getExtensionName(): string {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['publishExtensionName'])) {
			return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['publishExtensionName'];
		}

		$composerFile = Environment::getProjectPath() . '/composer.json';
		if (!file_exists($composerFile)) {
			throw new \Exception('composer.json not found', 1788187841);
		}

		$composerContents = file_get_contents($composerFile);
		$composerConfig = json_decode($composerContents, true);
		if (!is_array($composerConfig)) {
			throw new \Exception('Could not parse composer.json', 1788187850);
		}

		$extensionName = ArrayUtility::getValueByPath($composerConfig, 'extra/vierwd/extensionName');

		if (!$extensionName || !is_string($extensionName)) {
			throw new \Exception('Could not find extensionName', 1788187854);
		}

		return $extensionName;
	}

}
