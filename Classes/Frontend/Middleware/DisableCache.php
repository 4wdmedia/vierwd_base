<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Allow Shift-Reload even without admin login in local context
 */
class DisableCache implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		if (!empty($request->getServerParams()['VIERWD_CONFIG'] ?? false) && $request->getHeaderLine('Cache-Control') === 'no-cache') {
			$request = $request->withAttribute('noCache', true);
		}

		return $handler->handle($request);
	}

}
