<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * We work with many custom content elements. It is quite easy to forget to give editors access
 * rights to edit and modify those elements.
 * This Hook checks if all available content elements are allowed for at least one backend group.
 * If a content element should be only available for admins, you need to configure this behaviour in
 * the extension configuration
 */
class CheckBackendGroups {

	public function displayWarningMessages_postProcess(array &$warnings) {
		$contentElements = $this->getContentElements();
		$backendGroups = $this->getBackendGroups();

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

		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vierwd_base']);
		if (!empty($extConf['adminElements'])) {
			$adminElements = GeneralUtility::trimExplode(',', $extConf['adminElements']);
			$contentElements = array_diff_key($contentElements, array_flip($adminElements));
		}

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

	/**
	 * @return array
	 */
	protected function getBackendGroups() {
		if (TYPO3_version <= '8.5.0') {
			return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_groups', '1=1' . BackendUtility::BEenableFields('be_groups'));
		} else {
			$queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('be_groups');
			$queryBuilder->select('*')->from('be_groups');
			return $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
		}
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns an array with all content element CTypes
	 *
	 * @return array
	 */
	protected function getContentElements() {
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

		$contentElements = array_combine($elementKeys, $elementNames);
		return $contentElements;
	}
}
