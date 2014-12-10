<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * add new cache configuration "complete" which empties ALL (frontend and system) caches.
 * @see http://forge.typo3.org/issues/59173
 */
class ClearCache implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	public function addCompleteCacheGroup() {
		$cacheManager = $this->getCacheManager();
		if (!$cacheManager->hasCache('complete')) {
			// foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $key => &$data) {
			// 	if (is_array($data['groups']) && !in_array('complete', $data['groups'])) {
			// 		$data['groups'][] = 'complete';
			// 	}
			// }

			// $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

			// TYPO3 6.2.7 does not add the group later on. Force it!
			$reflection = new \ReflectionObject($cacheManager);
			$cacheGroupsProperty = $reflection->getProperty('cacheGroups');
			$cacheGroupsProperty->setAccessible(true);
			$cacheGroups = $cacheGroupsProperty->getValue($cacheManager);
			$cacheGroups['complete'] = array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
			$cacheGroupsProperty->setValue($cacheManager, $cacheGroups);
		}
	}

	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if (!$GLOBALS['BE_USER']->isAdmin() && !$GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.complete')) {
			return;
		}

		$cacheActions[] = array(
			'id' => 'complete',
			'title' => 'Alle Caches leeren',
			'description' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesDescription', TRUE),
			'href' => 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() . '&cacheCmd=complete&ajaxCall=1' . BackendUtility::getUrlToken('tceAction'),
			'icon' => IconUtility::getSpriteIcon('actions-system-cache-clear-impact-high')
		);
		$optionValues[] = 'complete';
	}

	public function clear_cacheCmd(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
		if (isset($params['cacheCmd']) && $params['cacheCmd'] == 'complete') {
			$this->addCompleteCacheGroup();
			$this->getCacheManager()->flushCachesInGroup('complete');
		}
	}

	/**
	 * Create and returns an instance of the CacheManager
	 *
	 * @return \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected function getCacheManager() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
	}
}