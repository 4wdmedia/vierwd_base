<?php

namespace Vierwd\VierwdBase\Configuration;

use TYPO3\CMS\Core\Utility;

/**
 * TYPO3 installation tries to guess some variables and sets these based on the current system.
 * We do not want this behaviour, because our configuration should be stored in AdditionalConfiguration.php.
 * To solve this issue, we override the configuration manager during installation process and filter
 * some configuration variables
 */
class ConfigurationManager extends \TYPO3\CMS\Core\Configuration\ConfigurationManager {
	public function setLocalConfigurationValuesByPathValuePairs(array $pairs) {
		$localConfiguration = $this->getLocalConfiguration();
		foreach ($pairs as $path => $value) {
			if ($this->isValidLocalConfigurationPath($path)) {
				if (!Utility\ArrayUtility::isValidPath($localConfiguration, $path)) {
					// only set a new value if no old value exists
					$localConfiguration = Utility\ArrayUtility::setValueByPath($localConfiguration, $path, $value);
				}
			}
		}
		return $this->writeLocalConfiguration($localConfiguration);
	}
}