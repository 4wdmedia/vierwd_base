<?php

namespace Vierwd\VierwdBase\Hooks;

class ImportExport {

	public function before_setRelation(array &$params, \TYPO3\CMS\Impexp\ImportExport $parentObject) {
		if (isset($params['data'], $params['data']['pages'])) {
			foreach ($params['data']['pages'] as &$page) {
				if (isset($page['perms_groupid'])) {
					$page['perms_groupid'] = str_replace('be_groups_', '', $page['perms_groupid']);
				}
				unset($page);
			}
		}
	}

	public function before_writeRecordsPages(array &$params, \TYPO3\CMS\Impexp\ImportExport $parentObject) {
		if (isset($params['data'], $params['data']['pages'])) {
			foreach ($params['data']['pages'] as &$page) {
				if (isset($page['force_perms_groupid'])) {
					$page['perms_groupid'] = $page['force_perms_groupid'];
					unset($page['force_perms_groupid']);
				}
				unset($page);
			}
		}
	}
}