<?php

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Expired Preview links throw an exception. The only solution is to delete the cookie
 * This is really user-unfriendly
 * @see https://forge.typo3.org/issues/18761
 */
class VersioningHooks {

	public function removeExpiredPreviewCookie() {
		if (isset($_COOKIE['ADMCMD_prev'])) {
			// check if the cookie is valid. if not, delete it
			$inputCode = $_COOKIE['ADMCMD_prev'];
			$previewData = false;

			try {
				if (TYPO3_version <= '8.5.0') {
					$where = 'keyword=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($inputCode, 'sys_preview') . ' AND endtime>' . $GLOBALS['EXEC_TIME'];
					$previewData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_preview', $where);
				} else {
					$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_preview');
					$previewData = $queryBuilder->select('*')
						->from('sys_preview')
						->where($queryBuilder->expr()->eq('keyword', $inputCode))
						->andWhere($queryBuilder->expr()->gt('endtime', $GLOBALS['EXEC_TIME']))
						->setMaxResults(1)
						->execute()
						->rowCount();
				}
			} catch (\Exception $e) {
				// exception might occur when sys_preview table does not exist.
				// ignore exception. previewData is still false
			}
			if (!$previewData) {
				// remove cookie
				unset($_COOKIE['ADMCMD_prev']);
				setcookie('ADMCMD_prev', '', 0, GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
			}
		}
	}
}
