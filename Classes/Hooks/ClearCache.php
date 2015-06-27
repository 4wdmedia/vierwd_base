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

		$groups = [];
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cacheKey => $cacheConfiguration) {
			if (!is_array($cacheConfiguration['groups'])) {
				continue;
			}
			foreach ($cacheConfiguration['groups'] as $group) {
				$groups[$group][] = $cacheKey;
			}
		}
		if (isset($groups['all'], $groups['pages']) && $groups['all'] === $groups['pages']) {
			// remove the "all" group, it is identical to pages.
			$cacheActions = array_filter($cacheActions, function($cacheConfiguration) {
				return $cacheConfiguration['id'] != 'all';
			});
		}

		if ($GLOBALS['BE_USER']->isAdmin()) {
			// modify description to inform which caches will be cleared
			foreach ($cacheActions as &$actionConfiguration) {
				$cacheKey = $actionConfiguration['id'];
				if (isset($groups[$cacheKey])) {
					if (!isset($actionConfiguration['description'])) {
						$actionConfiguration['description'] = $actionConfiguration['title'];
					}
					$actionConfiguration['description'] .= "\n" . implode(', ', $groups[$cacheKey]);
				}
				unset($actionConfiguration);
			}
		}

		// change the icon of the system cache group. It has a lower impact as our "complete" cache action
		foreach ($cacheActions as &$actionConfiguration) {
			if ($actionConfiguration['id'] == 'system') {
				$actionConfiguration['icon'] = IconUtility::getSpriteIcon('actions-system-cache-clear-impact-medium');
			}
			unset($actionConfiguration);
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

	public function clearCacheCmd(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
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