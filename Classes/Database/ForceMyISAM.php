<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Database;

use Doctrine\DBAL\Schema\Schema;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 tables are generated as InnoDB by default. Many tables would be better
 * as MyISAM, because they do not use transactions or foreign keys and
 * the performance is way faster on our DomainFactory server with MyISAM instead
 * of InnoDB.
 */
class ForceMyISAM extends SqlReader {

	/** @var array */
	private static $MyISAMTables = [
		'sys_file',
		'sys_file_metadata',
		'sys_file_processedfile',
		'sys_file_reference',
		'sys_history',
		'sys_log',
		'sys_refindex',
		'tt_content',
		'tx_extensionmanager_domain_model_extension',
	];

	/** @var array */
	private static $tablePrefixes = ['cf_', 'cache_', 'index_'];

	public function getCreateTableStatementArray(string $dumpContent): array {
		$statements = parent::getCreateTableStatementArray($dumpContent);

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$platform = $connectionPool->getConnectionByName('Default')->getDatabasePlatform();

		$statements = array_map(function(string $statement) use ($platform) {
			$parser = GeneralUtility::makeInstance(Parser::class, $statement);
			$tables = $parser->parse();
			$table = $tables[0];
			$forceMyISAM = in_array($table->getName(), static::$MyISAMTables);
			foreach (static::$tablePrefixes as $tablePrefix) {
				$forceMyISAM = $forceMyISAM || substr($table->getName(), 0, strlen($tablePrefix)) === $tablePrefix;
			}

			if ($forceMyISAM) {
				$table->addOption('engine', 'MyISAM');
				$schema = new Schema([$table]);
				$sql = $schema->toSql($platform);
				return $sql[0] . ';';
			}

			return $statement;
		}, $statements);

		return $statements;
	}
}
