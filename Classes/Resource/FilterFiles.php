<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource;

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * Filter files in fileadmin
 */
class FilterFiles {

	/**
	 * @return bool|int -1 is the "false" value. call_user_func might also return false
	 */
	static public function filterFilesCallback(string $itemName, string $itemIdentifier, string $parentIdentifier, array $additionalInformation, DriverInterface $driverInstance): bool|int {
		$ignoreFolders = ['_vti_cnf', '_vti_pvt', '.git', '.svn', 'CVS', 'Thumbs.db', '.DS_Store'];
		if (in_array($itemName, $ignoreFolders)) {
			return -1;
		}

		foreach ($ignoreFolders as $folderName) {
			if (strpos($itemIdentifier, '/' . $folderName . '/') !== false) {
				return -1;
			}
		}

		$ignorePrefixes = ['_vti'];
		foreach ($ignorePrefixes as $prefix) {
			if (substr($itemName, 0, strlen($prefix)) == $prefix) {
				return -1;
			}
		}

		$ignoreSuffixes = ['.svn-base'];
		foreach ($ignoreSuffixes as $suffix) {
			if (substr($itemName, -strlen($suffix)) == $suffix) {
				return -1;
			}
		}

		return true;
	}

}
