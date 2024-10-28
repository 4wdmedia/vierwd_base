<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource;

class LocalDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver {

	/**
	 * {@inheritdoc}
	 */
	public function sanitizeFileName(string $fileName, string $charset = 'utf-8'): string {
		$fileName = parent::sanitizeFileName($fileName, $charset);

		$pathinfo = pathinfo($fileName);
		if (isset($pathinfo['extension']) && $pathinfo['extension'] !== $fileName && strtolower($pathinfo['extension']) !== $pathinfo['extension']) {
			$fileName = $pathinfo['filename'] . '.' . strtolower($pathinfo['extension']);
		}

		return $fileName;
	}

}
