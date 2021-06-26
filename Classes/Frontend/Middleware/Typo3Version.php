<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Information\Typo3Version as VersionInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3Version implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		if (!($queryParams = $request->getQueryParams()) || !isset($queryParams['eID']) || $queryParams['eID'] !== 'vierwd_typo3version') {
			return $handler->handle($request);
		}

		$stream = fopen('php://memory', 'r+');
		if ($stream === false) {
			return GeneralUtility::makeInstance(Response::class, '', 500);
		}
		fwrite($stream, (new VersionInformation())->getVersion());
		rewind($stream);
		$stream = new Stream($stream);

		$response = GeneralUtility::makeInstance(Response::class);
		$response = $response->withBody($stream);
		$response = $response->withAddedHeader('Content-Type', 'text/plain');
		$response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');

		return $response;
	}
}
