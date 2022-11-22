<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
	public function pageNotFound(array $param): string {
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
		if (!$language || !($language instanceof SiteLanguage) || !$language->isEnabled()) {
			$site = $request->getAttribute('site');
			assert($site instanceof SiteInterface);
			$language = $site->getDefaultLanguage();
		}

		if ($reasons && in_array($reasons['code'], ['access.page', 'access.subsection'])) {
			$uri = (string)$language->getBase() . 'login?redirect_url=' . urlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
			$statusCode = 403;
		} else {
			$uri = (string)$language->getBase() . '404';
			$statusCode = 404;
		}

		try {
			$response = $this->load404Page($uri);

			$pageContent = (string)$response->getBody();
			if ($GLOBALS['BE_USER']) {
				$pageContent = str_replace('%REASON%', '<strong>Reason</strong>: ' . htmlspecialchars($message), $pageContent);
			} else {
				$pageContent = str_replace('%REASON%', '', $pageContent);
			}

			$cookieHeaders = $response->getHeader('set-cookie');
			$headers = $cookieHeaders ? ['Set-Cookie' => $cookieHeaders] : [];

			$response = new HtmlResponse($pageContent, $statusCode, $headers);
		} catch (\Throwable $e) {
			$response = new HtmlResponse($e->getMessage(), $statusCode);
		}

		return $response;
	}

	protected function load404Page(string $uri): ResponseInterface {
		if (!empty($_SERVER['HTTP_X_PAGENOTFOUND'])) {
			throw new \Exception('404 Loop', 1618222390);
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

		$requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
		return $requestFactory->request($uri, 'GET', [
			'headers' => $headers,
			'http_errors' => false,
		]);
	}

}
