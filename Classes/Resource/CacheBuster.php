<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource;

use TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;

class CacheBuster {

	public function __invoke(GeneratePublicUrlForResourceEvent $event): void {
		$storage = $event->getStorage();
		$resource = $event->getResource();
		$driver = $event->getDriver();
		$publicUrl = $event->getPublicUrl();

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
		}

		// Add absRefPrefix infront of URLs. We do not use this for TYPO3 7, because we're still using sourceSetCollection
		// for some projects (picture-configuration.ts) and this would lead to duplicate prefixes.
		// Projects running TYPO3 7 must handle these issues themselves. But most are still using baseURL and do not need
		// an absolute path
		if ($publicUrl && TYPO3_MODE === 'FE' && isset($GLOBALS['TSFE']) && preg_match('/^[a-z]/i', $publicUrl) && $GLOBALS['TSFE']->config['config']['tx_vierwd.']['prependAbsRefPrefixToPublicUrl']) {
			// Force absolute path
			$publicUrl = $GLOBALS['TSFE']->absRefPrefix . $publicUrl;
		}

		if ($publicUrl) {
			$event->setPublicUrl($publicUrl);
		}
	}
}
