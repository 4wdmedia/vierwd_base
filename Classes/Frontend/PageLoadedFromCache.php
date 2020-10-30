<?php

namespace Vierwd\VierwdBase\Frontend;

/**
 * when two requests hit TYPO3 in parallel, the first request will write a "Page is being generated." message as cache-entry for the page.
 * The second request will load this page from cache and not generate the page as well.
 * Better handling would be to stall for a short time and check if the generation has finished.
 */
class PageLoadedFromCache {

	public function stallTempPage(array $params, $TSFE) {
		if ($params['cache_pages_row']['temp_content']) {
			$times = 0;
			while (++$times < 25 && $params['cache_pages_row']['temp_content']) {
				// sleep for 200 ms (for a total maximum of 5s)
				usleep(200000);
				$params['cache_pages_row'] = $TSFE->getFromCache_queryRow();
			}
		}
	}
}
