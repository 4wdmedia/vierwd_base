<?php

namespace Vierwd\VierwdBase\Resource;

class LocalDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver {

	public function sanitizeFileName($fileName, $charset = '') {
		$fileName = parent::sanitizeFileName($fileName, $charset);

		$pathinfo = pathinfo($fileName);
		if ($pathinfo['extension'] !== $fileName && strtolower($pathinfo['extension']) !== $pathinfo['extension']) {
			$fileName = $pathinfo['filename'] . '.' . strtolower($pathinfo['extension']);
		}

		return $fileName;
	}
}