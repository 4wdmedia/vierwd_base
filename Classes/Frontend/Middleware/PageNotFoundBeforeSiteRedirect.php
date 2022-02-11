<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Resolves redirects of site if base is not /
 * Can be replaced or extended by extensions if GeoIP-based or user-agent based language redirects need to happen.
 */
class PageNotFoundBeforeSiteRedirect implements MiddlewareInterface {

	/**
	 * Generate 404 instead of redirecting to language default page
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$site = $request->getAttribute('site', null);
		$language = $request->getAttribute('language', null);
		$routeResult = $request->getAttribute('routing', null);

		// Usually called when "https://www.example.com" was entered, but all sites have "https://www.example.com/lang-key/"
		// So a redirect to the first possible language is done.
		if ($site instanceof Site && !($language instanceof SiteLanguage) && $routeResult instanceof SiteRouteResult && $routeResult->getTail()) {
			return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
				$GLOBALS['TYPO3_REQUEST'],
				'The requested page does not exist!',
				['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
			);
		}

		return $handler->handle($request);
	}
}
