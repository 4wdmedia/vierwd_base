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
			assert($resource->getIdentifier() !== '');
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

		if ($publicUrl) {
			$event->setPublicUrl($publicUrl);
		}
	}

}
