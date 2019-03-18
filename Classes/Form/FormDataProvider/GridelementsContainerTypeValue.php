<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Determine the TCA gridelements parent type value
 */
class GridelementsContainerTypeValue implements FormDataProviderInterface {

	public function addData(array $result) {
		if (!ExtensionManagementUtility::isLoaded('gridelements')) {
			return $result;
		}

		if ($result['tableName'] !== 'tt_content' || !isset($result['databaseRow']['tx_gridelements_container']) || empty($result['databaseRow']['tx_gridelements_container'])) {
			// nothing to do
			return $result;
		}

		// get parent
		$parent = BackendUtility::getRecord('tt_content', (int)$result['databaseRow']['tx_gridelements_container'], 'tx_gridelements_backend_layout');
		if ($parent && $parent['tx_gridelements_backend_layout']) {
			$result['customData']['vierwd_base']['gridelementType'] = (string)$parent['tx_gridelements_backend_layout'];
		}

		return $result;
	}
}
