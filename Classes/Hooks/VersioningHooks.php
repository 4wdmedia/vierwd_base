<?php

namespace Vierwd\VierwdBase\Hooks;

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
			$where = 'keyword=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($inputCode, 'sys_preview') . ' AND endtime>' . $GLOBALS['EXEC_TIME'];
			$previewData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_preview', $where);
			if (!$previewData) {
				// remove cookie
				unset($_COOKIE['ADMCMD_prev']);
				setcookie('ADMCMD_prev', '', 0, GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
			}
		}
	}
}