<?php

namespace Vierwd\VierwdBase\Database\Schema;

use Doctrine\DBAL\Schema\Schema;

class ConnectionMigrator extends \TYPO3\CMS\Core\Database\Schema\ConnectionMigrator {

	protected function buildExpectedSchemaDefinitions(string $connectionName): Schema {
		$schema = parent::buildExpectedSchemaDefinitions($connectionName);
		foreach ($schema->getTables() as $table) {
			$table->addOption('charset', 'utf8mb4');
			$table->addOption('collate', 'utf8mb4_unicode_ci');
		}

		return $schema;
	}
}