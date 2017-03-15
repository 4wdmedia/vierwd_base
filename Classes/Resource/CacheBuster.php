<?php

namespace Vierwd\VierwdBase\Resource;

use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class CacheBuster {

	public function getPublicUrl(ResourceStorageInterface $storage, DriverInterface $driver, ResourceInterface $resource, $relativeToCurrentScript, $params) {
		if ($storage->isPublic() && $resource instanceof Resource\FileInterface) {
			$publicUrl = $driver->getPublicUrl($resource->getIdentifier());
			if ($resource instanceof Resource\ProcessedFile || $resource instanceof Resource\FileReference) {
				$publicUrl .= '?_=' . $resource->getOriginalFile()->getModificationTime();
			} else {
				$publicUrl .= '?_=' . $resource->getModificationTime();
			}

			// Fix filename for stupid IE
			// https://support.microsoft.com/en-us/kb/221805
			$fileName = $resource->getName();
			$extension = strtolower(substr($fileName, -4));
			if ($extension === '.exe' || $extension === '.dll' || $extension === '.zip') {
				$publicUrl .= '&fileName=' . $fileName;
			}

			// copied from typo3/sysext/core/Classes/Resource/ResourceStorage.php
			if ($publicUrl !== null && $relativeToCurrentScript && !GeneralUtility::isValidUrl($publicUrl)) {
				$absolutePathToContainingFolder = PathUtility::dirname(PATH_site . $publicUrl);
				$pathPart = PathUtility::getRelativePathTo($absolutePathToContainingFolder);
				$filePart = substr(PATH_site . $publicUrl, strlen($absolutePathToContainingFolder) + 1);
				$publicUrl = $pathPart . $filePart;
			}

			$params['publicUrl'] = $publicUrl;
		}
	}
}