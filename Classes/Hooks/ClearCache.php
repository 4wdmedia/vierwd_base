<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * add new cache configuration "complete" which empties ALL (frontend and system) caches.
 * @see http://forge.typo3.org/issues/59173
 */
class ClearCache implements ClearCacheActionsHookInterface {

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

		if (TYPO3_version >= '8.6.0') {
			// no need to add "all" cache group
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

		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);

		// change the icon of the system cache group. It has a lower impact as our "complete" cache action
		foreach ($cacheActions as &$actionConfiguration) {
			if ($actionConfiguration['id'] == 'system') {
				$actionConfiguration['icon'] = $iconFactory->getIcon('actions-system-cache-clear-impact-medium', Icon::SIZE_SMALL)->render();
			}
			unset($actionConfiguration);
		}

		$cacheActions[] = [
			'id' => 'complete',
			'title' => 'Alle Caches leeren',
			'description' => htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesDescription'), ENT_COMPAT, 'UTF-8', false),
			'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $GLOBALS['BE_USER']->veriCode(), 'cacheCmd' => 'complete', 'ajaxCall' => 1]),
			'icon' => $iconFactory->getIcon('actions-system-cache-clear-impact-high', Icon::SIZE_SMALL)->render(),
		];
		$optionValues[] = 'complete';
	}

	public function clearCacheCmd(array $params, DataHandler $dataHandler) {
		if (!isset($params['cacheCmd'])) {
			return;
		}

		$cacheCmd = $params['cacheCmd'];

		if ($cacheCmd === 'all' && TYPO3_version >= '8.6.0') {
			// all works as before 6.2. but also delete realurl
			if (file_exists(PATH_typo3conf . 'realurl_autoconf.php')) {
				unlink(PATH_typo3conf . 'realurl_autoconf.php');
			}
		} else if ($cacheCmd === 'complete' && TYPO3_version < '8.6.0') {
			$this->addCompleteCacheGroup();
			$this->getCacheManager()->flushCachesInGroup('complete');

			if (file_exists(PATH_typo3conf . 'realurl_autoconf.php')) {
				unlink(PATH_typo3conf . 'realurl_autoconf.php');
			}
		} else if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheCmd])) {
			if ($this->getCacheManager()->getCache($cacheCmd)) {
				// flush only one specific cache
				$this->getCacheManager()->getCache($cacheCmd)->flush();
			}
		}
	}

	/**
	 * Create and returns an instance of the CacheManager
	 *
	 * @return \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected function getCacheManager() {
		return GeneralUtility::makeInstance(CacheManager::class);
	}
}
