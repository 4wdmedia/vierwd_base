<?php

namespace Vierwd\VierwdBase\Controller;

class Typo3Version {

	public function main(\Psr\Http\Message\RequestInterface $request, \TYPO3\CMS\Core\Http\Message $response) {
		$stream = new \TYPO3\CMS\Core\Http\Stream(fopen('data:text/plain,' . TYPO3_version, 'r'));
		$response = $response->withBody($stream);
		$response = $response->withAddedHeader('Content-Type', 'text/plain');
		$response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');

		return $response;
	}
}