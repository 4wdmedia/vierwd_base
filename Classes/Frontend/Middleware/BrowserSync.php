<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BrowserSync implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$response = $handler->handle($request);
		$typoScript = $request->getAttribute('frontend.typoscript');

		if (($response instanceof NullResponse) || !$typoScript) {
			return $response;
		}

		try {
			$browserSync = ArrayUtility::getValueByPath($typoScript->getConfigArray(), 'tx_vierwd./browserSync');
			if (!$browserSync) {
				return $response;
			}
		} catch (MissingArrayPathException $th) {
			// ignore
		}

		// check if the port 3000 is open
		// https://apple.stackexchange.com/questions/81140/why-is-lsof-on-os-x-so-ridiculously-slow
		// lsof -i tcp:3000 -P -O -l -n | grep "^node.*3000"
		if (!trim(shell_exec('netstat -vanp tcp | grep 3000') ?: '')) {
			return $response;
		}

		$browserSync = '<script async src="http' . (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . ':3000/browser-sync/browser-sync-client.js"></script>';

		$body = $response->getBody();
		$body->rewind();
		$contents = $response->getBody()->getContents();
		$content = str_ireplace(
			'</body>',
			$browserSync . '</body>',
			$contents
		);
		$body = new Stream('php://temp', 'rw');
		$body->write($content);
		$response = $response->withBody($body);
		return $response;
	}

}
