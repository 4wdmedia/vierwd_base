<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * We work with many custom content elements. It is quite easy to forget to give editors access
 * rights to edit and modify those elements.
 * This Hook checks if all available content elements are allowed for at least one backend group.
 * If a content element should be only available for admins, you need to configure this behaviour in
 * the extension configuration
 */
class CheckBackendGroups {

	public function displayWarningMessages_postProcess(array &$warnings): void {
		if (!$GLOBALS['BE_USER'] || (!StringUtility::endsWith($GLOBALS['BE_USER']->user['email'], '@4wdmedia.de') && !StringUtility::endsWith($GLOBALS['BE_USER']->user['email'], '@nou-digital.de'))) {
			return;
		}

		$backendGroups = $this->getBackendGroups();
		$this->checkContentElements($warnings, $backendGroups);

		$this->checkDatabaseTables($warnings, $backendGroups);
	}

	private function checkContentElements(array &$warnings, array $backendGroups): void {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] != 'explicitAllow') {
			return;
		}

		$contentElements = $this->getContentElements();

		foreach ($backendGroups as $backendGroup) {
			$allowDeny = GeneralUtility::trimExplode(',', $backendGroup['explicit_allowdeny']);
			foreach ($allowDeny as $field) {
				if (substr($field, 0, 17) !== 'tt_content:CType:') {
					continue;
				}

				$allow = GeneralUtility::trimExplode(':', $field);
				if ($allow[3] === 'ALLOW') {
					// $allow[2] is the CType
					unset($contentElements[$allow[2]]);
				}
			}
		}

		$adminElements = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base', 'adminElements');
		$adminElements = GeneralUtility::trimExplode(',', $adminElements);
		$contentElements = array_diff_key($contentElements, array_flip($adminElements));

		// all contentElements that are still left in $contentElements do not appear in any backend group
		// this might be an error, if the content element is new and the editor group was not updated
		// and allowed access to the new element
		if ($contentElements) {
			$contentElements = implode(', ', array_map(function($name, $CType) {
				return htmlspecialchars($name . ' (' . $CType . ')');
			}, $contentElements, array_keys($contentElements)));
			$warnings[] = 'No backend group has access to edit the following content elements: <strong>' . $contentElements . '</strong>. If this is intentional, configure adminElements in vierwd_base extension configuration.';
		}
	}

	private function checkDatabaseTables(array &$warnings, array $backendGroups): void {
		$allAllowedTables = [];
		foreach ($backendGroups as $backendGroup) {
			$tables = GeneralUtility::trimExplode(',', $backendGroup['tables_modify'], true);
			$allAllowedTables = array_merge($allAllowedTables, $tables);

			$tables = GeneralUtility::trimExplode(',', $backendGroup['tables_select'], true);
			$allAllowedTables = array_merge($allAllowedTables, $tables);
		}
		$allAllowedTables = array_unique($allAllowedTables);

		$tables = [];
		foreach ($GLOBALS['TCA'] as $table => $tableConfig) {
			if (empty($tableConfig['ctrl']['hideTable']) && empty($tableConfig['ctrl']['adminOnly']) && !in_array($table, $allAllowedTables)) {
				$tables[] = $table;
			}
		}

		// Only check FORWARD MEDIA Tables
		$tables = array_filter($tables, function($table) {
			return substr($table, 0, 9) === 'tx_vierwd';
		});

		// $tables contains all tables for which no backend group has access
		// this might be an error, if the table is new and the editor group was not updated
		// and allowed access to the new table
		if ($tables) {
			$tables = implode(', ', array_map(function($table) {
				$name = $GLOBALS['TCA'][$table]['ctrl']['title'];
				$name = $GLOBALS['LANG']->sL($name);
				return htmlspecialchars($name . ' (' . $table . ')');
			}, $tables));
			$warnings[] = 'No backend group has access to edit the following tables: <strong>' . $tables . '</strong>.<br>If this is intentional, set adminOnly for those tables.';
		}
	}

	protected function getBackendGroups(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
		$queryBuilder->select('*')->from('be_groups');
		return $queryBuilder->execute()->fetchAllAssociative();
	}

	/**
	 * @return \TYPO3\CMS\Core\Localization\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns an array with all content element CTypes
	 */
	protected function getContentElements(): array {
		$languageService = static::getLanguageService();
		$contentElements = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
		$contentElements = array_filter($contentElements, function($contentElement) {
			return $contentElement[1] !== '--div--' && empty($contentElement['adminOnly']);
		});

		$elementKeys = array_map(function($contentElement) {
			return $contentElement[1];
		}, $contentElements);
		$elementNames = array_map(function($contentElement) use ($languageService) {
			return $languageService->sL($contentElement[0]);
		}, $contentElements);

		$contentElements = (array)array_combine($elementKeys, $elementNames);
		return $contentElements;
	}
}
