<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * We work with many custom content elements. It is quite easy to forget to give editors access
 * rights to edit and modify those elements.
 * This Hook checks if all available content elements are allowed for at least one backend group.
 * If a content element should be only available for admins, you need to configure this behaviour in
 * the extension configuration
 */
final class CheckBackendGroups {

	#[AsEventListener]
	public function __invoke(ModifyGenericBackendMessagesEvent $event): void {
		$beUser = $this->getBackendUser();
		$beUserEmail = $beUser->user['email'] ?? '';
		if (!str_ends_with($beUserEmail, '@4wdmedia.de') && !str_ends_with($beUserEmail, '@nou-digital.de')) {
			return;
		}

		$backendGroups = $this->getBackendGroups();
		$this->checkContentElements($event, $backendGroups);

		$this->checkDatabaseTables($event, $backendGroups);
	}

	private function getBackendUser(): BackendUserAuthentication {
		return $GLOBALS['BE_USER'];
	}

	private function getLanguageService(): LanguageService {
		return $GLOBALS['LANG'];
	}

	private function checkContentElements(ModifyGenericBackendMessagesEvent $event, array $backendGroups): void {
		$contentElements = $this->getContentElements();

		foreach ($backendGroups as $backendGroup) {
			$allowDeny = GeneralUtility::trimExplode(',', $backendGroup['explicit_allowdeny'], true);
			foreach ($allowDeny as $field) {
				if (!str_starts_with($field, 'tt_content:CType:')) {
					continue;
				}

				$allow = GeneralUtility::trimExplode(':', $field);
				// $allow[2] is the CType
				unset($contentElements[$allow[2]]);
			}
		}

		$adminElements = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base', 'adminElements');
		if (!is_string($adminElements)) {
			$adminElements = '';
		}
		$adminElements = GeneralUtility::trimExplode(',', $adminElements);
		$contentElements = array_diff_key($contentElements, array_flip($adminElements));

		// all contentElements that are still left in $contentElements do not appear in any backend group
		// this might be an error, if the content element is new and the editor group was not updated
		// and allowed access to the new element
		if ($contentElements) {
			$contentElements = implode(', ', array_map(function($name, $CType) {
				return htmlspecialchars($name . ' (' . $CType . ')');
			}, $contentElements, array_keys($contentElements)));

			$event->addMessage(new FlashMessage('No backend group has access to edit the following content elements: <strong>' . $contentElements . '</strong>. If this is intentional, configure adminElements in vierwd_base extension configuration.'));
		}
	}

	private function checkDatabaseTables(ModifyGenericBackendMessagesEvent $event, array $backendGroups): void {
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
			return str_starts_with($table, 'tx_vierwd');
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
			$event->addMessage(new FlashMessage('No backend group has access to edit the following tables: <strong>' . $tables . '</strong>.<br>If this is intentional, set adminOnly for those tables.'));
		}
	}

	private function getBackendGroups(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
		$queryBuilder->select('*')->from('be_groups');
		return $queryBuilder->executeQuery()->fetchAllAssociative();
	}

	/**
	 * Returns an array with all content element CTypes
	 */
	private function getContentElements(): array {
		$languageService = $this->getLanguageService();
		$contentElements = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
		$contentElements = array_filter($contentElements, function($contentElement) {
			return empty($contentElement['adminOnly']);
		});

		$elementKeys = array_map(function($contentElement) {
			return $contentElement['value'];
		}, $contentElements);
		$elementNames = array_map(function($contentElement) use ($languageService) {
			return $languageService->sL($contentElement['label']);
		}, $contentElements);

		$contentElements = (array)array_combine($elementKeys, $elementNames);
		return $contentElements;
	}

}
