<?php

namespace Vierwd\VierwdBase\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Robert Vock <robert.vock@4wdmedia.de>, 4WD MEDIA
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

	/**
	 * process the CType and sort custom FCEs into a special group
	 */
	public function process_CType($params, $refObj) {
		if (static::$oldProcFunc) {
			GeneralUtility::callUserFunction(static::$oldProcFunc, $params, $refObj);
		}

		$params['items'][] = array('<%= project.name %>', '--div--');
		$appendKeys = array();
		$append = array();
		foreach ($params['items'] as $key => $data) {
			if (substr($data[1], 0, strlen('<%= project.extensionName %>_')) == '<%= project.extensionName %>_') {
				$appendKeys[] = $key;
				$append[] = $data;
			}
		}

		foreach ($appendKeys as $key) {
			unset($params['items'][$key]);
		}

		usort($append, function($plugin1, $plugin2) {
			return strnatcasecmp($plugin1[0], $plugin2[0]);
		});

		foreach ($append as $data) {
			$params['items'][] = $data;
		}

		return $params['items'];
	}

	/**
	 * add Content Elements
	 *
	 * @param string $extensionKey
	 */
	static public function addFCEs($extensionKey, $isLocalConf = false) {
		global $TCA;

		// Groups in planquadrat
		// https://intern.4wdmedia.de/svn/14013_planquadrat/InBearbeitung/Relaunch-Website_1_1/Webseite/typo3/typo3conf/ext/vierwd_planquadrat/Classes/Hooks/ContentElements.php

		$fceDir = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/FCE/';

		$pageTS = '';
		$typoScript = '';

		$defaults = include $fceDir . '_defaults.php';

		foreach (new \DirectoryIterator($fceDir) as $fceConfigFile) {
			if ($fceConfigFile->isDot() || $fceConfigFile->isDir() || substr($fceConfigFile->getFilename(), 0, 1) == '_') {
				continue;
			}

			if (substr($fceConfigFile->getFilename(), -4) != '.php') {
				continue;
			}

			$config = include $fceConfigFile->getPathname();
			$config = $config + $defaults;

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
							"tt_content." . $config['CType'] . " < tmp\n" .
							"tmp >\n".
							"tt_content." . $pluginSignature . " >\n";
					} else {
						$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.' . $pluginSignature . ".20\n";
					}
				}
			}

			if (empty($config['CType'])) {
				throw new \Exception('Missing CType for ' . $fceConfigFile->getFilename());
			}

			if (!$isLocalConf) {
				// ext_tables

				$name = $config['name'];
				if (empty($name) && !empty($config['list_type'])) {
					foreach ($TCA['tt_content']['columns']['list_type']['config']['items'] as $plugin) {
						if ($plugin[1] == $config['list_type']) {
							$name = $plugin[0];
							break;
						}
					}
				}

				if (!$name) {
					throw new \Exception('Missing FCE name for ' . $fceConfigFile->getFilename());
				}

				ExtensionManagementUtility::addPlugin(array($name, $config['CType']), 'CType', $extensionKey);

				if ($config['flexform']) {
					ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/' . $config['flexform'], $config['CType']);
				}

				$pageTS .=
					'mod.wizards.newContentElement.wizardItems.vierwd.elements.' . $config['CType'] . ' {' . "\n" .
					'	icon = ' . $config['icon'] . "\n" .
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

				$TCA['tt_content']['types'][$config['CType']]['showitem'] = $tca;

				foreach ($config['tcaAdditions'] as $tcaAddition) {
					$method = array_shift($tcaAddition);
					if ($method == 'addToAllTCAtypes') {
						ExtensionManagementUtility::addToAllTCAtypes('tt_content', $tcaAddition[0], $tcaAddition[1], $tcaAddition[2]);
					}
				}
			}

			// update typoscript
			if ($config['list_type']) {
				$typoScript .= 'tt_content.' . $config['CType'] . ' < tt_content.list.20.'.$config['list_type']. "\n";
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
			$header = '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.headers;headers,';
		} else if (in_array('simpleheaders', $tcaType)) {
			$header = '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,';
		} else {
			$header = 'header;LLL:EXT:cms/locallang_ttc.xlf:header.ALT.div_formlabel,';
		}

		$bodytext = '';
		if (in_array('bodytext', $tcaType) || in_array('richtext', $tcaType)) {
			$bodytext = 'bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext_formlabel';

			if (in_array('richtext', $tcaType)) {
				$bodytext .= ';;richtext:rte_transform[flag=rte_enabled|mode=ts_css]';
			}

			$bodytext .= ',';
		}

		if (in_array('image', $tcaType)) {
			$image = '--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.images,
				image,
				--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.imagelinks;imagelinks,';
		} else {
			$image = '';
		}
		if ($config['flexform']) {
			$flexform = 'pi_flexform,';
		} else {
			$flexform = '';
		}

		$tca = '
				--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
				' . $header . '
				' . $bodytext . '
				' . $flexform . '
				' . $image . '
			--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
				--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
			--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
				--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
				--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
			--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended';

		return $tca;
	}

	public function getColumnWidth($data, $column, $size, $zeroOnHidden = true) {
		if ($zeroOnHidden && strpos($data['flexform_visibility_col' . $column], 'hidden-' . $size) !== false) {
			// column not visible
			return 0;
		}

		if ($data['flexform_width_column_' . $size . '_' . $column]) {
			return substr($data['flexform_width_column_' . $size . '_' . $column], 7);
		} else {
			// check width of lower columns
			switch ($size) {
				case 'lg':
					return $this->getColumnWidth($data, $column, 'md', false);
				case 'md':
					return $this->getColumnWidth($data, $column, 'sm', false);
				case 'sm':
					return $this->getColumnWidth($data, $column, 'xs', false);
				default:
					return 12;
			}
		}
	}

	public function resetGrid($content) {
		$this->clearfix = null;
		return $content;
	}

	public function calculateClearfix($data, $columns) {
		$widths = array(
			'xs' => 0,
			'sm' => 0,
			'md' => 0,
			'lg' => 0,
		);
		$clearfix = array(
			'xs' => array(),
			'sm' => array(),
			'md' => array(),
			'lg' => array(),
		);

		foreach (array_keys($widths) as $size) {
			for ($i=1; $i < $columns; $i++) {
				$width = $this->getColumnWidth($data, $i, $size);
				if (!$width) {
					continue;
				}
				if ($width == 12) {
					// no clearfix needed for width of 12
					$widths[$size] = 0;
					continue;
				}
				$widths[$size] += $width;
				if ($widths[$size] >= 12) {
					$clearfix[$size][$i] = true;
					$widths[$size] = 0;
				} else {
					// check if it would be larger with the next column
					if ($widths[$size] + $this->getColumnWidth($data, $i + 1, $size) > 12) {
						$clearfix[$size][$i] = true;
						$widths[$size] = 0;
					}
				}
			}
		}

		$this->clearfix = $clearfix;
	}

	public function clearfixGrid($content, $params) {
		$data = $this->cObj->data;
		$column = $params['column'];
		$columns = $params['columns'];

		if (!$this->clearfix) {
			$this->calculateClearfix($data, $columns);
		}

		$clear = '';

		foreach ($this->clearfix as $size => $clearfixes) {
			foreach ($clearfixes as $clearAfterColumn => $true) {
				if ($clearAfterColumn == $column) {
					$clear .= '<div class="clearfix visible-' . $size . '"></div>';
				}
			}
		}

		return $clear;
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

		if (preg_match('/^<[^>]*\s+id=[^>]*>/', $content)) {
			// id already present, add anchor before the element
			return '<a' . $idAttr . '></a>' . $content;
		}

		return preg_replace('/^<([^\s>!]+)/', '<$1' . $idAttr, $content);
	}
}
