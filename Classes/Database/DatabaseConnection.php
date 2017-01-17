<?php

namespace Vierwd\VierwdBase\Database;

/**
 * TYPO3 uses utf8 charset instead of better utf8mb4 charset. Overwrite
 * DatabaseConnection and fix the charset
 */
class DatabaseConnection extends \TYPO3\CMS\Core\Database\DatabaseConnection {

	public function initialize() {
		parent::initialize();

		if ($this->connectionCharset !== 'utf8mb4') {
			$this->setConnectionCharset('utf8mb4');
		}
	}
}
