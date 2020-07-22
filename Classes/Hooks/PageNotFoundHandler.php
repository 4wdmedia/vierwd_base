<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * handle page not found actions.
 * Redirect to login, when the reason is missing access
 */
class PageNotFoundHandler implements PageErrorHandlerInterface {

	/**
	 * page not found action.
	 * will try to load "/404" and display it. If the failure is due to a access error, tries to load "/login".
	 */
	public function pageNotFound(array $param) {
		$request = $GLOBALS['TYPO3_REQUEST'];
		$response = $this->handlePageError($request, $param['reasonText'], $param['pageAccessFailureReasons']);
		return (string)$response->getBody();
	}

	public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface {
		if (!empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			$response = new HtmlResponse('404 Loop', 404);
			return $response;
		}

		$language = $request->getAttribute('language');
		if (!$language) {
			$language = $request->getAttribute('site')->getDefaultLanguage();
		}

		if ($reasons && in_array($reasons['code'], ['access.page', 'access.subsection'])) {
			$uri = (string)$language->getBase() . 'login?redirect_url=' . urlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
			$statusCode = 403;
		} else {
			$uri = (string)$language->getBase() . '404';
			$statusCode = 404;
		}

		$page = $this->load404Page($uri, $message);

		$response = new HtmlResponse($page, $statusCode);
		return $response;
	}

	protected function load404Page(string $uri, string $reason = '') {
		if (!empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			return '404 Loop';
		}

		$headers = [
			'X-PageNotFound' => '1',
			'User-Agent' => GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
		];
		if ($_SERVER['Authorization']) {
			$headers['Authorization'] = $_SERVER['Authorization'];
		} else if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
			$headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
		} else if ($_SERVER['AUTH_TYPE'] == 'Basic') {
			// Kundenbereich
			$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
			if (isset($extConf['serviceUsername'], $extConf['servicePassword'])) {
				$headers['Authorization'] = 'Basic ' . base64_encode($extConf['serviceUsername'] . ':' . $extConf['servicePassword']);
			}
		}

		$cookieName = $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieName'];
		if (isset($_COOKIE[$cookieName])) {
			$headers['Cookie'] = $cookieName . '=' . $_COOKIE[$cookieName];
		}

		// $report = [];
		$report = null;
		$result = GeneralUtility::getUrl($uri, 0, $headers, $report);
		if ($GLOBALS['BE_USER']) {
			$result = str_replace('%REASON%', '<strong>Reason</strong>: ' . htmlspecialchars($reason), $result);
		} else {
			$result = str_replace('%REASON%', '', $result);
		}
		return $result;
	}
}
