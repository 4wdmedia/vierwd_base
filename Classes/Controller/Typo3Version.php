<?php

namespace Vierwd\VierwdBase\Controller;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Http\Message;
use TYPO3\CMS\Core\Http\Stream;

class Typo3Version {

	public function main(RequestInterface $request, Message $response) {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, TYPO3_version);
		rewind($stream);
		$stream = new Stream($stream);
		$response = $response->withBody($stream);
		$response = $response->withAddedHeader('Content-Type', 'text/plain');
		$response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');

		return $response;
	}
}