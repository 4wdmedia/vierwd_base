<?php

namespace Vierwd\VierwdBase\Resource;

/**
 * Filter files in fileadmin
 */
class FilterFiles {

	static public function filterFilesCallback($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, \TYPO3\CMS\Core\Resource\Driver\DriverInterface $driverInstance) {
		$ignoreFolders = array('_vti_cnf', '_vti_pvt', '.git', '.svn', 'CVS', 'Thumbs.db', '.DS_Store');
		if (in_array($itemName, $ignoreFolders)) {
			return -1;
		}

		foreach ($ignoreFolders as $folderName) {
			if (strpos($itemIdentifier, '/' . $folderName . '/') !== false) {
				return -1;
			}
		}

		$ignorePrefixes = array('_vti');
		foreach ($ignorePrefixes as $prefix) {
			if (substr($itemName, 0, strlen($prefix)) == $prefix) {
				return -1;
			}
		}

		$ignoreSuffixes = array('.svn-base');
		foreach ($ignoreSuffixes as $suffix) {
			if (substr($itemName, -strlen($suffix)) == $suffix) {
				return -1;
			}
		}

		return TRUE;
	}
}