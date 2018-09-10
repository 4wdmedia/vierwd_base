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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
			exit;
		}
		$headers = [
			'X-PageNotFound' => '1',
			'User-Agent' => GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
		];
		if ($_SERVER['Authorization']) {
			$headers['Authorization'] = $_SERVER['Authorization'];
		} else if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
			$headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] .':' . $_SERVER['PHP_AUTH_PW']);
		} else if ($_SERVER['AUTH_TYPE'] == 'Basic') {
			// Kundenbereich
			$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
			if (isset($extConf['serviceUsername'], $extConf['servicePassword'])) {
				$headers['Authorization'] = 'Basic ' . base64_encode($extConf['serviceUsername'] . ':' . $extConf['servicePassword']);
			}
		}

		$host = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
		$cookieName = $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieName'];
		if (isset($_COOKIE[$cookieName])) {
			$headers['Cookie'] = $cookieName . '=' . $_COOKIE[$cookieName];
		}
		// if (is_array($param['pageAccessFailureReasons']['fe_group']) && current($param['pageAccessFailureReasons']['fe_group']) != -1 && $param['pageAccessFailureReasons']['fe_group'] != ['' => 0]) {
		if ($tsfe->pageNotFound == 2 && isset($param['pageAccessFailureReasons']['fe_group']) && $param['pageAccessFailureReasons']['fe_group'] != ['' => 0]) {
			header('HTTP/1.0 403 Forbidden');
			$url = $host . $dirname . 'login/?redirect_url=' . urlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
		} else {
			$url = $host . $dirname . '404/';
		}
		//$report = [];
		$report = null;
		$result = GeneralUtility::getUrl($url, 0, $headers, $report);
		if ($GLOBALS['BE_USER']) {
			$result = str_replace('%REASON%', '<strong>Reason</strong>: ' . htmlspecialchars($param['reasonText']), $result);
		} else {
			$result = str_replace('%REASON%', '', $result);
		}
		return $result;
	}
}
