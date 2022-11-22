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

/**
 * Allow &L=1 in URL, when language prefix is missing
 */
class LanguageGetVar implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$site = $request->getAttribute('site', null);
		$language = $request->getAttribute('language', null);
		$routeResult = $request->getAttribute('routing', null);

		if ($site instanceof Site && !($language instanceof SiteLanguage) && $routeResult->getUri() && $routeResult->getUri()->getQuery()) {
			parse_str($routeResult->getUri()->getQuery(), $query);
			if (!isset($query['L']) || !is_numeric($query['L'])) {
				return $handler->handle($request);
			}

			$languageGetVar = (int)$query['L'];
			foreach ($site->getAllLanguages() as $language) {
				if ($language->getLanguageId() === $languageGetVar) {
					$request = $request->withAttribute('language', $language);
					$siteRouteResult = new SiteRouteResult($request->getUri(), $site, $language, $routeResult->getTail());
					$request = $request->withAttribute('routing', $siteRouteResult);
					return $handler->handle($request);
				}
			}
		}

		return $handler->handle($request);
	}

}
