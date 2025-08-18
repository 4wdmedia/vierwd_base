<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Page TsConfig relevant for this record
 */
class GridelementsMergeTsConfig implements FormDataProviderInterface {

	/**
	 * Merge type specific page TS to pageTsConfig
	 *
	 * @return array
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 */
	public function addData(array $result) {
		if (!ExtensionManagementUtility::isLoaded('gridelements')) {
			return $result;
		}

		if ($result['tableName'] !== 'tt_content') {
			return $result;
		}

		if (!isset($result['pageTsConfig']['TCEFORM.'], $result['pageTsConfig']['TCEFORM.']['tt_content.'])
			|| !is_array($result['pageTsConfig']['TCEFORM.']['tt_content.'])
		) {
			return $result;
		}

		$mergedTCEForm = $result['pageTsConfig']['TCEFORM.']['tt_content.'];
		$type = $result['customData']['vierwd_base']['gridelementType'] ?? '';

		// Merge TCEFORM.tt_content.vierwd_gridtypes.[type].[field] over TCEFORM.tt_content.[field]
		if (!empty($mergedTCEForm['vierwd_gridtypes.'][$type . '.'])
			&& is_array($mergedTCEForm['vierwd_gridtypes.'][$type . '.'])
		) {
			foreach ($mergedTCEForm['vierwd_gridtypes.'][$type . '.'] as $fieldNameWithDot => $additionalFieldConfiguration) {
				$newFieldConfiguration = $mergedTCEForm;
				ArrayUtility::mergeRecursiveWithOverrule($newFieldConfiguration, $additionalFieldConfiguration);
				$mergedTCEForm[$fieldNameWithDot] = $newFieldConfiguration;
			}
		}

		$result['pageTsConfig']['TCEFORM.']['tt_content.'] = $mergedTCEForm;

		return $result;
	}

}
