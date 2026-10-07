<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\Caching;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;

class ForcedReload {

	#[AsEventListener]
	public function __invoke(ShouldUseCachedPageDataIfAvailableEvent $event): void {
		$request = $event->getRequest();
		if (!empty($request->getServerParams()['VIERWD_CONFIG'] ?? false) && $request->getHeaderLine('Cache-Control') === 'no-cache') {
			$event->setShouldUseCachedPageData(false);
		}
	}

}
