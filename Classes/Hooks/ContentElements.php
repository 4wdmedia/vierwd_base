<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

class ContentElements implements SingletonInterface {

	/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
	public $cObj = null;

	/** @var string */
	public static $oldProcFunc;

	/** @var array */
	protected static $groups = ['vierwd' => []];
	/** @var array */
	protected static $groupNames = ['vierwd' => 'FORWARD MEDIA'];

	/** @var array */
	protected static $fceConfiguration = [];

	/** @var array */
	protected static $usedUids = [];

	/**
	 * process the CType and sort custom FCEs into a special group
	 * @param object|null $refObj
	 */
	public function processCType(array $params, $refObj): array {
		if (static::$oldProcFunc) {
			GeneralUtility::callUserFunction(static::$oldProcFunc, $params, $refObj);
		}

		$CTypes = [];
		foreach (self::$groups as $groupKey => $groupCTypes) {
			foreach ($groupCTypes as $CType) {
				$CTypes[$CType] = $groupKey;
			}
		}

		$defaultGroups = [
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.standard' => 'common',
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.lists' => 'lists',
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.menu' => 'menu',
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.special' => 'special',
			'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms' => 'forms',
		];

		$groups = [];
		$currentGroup = '';
		foreach ($params['items'] as $item) {
			if ($item[1] === '--div--') {
				// it's a group
				$groupName = $defaultGroups[$item[0]] ?? $item[0];
				$currentGroup = $groupName;
				continue;
			}

			// convert the name
			$item[0] = $GLOBALS['LANG']->sL($item[0]);

			if (isset($CTypes[$item[1]])) {
				$group = $CTypes[$item[1]];
				$groups[$group][] = $item;
			} else {
				$groups[$currentGroup][] = $item;
			}
		}

		$defaultGroups = array_flip($defaultGroups);
		$items = [];
		foreach ($groups as $groupKey => $elements) {
			$groupName = self::$groupNames[$groupKey] ?? $defaultGroups[$groupKey];
			$items[] = [$groupName, '--div--'];

			if (self::$groupNames[$groupKey]) {
				// Custom group -> sort
				usort($elements, function(array $plugin1, array $plugin2): int {
					return strnatcasecmp($plugin1[0], $plugin2[0]);
				});
			}

			$items = array_merge($items, $elements);
		}

		$params['items'] = $items;

		return $params['items'];
	}

	static public function initializeFCEs(string $extensionKey): void {
		if (isset(self::$fceConfiguration[$extensionKey])) {
			return;
		}

		$baseFceDir = ExtensionManagementUtility::extPath('vierwd_base') . 'Configuration/FCE/';
		$fceDir = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/FCE/';

		$pageTS = '';
		$typoScript = '';

		$defaults = include $baseFceDir . '_defaults.php';
		$additionalDefaults = include $fceDir . '_defaults.php';
		ArrayUtility::mergeRecursiveWithOverrule($defaults, $additionalDefaults);

		// Load all groups
		$groupsFile = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/FCE/_groups.php';
		if (file_exists($groupsFile)) {
			$groups = include $groupsFile;

			self::$groupNames = $groups + self::$groupNames;

			foreach ($groups as $key => $name) {
				$pageTS .= 'mod.wizards.newContentElement.wizardItems.' . $key . ' {' . "\n" .
				'	header = ' . $name . "\n" .
				'	show = *' . "\n" .
				'}' . "\n";
			}
		}

		$FCEs = [];

		// Load configs for FCEs
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fceDir, \FilesystemIterator::SKIP_DOTS)) as $fceConfigFile) {
			if (!$fceConfigFile instanceof \SplFileInfo || $fceConfigFile->isDir() || substr($fceConfigFile->getFilename(), 0, 1) == '_') {
				continue;
			}

			if (substr($fceConfigFile->getFilename(), -4) != '.php') {
				continue;
			}

			$config = include $fceConfigFile->getPathname();
			if (!$config || !is_array($config)) {
				continue;
			}
			$config = $config + $defaults;
			$config['filename'] = $fceConfigFile->getFilename();

			if (empty($config['group'])) {
				$config['group'] = 'vierwd';
			}

			$FCEs[] = $config;
		}

		usort($FCEs, function(array $FCE1, array $FCE2): int {
			return strcasecmp($FCE1['name'], $FCE2['name']);
		});

		// Process FCEs
		foreach ($FCEs as &$config) {
			/** @var array{'filename': string, 'adminOnly': bool, 'group': string|array, 'vendorName': string, 'pluginName': string, 'name': string, 'description': string, 'iconIdentifier': string, 'controllerActions': array, 'nonCacheableActions': array, 'template': string, 'flexform': string, 'tcaType': string, 'fullTCA': string, 'tcaAdditions': string} $config */
			if (!empty($config['pluginName'])) {
				// create a new plugin
				$pluginSignature = strtolower(str_replace('_', '', $extensionKey) . '_' . $config['pluginName']);
				if (empty($config['CType'])) {
					$config['CType'] = $pluginSignature;
				}

				$config['generatePlugin'] = true;
				$config['pluginSignature'] = $pluginSignature;
			}

			if (empty($config['CType'])) {
				throw new \Exception('Missing CType for ' . $config['filename']);
			}

			// update typoscript
			if ($config['template']) {
				$template = $config['template'];

				$templateDir = ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/Templates/';
				if (substr($template, 0, 4) !== 'EXT:' && file_exists($templateDir . $template)) {
					$template = 'EXT:' . $extensionKey . '/Resources/Private/Templates/' . $template;
				}

				$typoScript .= 'tt_content.' . $config['CType'] . ' =< plugin.tx_vierwdsmarty' . "\n";
				$typoScript .= 'tt_content.' . $config['CType'] . '.settings.template = ' . $template . "\n";

				$tcaType = GeneralUtility::trimExplode(',', $config['tcaType']);
				if (in_array('media', $tcaType)) {
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = assets' . "\n";
				} else if (in_array('image', $tcaType)) {
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = image' . "\n";
				}
			}

			if (is_array($config['group'])) {
				foreach ($config['group'] as $group) {
					self::$groups[$group][] = $config['CType'];
				}
			} else {
				self::$groups[$config['group']][] = $config['CType'];
			}
			unset($config);
		}

		$FCEs = self::validateFCEs($extensionKey, $FCEs);

		self::$fceConfiguration[$extensionKey] = [
			'typoScript' => $typoScript,
			'pageTS' => $pageTS,
			'FCEs' => $FCEs,
		];
	}

	/**
	 * validate FCEs and return only the valid FCEs.
	 * If the current request is not a clear cache request, this method will throw an exception if FCEs are invalid
	 */
	static protected function validateFCEs(string $extensionKey, array $FCEs): array {
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionKey)));

		$currentPlugins = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'];
		$FCEs = array_filter($FCEs, function(array $config) use ($extensionKey, &$currentPlugins): bool {
			if ($config['generatePlugin']) {
				if (isset($currentPlugins[$config['pluginName']])) {
					// a plugin with the same name was added before
					self::generateException('Duplicate pluginName for extension ' . $extensionKey . ': ' . $config['pluginName'], 1482331342);
					return false;
				}
				$currentPlugins[$config['pluginName']] = true;

				// TODO: check controller names. Should be valid class names
				// foreach (array_keys($config['controllerActions'] + $config['nonCacheableActions']) as $controllerName) {
				// 	if (!preg_match('/^[A-Z]/', $controllerName)) {
				// 		self::generateException('Controller name does not start with an uppercase letter. Extension ' . $extensionKey . '. Element: ' . $config['CType'], 1548429406);
				// 		return false;
				// 	}
				// }
			}

			$name = $config['name'];
			if (!$name) {
				self::generateException('Missing FCE name for ' . $config['filename'], 1603988427);
				return false;
			}

			$tcaType = GeneralUtility::trimExplode(',', $config['tcaType']);
			if (in_array('image', $tcaType) && in_array('media', $tcaType)) {
				self::generateException('You can only choose either media or image as tcaType, but not both', 1491296754);
				return false;
			}

			return true;
		});

		return $FCEs;
	}

	/**
	 * If the current request is not a clear cache request, this method will throw an exception
	 */
	static protected function generateException(string $message, int $code): void {
		if (!isset($_GET['cacheCmd']) || $_GET['cacheCmd'] !== 'all') {
			throw new \Exception($message, $code);
		}
	}

	/**
	 * add Content Elements
	 *
	 * @param string $extensionKey
	 */
	static public function addFCEs(string $extensionKey, bool $isLocalConf = false): void {
		self::initializeFCEs($extensionKey);

		$typoScript = self::$fceConfiguration[$extensionKey]['typoScript'];
		$pageTS = self::$fceConfiguration[$extensionKey]['pageTS'];

		foreach (self::$fceConfiguration[$extensionKey]['FCEs'] as $config) {
			if ($config['generatePlugin'] && $isLocalConf) {
				ExtensionUtility::configurePlugin(
					$config['vendorName'] . '.' . $extensionKey,
					$config['pluginName'],
					$config['controllerActions'],
					$config['nonCacheableActions'],
					ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
				);

				if ($config['pluginSignature'] != $config['CType']) {
					// Copy from generated plugin without lib.stdheader
					$typoScript .= 'tmp < tt_content.' . $config['pluginSignature'] . ".20\n" .
						'tt_content.' . $config['CType'] . " < tmp\n" .
						"tmp >\n" .
						'tt_content.' . $config['pluginSignature'] . " >\n";
				} else {
					$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.' . $config['pluginSignature'] . ".20\n";
				}

				$tcaType = GeneralUtility::trimExplode(',', $config['tcaType']);
				if (in_array('media', $tcaType)) {
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = assets' . "\n";
				} else if (in_array('image', $tcaType)) {
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
					$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = image' . "\n";
				}
			}

			if (!$isLocalConf) {
				// ext_tables

				$name = $config['name'];

				ExtensionManagementUtility::addPlugin([$name, $config['CType'], $config['iconIdentifier']], 'CType', $extensionKey);
				$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$config['CType']] = $config['iconIdentifier'];
				if ($config['adminOnly'] && is_array($GLOBALS['TCA']['tt_content']['columns'])) {
					$last = array_pop($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
					$last['adminOnly'] = true;
					$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][] = $last;
				}

				if ($config['flexform']) {
					if (substr($config['flexform'], 0, 5) !== 'FILE:') {
						$config['flexform'] = 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/' . $config['flexform'];
					}
					ExtensionManagementUtility::addPiFlexFormValue('*', $config['flexform'], $config['CType']);
				}

				if (is_array($config['group'])) {
					foreach ($config['group'] as $group) {
						$pageTS .=
						'mod.wizards.newContentElement.wizardItems.' . $group . '.elements.' . $config['CType'] . ' {' . "\n" .
						'	iconIdentifier = ' . $config['iconIdentifier'] . "\n" .
						'	title = ' . $name . "\n" .
						'	description = ' . $config['description'] . "\n" .
						'	tt_content_defValues {' . "\n" .
						'		CType = ' . $config['CType'] . "\n" .
						'	}' . "\n" .
						'}' . "\n";
					}
				} else {
					$pageTS .=
					'mod.wizards.newContentElement.wizardItems.' . $config['group'] . '.elements.' . $config['CType'] . ' {' . "\n" .
					'	iconIdentifier = ' . $config['iconIdentifier'] . "\n" .
					'	title = ' . $name . "\n" .
					'	description = ' . $config['description'] . "\n" .
					'	tt_content_defValues {' . "\n" .
					'		CType = ' . $config['CType'] . "\n" .
					'	}' . "\n" .
					'}' . "\n";
				}
			}
		}

		if ($typoScript && $isLocalConf) {
			ExtensionManagementUtility::addTypoScript($extensionKey, 'setup', $typoScript, 'defaultContentRendering');
		}

		if ($pageTS && !$isLocalConf) {
			ExtensionManagementUtility::addPageTSConfig($pageTS);
		}
	}

	/**
	 * Generate TCA for FCEs.
	 * Gets called in TCA/Overrides/tt_content.php and will be cached.
	 *
	 * @param array $TCA
	 * @return array modified $TCA
	 */
	static public function addTCA(array $TCA): array {
		$GLOBALS['TCA'] = $TCA;
		foreach (self::$fceConfiguration as $extensionKey => $configuration) {
			foreach ($configuration['FCEs'] as $config) {
				$tca = $config['fullTCA'] ?: self::generateTCA($config);

				if (ExtensionManagementUtility::isLoaded('gridelements') && strpos($tca, 'tx_gridelements_container, tx_gridelements_columns') === false) {
					$tca .= ', tx_gridelements_container, tx_gridelements_columns';
				}

				$GLOBALS['TCA']['tt_content']['types'][$config['CType']]['showitem'] = $tca;
				if (in_array('richtext', GeneralUtility::trimExplode(',', $config['tcaType']))) {
					$GLOBALS['TCA']['tt_content']['types'][$config['CType']]['columnsOverrides']['bodytext']['config']['enableRichtext'] = true;
				}

				foreach ($config['tcaAdditions'] as $tcaAddition) {
					$method = array_shift($tcaAddition);
					if ($method == 'addToAllTCAtypes') {
						ExtensionManagementUtility::addToAllTCAtypes('tt_content', $tcaAddition[0], $tcaAddition[1], $tcaAddition[2]);
					}
				}

				self::validateTCA($tca);
			}
		}

		return [$GLOBALS['TCA']];
	}

	static protected function validateTCA(string $tca): void {
		$fields = GeneralUtility::trimExplode(',', $tca, true);
		foreach ($fields as $fieldString) {
			$fieldArray = GeneralUtility::trimExplode(';', $fieldString);
			$fieldArray = [
				'fieldName' => isset($fieldArray[0]) ? $fieldArray[0] : '',
				'fieldLabel' => isset($fieldArray[1]) ? $fieldArray[1] : null,
				'paletteName' => isset($fieldArray[2]) ? $fieldArray[2] : null,
			];
			if ($fieldArray['fieldName'] === '--palette--' && $fieldArray['paletteName'] !== null) {
				if (!isset($GLOBALS['TCA']['tt_content']['palettes'][$fieldArray['paletteName']])) {
					throw new \Exception('Missing palette: ' . $fieldArray['paletteName'], 1531385089);
				}
			}
		}
	}

	static public function generateTCA(array $config): string {
		$tcaType = GeneralUtility::trimExplode(',', $config['tcaType']);

		// bodytext,richtext,image,fullheaders
		if (in_array('fullheaders', $tcaType)) {
			$header = '--palette--;;headers,';
		} else if (in_array('simpleheaders', $tcaType)) {
			$header = '--palette--;;header,';
		} else {
			$header = 'header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.div_formlabel,';
		}

		$bodytext = '';
		if (in_array('bodytext', $tcaType) || in_array('richtext', $tcaType)) {
			$bodytext = 'bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,';
		}

		if (in_array('media', $tcaType)) {
			$image = '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.media,
				assets,
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks;imagelinks,';
		} else if (in_array('image', $tcaType)) {
			$image = '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
				image,
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks;imagelinks,';
		} else {
			$image = '';
		}
		if ($config['flexform']) {
			$flexform = 'pi_flexform,';
		} else {
			$flexform = '';
		}

		$tca = '
			--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
				--palette--;;general,
				' . $header . '
				' . $bodytext . '
				' . $flexform . '
				' . $image . '
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
				--palette--;;frames,
				--palette--;;appearanceLinks,
			--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
				--palette--;;language,
			--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
				--palette--;;hidden,
				--palette--;;access,
			--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
				rowDescription,
			--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,';

		return $tca;
	}

	/**
	 * add id of content element to first HTML Element.
	 * This enables direct links to elements.
	 * Normally TYPO3 would add those links with a link (<a id="cXX"></a>) or in the default wrapper,
	 * but this would interfere with :first-child pseudo elements
	 */
	public function elementUid(string $content, array $params): string {
		if (!$content || $content[0] != '<' || $this->cObj === null || !$this->cObj->data['uid']) {
			return $content;
		}

		if ($GLOBALS['TSFE']->config['config']['tx_vierwd.']['disableElementId']) {
			return $content;
		}

		$additionalIdTag = '';
		$useAdditionalId = !empty($this->cObj->data['_LOCALIZED_UID']) && $this->cObj->data['_LOCALIZED_UID'] != $this->cObj->data['uid'];
		if ($useAdditionalId) {
			$additionalId = 'c' . $this->cObj->data['_LOCALIZED_UID'];
			$additionalIdAttr = ' id="' . $additionalId . '"';
			if (strpos($content, $additionalIdAttr) === false && $GLOBALS['TSFE']->config['config']['tx_vierwd.']['enableL10nAnchor']) {
				self::$usedUids[$additionalId] = true;
				$additionalIdTag = '<a' . $additionalIdAttr . '></a>';
			} else {
				$additionalIdTag = '';
			}
		}

		// add uid to first element
		$id = 'c' . $this->cObj->data['uid'];
		if (isset(self::$usedUids[$id])) {
			if (isset($this->cObj->data['parentData'], $this->cObj->data['parentData']['uid'])) {
				// this element is a reference. Make sure the ID does not appear twice on this page
				$id = 'c' . $this->cObj->data['uid'] . '-' . $this->cObj->data['parentData']['uid'];
			}
		}
		$idAttr = ' id="' . $id . '"';
		self::$usedUids[$id] = true;

		if (strpos($content, $idAttr) !== false) {
			return $additionalIdTag . $content;
		}

		// no-cache elements (COA_INT and USER_INT are marked with <!--INT_SCRIPT.MD5-HASH--> and replaced later)
		// if the current content starts with a no-cache element, we cannot add the id to this element
		// Solution: Wrap the cache-marker
		$isINTIncScript = substr($content, 0, strlen('<!--INT_SCRIPT.')) === '<!--INT_SCRIPT.';
		if ($isINTIncScript) {
			return $additionalIdTag . '<div' . $idAttr . '>' . $content . '</div>';
		}

		if (preg_match('/^<[^>]*\s+id=[^>]*>/', $content) || substr($content, 0, strlen('<!--')) === '<!--') {
			// id already present or comment -> add anchor before the element
			return $additionalIdTag . '<a' . $idAttr . '></a>' . $content;
		}

		return $additionalIdTag . preg_replace('/<(?!\\/)([^\s>!]+)/', '<$1' . $idAttr, $content, 1);
	}
}
