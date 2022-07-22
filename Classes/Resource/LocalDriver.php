<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource;

class LocalDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver {

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
	 */
	public function sanitizeFileName($fileName, $charset = 'utf-8') {
		$fileName = parent::sanitizeFileName($fileName, $charset);

		$pathinfo = pathinfo($fileName);
		if (isset($pathinfo['extension']) && $pathinfo['extension'] !== $fileName && strtolower($pathinfo['extension']) !== $pathinfo['extension']) {
			$fileName = $pathinfo['filename'] . '.' . strtolower($pathinfo['extension']);
		}

		return $fileName;
	}

}
