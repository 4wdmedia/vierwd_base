<?php

namespace Vierwd\VierwdBase\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Robert Vock <robert.vock@4wdmedia.de>, FORWARD MEDIA
 *
 *  All rights reserved
 *
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * @package vierwd_base
 */
class ContentElements {

	public static $oldProcFunc;

	protected static $groups = ['vierwd' => []];
	protected static $groupNames = ['vierwd' => 'FORWARD MEDIA'];

	/**
	 * process the CType and sort custom FCEs into a special group
	 */
	public function processCType($params, $refObj) {
		if (static::$oldProcFunc) {
			GeneralUtility::callUserFunction(static::$oldProcFunc, $params, $refObj);
		}

		$CTypes = [];
		foreach (self::$groups as $groupKey => $groupCTypes) {
			foreach ($groupCTypes as $CType) {
				$CTypes[$CType] = $groupKey;
			}
		}

		$groups = array_combine(array_keys(self::$groups), array_fill(0, count(self::$groups), []));

		$params['items'] = array_filter($params['items'], function($element) use (&$groups, $CTypes) {
			$CType = $element[1];

			if (isset($CTypes[$CType])) {
				$groupKey = $CTypes[$CType];
				$groups[$groupKey][] = $element;
				return false;
			}

			return true;
		});

		foreach ($groups as $groupKey => $elements) {
			$params['items'][] = array(self::$groupNames[$groupKey], '--div--');

			usort($elements, function($plugin1, $plugin2) {
				return strnatcasecmp($plugin1[0], $plugin2[0]);
			});

			$params['items'] = array_merge($params['items'], $elements);
		}

		return $params['items'];
	}

	/**
	 * add Content Elements
	 *
	 * @param string $extensionKey
	 * @throws \Exception if the FCE configuration is invalid (missing CType or missing name)
	 */
	static public function addFCEs($extensionKey, $isLocalConf = false) {
		// Groups in planquadrat
		// https://intern.4wdmedia.de/svn/14013_planquadrat/InBearbeitung/Relaunch-Website_1_1/Webseite/typo3/typo3conf/ext/vierwd_planquadrat/Classes/Hooks/ContentElements.php

		$fceDir = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/FCE/';

		$pageTS = '';
		$typoScript = '';

		$defaults = include $fceDir . '_defaults.php';

		$groupsFile = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/FCE/_groups.php';
		if (!$isLocalConf && file_exists($groupsFile)) {
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

		foreach (new \DirectoryIterator($fceDir) as $fceConfigFile) {
			if ($fceConfigFile->isDot() || $fceConfigFile->isDir() || substr($fceConfigFile->getFilename(), 0, 1) == '_') {
				continue;
			}

			if (substr($fceConfigFile->getFilename(), -4) != '.php') {
				continue;
			}

			$config = include $fceConfigFile->getPathname();
			$config = $config + $defaults;
			$config['filename'] = $fceConfigFile->getFilename();

			if (empty($config['group'])) {
				$config['group'] = 'vierwd';
			}

			$FCEs[] = $config;
		}

		usort($FCEs, function($FCE1, $FCE2) {
			return strcasecmp($FCE1['name'], $FCE2['name']);
		});

		foreach ($FCEs as $config) {
			if (!empty($config['pluginName'])) {
				// create a new plugin
				$pluginSignature = strtolower(str_replace('_', '', $extensionKey) . '_' . $config['pluginName']);
				if (empty($config['CType'])) {
					$config['CType'] = $pluginSignature;
				}

				if ($isLocalConf) {
					ExtensionUtility::configurePlugin(
						'Vierwd.' . $extensionKey,
						$config['pluginName'],
						$config['controllerActions'],
						// non-cacheable actions
						$config['nonCacheableActions'],
						ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
					);

					if ($pluginSignature != $config['CType']) {
						// Copy from generated plugin without lib.stdheader
						$typoScript .= 'tmp < tt_content.' . $pluginSignature . ".20\n" .
							'tt_content.' . $config['CType'] . " < tmp\n" .
							"tmp >\n" .
							'tt_content.' . $pluginSignature . " >\n";
					} else {
						$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.' . $pluginSignature . ".20\n";
					}
				}
			}

			if (empty($config['CType'])) {
				throw new \Exception('Missing CType for ' . $config['filename']);
			}

			if (!$isLocalConf) {
				// ext_tables

				$name = $config['name'];
				if (empty($name) && !empty($config['list_type'])) {
					foreach ($GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] as $plugin) {
						if ($plugin[1] == $config['list_type']) {
							$name = $plugin[0];
							break;
						}
					}
				}

				if (!$name) {
					throw new \Exception('Missing FCE name for ' . $config['filename']);
				}

				ExtensionManagementUtility::addPlugin(array($name, $config['CType']), 'CType', $extensionKey);

				if ($config['flexform']) {
					ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/' . $config['flexform'], $config['CType']);
				}

				$pageTS .=
					'mod.wizards.newContentElement.wizardItems.' . $config['group'] . '.elements.' . $config['CType'] . ' {' . "\n" .
					'	iconIdentifier = ' . $config['iconIdentifier'] . "\n" .
					'	title = ' . $name . "\n" .
					'	description = ' . $config['description'] . "\n" .
					'	tt_content_defValues {' . "\n" .
					'		CType = ' . $config['CType'] . "\n" .
					'	}' . "\n" .
					'}' . "\n";

				$tca = $config['fullTCA'] ? $config['fullTCA'] : self::generateTCA($config);

				if (ExtensionManagementUtility::isLoaded('gridelements') && strpos($tca, 'tx_gridelements_container, tx_gridelements_columns') === false) {
					$tca .= ', tx_gridelements_container, tx_gridelements_columns';
				}

				$GLOBALS['TCA']['tt_content']['types'][$config['CType']]['showitem'] = $tca;

				foreach ($config['tcaAdditions'] as $tcaAddition) {
					$method = array_shift($tcaAddition);
					if ($method == 'addToAllTCAtypes') {
						ExtensionManagementUtility::addToAllTCAtypes('tt_content', $tcaAddition[0], $tcaAddition[1], $tcaAddition[2]);
					}
				}
			}

			// update typoscript
			if ($config['list_type']) {
				$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.list.20.' . $config['list_type'] . "\n";
			} else if ($config['template']) {
				$template = $config['template'];

				$templateDir = ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/Templates/';
				if (substr($template, 0, 4) !== 'EXT:' && file_exists($templateDir . $template)) {
					$template = 'EXT:' . $extensionKey . '/Resources/Private/Templates/' . $template;
				}

				$typoScript .= 'tt_content.' . $config['CType'] . ' < plugin.tx_vierwdsmarty' . "\n";
				$typoScript .= 'tt_content.' . $config['CType'] . '.settings.template = ' . $template . "\n";
			}
			foreach ($config['switchableControllerActions'] as $controller => $actions) {
				$i = 1;

				if (!is_array($actions)) {
					$actions = GeneralUtility::trimExplode(',', $actions, true);
				}

				foreach ($actions as $action) {
					$typoScript .= 'tt_content.' . $config['CType'] . '.switchableControllerActions.' . $controller . '.' . $i++ . ' = ' . $action . "\n";
				}
			}

			if ($isLocalConf) {
				self::$groups[$config['group']][] = $config['CType'];
			}
		}

		if ($typoScript && $isLocalConf) {
			ExtensionManagementUtility::addTypoScript($extensionKey, 'setup', $typoScript, 'defaultContentRendering');
		}

		if ($pageTS && !$isLocalConf) {
			ExtensionManagementUtility::addPageTSConfig($pageTS);
		}
	}

	public function generateTCA(array $config) {
		$tcaType = explode(',', $config['tcaType']);

		// bodytext,richtext,image,fullheaders
		if (in_array('fullheaders', $tcaType)) {
			$header = '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,';
		} else if (in_array('simpleheaders', $tcaType)) {
			$header = '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,';
		} else {
			$header = 'header;LLL:EXT:cms/locallang_ttc.xlf:header.ALT.div_formlabel,';
		}

		$bodytext = '';
		if (in_array('bodytext', $tcaType) || in_array('richtext', $tcaType)) {
			$bodytext = 'bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel';

			if (in_array('richtext', $tcaType)) {
				$bodytext .= ';;richtext:rte_transform[flag=rte_enabled|mode=ts_css]';
			}

			$bodytext .= ',';
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
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
				' . $header . '
				' . $bodytext . '
				' . $flexform . '
				' . $image . '
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
				--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended';

		return $tca;
	}

	/**
	 * add id of content element to first HTML Element.
	 * This enables direct links to elements.
	 * Normally TYPO3 would add those links with a link (<a id="cXX"></a>) or in the default wrapper,
	 * but this would interfere with :first-child pseudo elements
	 */
	public function elementUid($content, $params) {
		if (!$content || $content[0] != '<' || !$this->cObj || !$this->cObj->data['uid']) {
			return $content;
		}

		// add uid to first element
		$idAttr = ' id="c' . $this->cObj->data['uid'] . '"';
		if (strpos($content, $idAttr) !== false) {
			return $content;
		}

		// no-cache elements (COA_INT and USER_INT are marked with <!--INT_SCRIPT.MD5-HASH--> and replaced later)
		// if the current content starts with a no-cache element, we cannot add the id to this element
		// Solution: Wrap the cache-marker
		$isINTIncScript = substr($content, 0, strlen('<!--INT_SCRIPT.')) === '<!--INT_SCRIPT.';
		if ($isINTIncScript) {
			return '<div' . $idAttr . '>' . $content . '</div>';
		}

		if (preg_match('/^<[^>]*\s+id=[^>]*>/', $content) || substr($content, 0, strlen('<!--')) === '<!--') {
			// id already present or comment -> add anchor before the element
			return '<a' . $idAttr . '></a>' . $content;
		}

		return preg_replace('/^<([^\s>!]+)/', '<$1' . $idAttr, $content);
	}
}
