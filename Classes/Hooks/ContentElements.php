<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry as ContainerRegistry;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentElements implements SingletonInterface {

	protected ?ContentObjectRenderer $cObj = null;

	protected static array $groups = ['vierwd' => []];
	protected static array $groupNames = ['vierwd' => 'FORWARD MEDIA'];

	protected static array $fceConfiguration = [];
	public static array $pageTS = [];

	protected static array $usedUids = [];

	public function setContentObjectRenderer(ContentObjectRenderer $cObj): void {
		$this->cObj = $cObj;
	}

	/**
	 * Add TCA from FCEs
	 */
	public function __invoke(AfterTcaCompilationEvent $event): void {
		$GLOBALS['TCA'] = $event->getTca();
		self::addTCA($GLOBALS['TCA']);
		$event->setTca($GLOBALS['TCA']);
	}

	static public function initializeFCEs(string $extensionKey): void {
		if (isset(self::$fceConfiguration[$extensionKey])) {
			return;
		}

		$extensionPath = ExtensionManagementUtility::extPath($extensionKey);

		$baseFceDir = ExtensionManagementUtility::extPath('vierwd_base') . 'Configuration/FCE/';
		$fceDir = $extensionPath . 'Configuration/FCE/';

		$pageTS = '';
		$typoScript = '';

		$defaults = include $baseFceDir . '_defaults.php';
		$additionalDefaults = include $fceDir . '_defaults.php';
		ArrayUtility::mergeRecursiveWithOverrule($defaults, $additionalDefaults);

		// Load all groups
		$groupsFile = $extensionPath . 'Configuration/FCE/_groups.php';
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
			$config += $defaults;
			assert(is_array($config));
			$config['filename'] = $fceConfigFile->getFilename();

			if (empty($config['group'])) {
				$config['group'] = 'vierwd';
			}

			$FCEs[] = $config;
		}

		$names = array_column($FCEs, 'name');
		array_multisort($names, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $FCEs);

		// Process FCEs
		/** @var array{'filename': string, 'CType': ?string, 'adminOnly': bool, 'group': string|array, 'pluginName': string, 'name': string, 'description': string, 'containerGrid': array, 'iconIdentifier': string, 'controllerActions': array, 'nonCacheableActions': array, 'template': string, 'flexform': string, 'tcaType': string, 'fullTCA': string, 'tcaAdditions': array|callable, 'typoScript': string|callable|null, 'tsConfig': string|callable|null} $config */
		foreach ($FCEs as &$config) {
			if (!empty($config['pluginName'])) {
				// create a new plugin
				$pluginSignature = strtolower(str_replace('_', '', $extensionKey) . '_' . $config['pluginName']);
				if (empty($config['CType'])) {
					$config['CType'] = $pluginSignature;
				}

				$config['generatePlugin'] = true;

				if ($pluginSignature != $config['CType']) {
					// Copy from generated plugin without lib.stdheader
					$typoScript .= 'tmp < tt_content.' . $pluginSignature . ".20\n" .
						'tt_content.' . $config['CType'] . " < tmp\n" .
						"tmp >\n" .
						'tt_content.' . $pluginSignature . " >\n";
				} else {
					$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.' . $pluginSignature . ".20\n";
				}
			} else {
				$config['generatePlugin'] = false;
			}

			if (empty($config['CType'])) {
				throw new \Exception('Missing CType for ' . $config['filename']);
			}

			// update typoscript
			$tcaType = GeneralUtility::trimExplode(',', $config['tcaType']);
			if (in_array('media', $tcaType)) {
				$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
				$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = assets' . "\n";
			} else if (in_array('image', $tcaType)) {
				$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor' . "\n";
				$typoScript .= 'tt_content.' . $config['CType'] . '.dataProcessing.10.references.fieldName = image' . "\n";
			}

			if ($config['template']) {
				$template = $config['template'];

				$templateDir = $extensionPath . 'Resources/Private/Templates/';
				if (substr($template, 0, 4) !== 'EXT:' && file_exists($templateDir . $template)) {
					$template = 'EXT:' . $extensionKey . '/Resources/Private/Templates/' . $template;
				}

				$typoScript .= 'tt_content.' . $config['CType'] . ' =< plugin.tx_vierwdsmarty' . "\n";
				$typoScript .= 'tt_content.' . $config['CType'] . '.settings.template = ' . $template . "\n";
			}

			if ($config['typoScript'] ?? false) {
				if (is_string($config['typoScript'])) {
					$typoScript .= $config['typoScript'] . "\n";
				} else {
					$typoScript .= call_user_func($config['typoScript'], $config) . "\n";
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

		$currentPlugins = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'] ?? [];
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
	 */
	static public function addFCEs(string $extensionKey, bool $isLocalConf = false): void {
		if (!$isLocalConf) {
			// calling this method in ext_tables.php is not needed anymore.
			// All other methods are called with an event
			trigger_error('Calling ContentElements::addFCEs from ext_tables is deprecated.', E_USER_DEPRECATED);
			return;
		}

		self::initializeFCEs($extensionKey);

		$typoScript = self::$fceConfiguration[$extensionKey]['typoScript'];
		$pageTS = self::$fceConfiguration[$extensionKey]['pageTS'];

		foreach (self::$fceConfiguration[$extensionKey]['FCEs'] as $config) {
			if ($config['generatePlugin']) {
				// FOR USE IN ext_localconf.php FILES
				ExtensionUtility::configurePlugin(
					$extensionKey,
					$config['pluginName'],
					$config['controllerActions'],
					$config['nonCacheableActions'],
					ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
				);
			}

			$name = $config['name'];

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

			if ($config['tsConfig'] ?? false) {
				if (is_string($config['tsConfig'])) {
					$pageTS .= $config['tsConfig'] . "\n";
				} else if (is_callable($config['tsConfig'])) {
					$pageTS .= call_user_func($config['tsConfig'], $config) . "\n";
				}
			}
		}

		if ($typoScript) {
			// FOR USE IN ext_localconf.php FILES
			ExtensionManagementUtility::addTypoScript($extensionKey, 'setup', $typoScript, 'defaultContentRendering');
		}

		if ($pageTS) {
			// FOR USE IN ext_localconf.php FILES
			self::$pageTS[$extensionKey] = $pageTS;
		}
	}

	/**
	 * Generate TCA for FCEs.
	 * Gets called in TCA/Overrides/tt_content.php and will be cached.
	 *
	 * @return array modified $TCA
	 */
	static public function addTCA(array $TCA): array {
		$GLOBALS['TCA'] = $TCA;
		$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'] += self::$groupNames;
		foreach (self::$fceConfiguration as $extensionKey => $configuration) {
			foreach ($configuration['FCEs'] as $config) {
				if ($config['containerGrid']) {
					$containerConfiguration = new ContainerConfiguration($config['CType'], $config['name'], $config['description'], $config['containerGrid']);
					$containerConfiguration->setIcon($config['iconIdentifier']);
					GeneralUtility::makeInstance(ContainerRegistry::class)->configureContainer($containerConfiguration);
				}

				$tca = $config['fullTCA'] ?: self::generateTCA($config);

				if (ExtensionManagementUtility::isLoaded('gridelements') && strpos($tca, 'tx_gridelements_container, tx_gridelements_columns') === false) {
					$tca .= ', tx_gridelements_container, tx_gridelements_columns';
				}

				$GLOBALS['TCA']['tt_content']['types'][$config['CType']]['showitem'] = $tca;
				if (in_array('richtext', GeneralUtility::trimExplode(',', $config['tcaType']))) {
					$GLOBALS['TCA']['tt_content']['types'][$config['CType']]['columnsOverrides']['bodytext']['config']['enableRichtext'] = true;
				}

				if (is_array($config['tcaAdditions'])) {
					foreach ($config['tcaAdditions'] as $tcaAddition) {
						$method = array_shift($tcaAddition);
						if ($method == 'addToAllTCAtypes') {
							// FOR USE IN files in Configuration/TCA/Overrides/*.php
							ExtensionManagementUtility::addToAllTCAtypes('tt_content', $tcaAddition[0], $tcaAddition[1], $tcaAddition[2]);
						}
					}
				} else if (is_callable($config['tcaAdditions'])) {
					call_user_func($config['tcaAdditions'], $config);
				}

				$name = $config['name'];

				// FOR USE IN files in Configuration/TCA/Overrides/*.php
				$group = $config['group'] === 'common' ? 'default' : $config['group'];
				if (is_array($group)) {
					$group = current($group);
				}
				ExtensionManagementUtility::addPlugin([$name, $config['CType'], $config['iconIdentifier'], $group], 'CType', $extensionKey);
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
					// FOR USE IN files in Configuration/TCA/Overrides/*.php
					ExtensionManagementUtility::addPiFlexFormValue('*', $config['flexform'], $config['CType']);
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
	public function elementUid(string $content): string {
		if (!$content || $content[0] != '<' || $this->cObj === null || !$this->cObj->data['uid']) {
			return $content;
		}

		if (!empty($GLOBALS['TSFE']->config['config']['tx_vierwd.']['disableElementId'])) {
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

		/** @var string $content */
		$content = preg_replace('/<(?!\\/)([^\s>!]+)/', '<$1' . $idAttr, $content, 1);
		return $additionalIdTag . $content;
	}

	public function addContentElementsToAllowList(string $allowDeny): string {
		$allowDenyValues = GeneralUtility::trimExplode(',', $allowDeny);

		foreach (self::$fceConfiguration as $configuration) {
			foreach ($configuration['FCEs'] as $config) {
				if (empty($config['CType']) || !empty($config['adminOnly'])) {
					continue;
				}

				$allowValue = 'tt_content:CType:' . $config['CType'] . ':ALLOW';
				if (!in_array($allowValue, $allowDenyValues, true)) {
					$allowDenyValues[] = $allowValue;
				}
			}
		}

		return implode(',', $allowDenyValues);
	}

}
