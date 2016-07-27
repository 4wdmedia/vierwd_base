<?php

namespace Vierwd\VierwdBase\Controller;

class Typo3Version {

	public function main(\Psr\Http\Message\RequestInterface $request, \TYPO3\CMS\Core\Http\Message $response) {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, TYPO3_version);
		rewind($stream);
		$stream = new \TYPO3\CMS\Core\Http\Stream($stream);
		$response = $response->withBody($stream);
		$response = $response->withAddedHeader('Content-Type', 'text/plain');
		$response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');

		return $response;
	}
}