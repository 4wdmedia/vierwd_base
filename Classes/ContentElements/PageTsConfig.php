<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ContentElements;

use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;
use Vierwd\VierwdBase\Hooks\ContentElements;

class PageTsConfig {

	public function __invoke(ModifyLoadedPageTsConfigEvent $event): void {
		foreach (ContentElements::$pageTS as $tsConfig) {
			$event->addTsConfig($tsConfig);
		}
	}

}
