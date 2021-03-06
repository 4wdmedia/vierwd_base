<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Controller;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Message;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Information\Typo3Version as VersionInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3Version {

	public function main(RequestInterface $request, Message $response = null): ResponseInterface {
		$stream = fopen('php://memory', 'r+');
		if ($stream === false) {
			return GeneralUtility::makeInstance(Response::class, '', 500);
		}
		fwrite($stream, (new VersionInformation())->getVersion());
		rewind($stream);
		$stream = new Stream($stream);

		if (!$response || !$response instanceof ResponseInterface) {
			$response = GeneralUtility::makeInstance(Response::class);
		}
		$response = $response->withBody($stream);
		$response = $response->withAddedHeader('Content-Type', 'text/plain');
		$response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');

		return $response;
	}
}
