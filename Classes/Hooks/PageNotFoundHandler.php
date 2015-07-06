<?php

namespace Vierwd\VierwdBase\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Robert Vock <robert.vock@4wdmedia.de>, FORWARD MEDIA
 *
 *  All rights reserved
 *
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * handle page not found actions.
 * Redirect to login, when the reason is missing access
 *
 * @package vierwd_base
 */
class PageNotFoundHandler {

	/**
	 * page not found action.
	 * will try to load "/404" and display it. If the failure is due to a access error, tries to load "/login".
	 */
	public function pageNotFound($param, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe) {
		$dirname = dirname($_SERVER['SCRIPT_NAME']);
		if (substr($dirname, -1) != '/') {
			$dirname .= '/';
		}

		if (!empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			header('HTTP/1.1 200 OK');
			echo '404 Loop';
			die;
		}
		$headers = array(
			'X-PageNotFound: 1',
			'User-Agent: ' . GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
		);
		if ($_SERVER['Authorization']) {
			$headers[] = 'Authorization: ' . $_SERVER['Authorization'];
		} else if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
			$headers[] = 'Authorization: Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] .':' . $_SERVER['PHP_AUTH_PW']);
		} else if ($_SERVER['AUTH_TYPE'] == 'Basic') {
			// Kundenbereich
			// $headers[] = 'Authorization: Basic ' . base64_encode('username:password');
		}

		$host = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
		if ($tsfe->pageNotFound == 2 && $param['pageAccessFailureReasons']['fe_group'] != array('' => 0)) {
			header("HTTP/1.0 403 Forbidden");
			if (isset($_COOKIE['fe_typo_user'])) {
				$headers[] = 'Cookie: fe_typo_user=' . $_COOKIE['fe_typo_user'];
			}
			$url = $host . $dirname . 'login/?redirect_url=' . urlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
		} else {
			$url = $host . $dirname . '404/';
		}
		//$report = array();
		$report = null;
		$result = GeneralUtility::getUrl($url, 0, $headers, $report);
		return $result;
	}
}
