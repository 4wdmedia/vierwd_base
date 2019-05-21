<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseCommandController extends CommandController {

	private const CONNECTION_LOCAL = 'local';
	private const CONNECTION_REMOTE = 'remote';

	/**
	 * @var string
	 */
	private static $mysqlTempFile;

	/**
	 * @var ConnectionConfiguration
	 */
	private $connectionConfiguration;

	/**
	 * @var array
	 */
	private $dbConfig;

	public function __construct(ConnectionConfiguration $connectionConfiguration, CommandDispatcher $commandDispatcher = null) {
		$this->connectionConfiguration = $connectionConfiguration;
		$this->dbConfig = $this->connectionConfiguration->build();

		$this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
	}

	/**
	 * Import database from a file.
	 * This completly overwrites the current DB. As a security measure, we export the DB before importing a new one
	 *
	 * @param string $file filename for export
	 */
	public function importCommand(string $file = '') {
		$importFile = $file ? basename($file) : 'backup.sql.gz';
		$databaseFolder = dirname(PATH_site) . '/database';
		$importPath = $databaseFolder . '/' . $importFile;
		if (!file_exists($importPath)) {
			$this->outputLine('<error>Import file %s not found</error>', [$importFile]);
			$this->quit(1);
		}


		// Show info about import
		$bytes = filesize($importPath);
		$size = GeneralUtility::formatSize($bytes, 'B|Kb|Mb|Gb|Tb|Pb', 1024);
		$this->outputLine('DB Import of %s (%s)', [$importFile, $size]);


		if (!$this->isDbEmpty()) {
			// Create backup first
			$this->createBackup();
		}


		// Perform import
		$commandLine = array_merge(['mysql'], $this->buildConnectionArguments(), ['--default-character-set=utf8mb4']);
		$mysqlProcess = new Process($commandLine);
		$commandLine = 'gzip -c -d ' . escapeshellarg($importPath) . ' | ' . $mysqlProcess->getCommandLine();

		$process = new Process($commandLine);
		$process->setTimeout(0.0);
		$process->inheritEnvironmentVariables();
		$process->run($this->buildStreamOutput());
		$this->outputLine('<info>Import complete</info>');


		// Clear cache
		$output = $this->commandDispatcher->executeCommand('cache:flush');
		$this->outputLine($output);
	}

	/**
	 * Import database from the current ServiceArea or Live-Server.
	 * This completly overwrites the current DB. As a security measure, we export the DB before importing a new one
	 */
	public function kbImportCommand() {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !$config['ssh']) {
			$this->outputLine('<error>No SSH config found</error>');
			$this->quit(1);
		}

		// create backup first
		$this->createBackup();

		$commandLine = array_merge(['mysql'], $this->buildConnectionArguments(), ['--default-character-set=utf8mb4']);
		$localMysqlProcess = new Process($commandLine);

		$commandLine = $this->getExportDataTablesCommand(self::CONNECTION_REMOTE) . ' 2>/dev/null; ' . $this->getExportStructureTablesCommand(self::CONNECTION_REMOTE) . ' 2>/dev/null';
		$remoteMysqlProcess = new Process($commandLine);

		$command = [
			'ssh',
			'-C',
			$config['ssh']['arguments'],
			$config['ssh']['liveUser'] . '@' . $config['ssh']['liveHost'],
			$remoteMysqlProcess->getCommandLine(),
		];
		$importProcess = new Process(array_filter($command));

		$importProcess = new Process($importProcess->getCommandLine() . ' | ' . $localMysqlProcess->getCommandLine());
		$importProcess->setTimeout(0.0);
		$importProcess->inheritEnvironmentVariables();
		$importProcess->run($this->buildStreamOutput());
		$this->outputLine('<info>Import complete</info>');

		// Clear cache
		$this->commandDispatcher->executeCommand('cache:flush');
	}

	/**
	 * Export database
	 *
	 * Export the database (all tables) directly to a file.
	 * The mysqldump binary must be available in the path for this command to work.
	 * This obviously only works when MySQL is used as DBMS.
	 *
	 * A filename can be given to save the gzipped output. If no file is given var/database/backup.sql.gz will be used
	 *
	 * <b>Example:</b> <code>%command.full_name% --file db.sql.gz</code>
	 *
	 * <warning>This command passes the plain text database password to the command line process.</warning>
	 * This means, that users that have the permission to observe running processes,
	 * will be able to read your password.
	 * If this imposes a security risk for you, then refrain from using this command!
	 *
	 * @param string $file filename for export
	 */
	public function exportCommand(string $file = '') {
		if ($this->isDbEmpty()) {
			$this->outputLine('<error>Database is empty</error>');
			$this->quit(1);
			return;
		}

		$command1 = $this->getExportDataTablesCommand();
		$command2 = $this->getExportStructureTablesCommand();

		$exportFile = $file ? basename($file, '.gz') : 'backup.sql';
		$databaseFolder = dirname(PATH_site) . '/database';
		if (!is_dir($databaseFolder)) {
			GeneralUtility::mkdir($databaseFolder);
		}
		$exportPath = $databaseFolder . '/' . $exportFile;

		// delete old export file
		if (file_exists($exportPath . '.gz')) {
			unlink($exportPath . '.gz');
		}

		$process1 = new Process($command1 . '> ' . escapeshellarg($exportPath));
		$process1->setTimeout(0.0);
		$exitCode = $process1->run();
		if ($exitCode) {
			$this->outputLine('<error>Could not export database</error>');
			unlink($exportPath);
			$this->quit($exitCode);
			return;
		}

		$process2 = new Process($command2 . '>> ' . escapeshellarg($exportPath));
		$process2->setTimeout(0.0);
		$exitCode = $process2->run();
		if ($exitCode) {
			$this->outputLine('<error>Could not export database</error>');
			unlink($exportPath);
			$this->quit($exitCode);
			return;
		}

		$process3 = new Process(['gzip', $exportPath]);
		$process3->setTimeout(0.0);
		$exitCode = $process3->run();
		if ($exitCode) {
			$this->outputLine('<error>Could not gzip export</error>');
			unlink($exportPath);
			unlink($exportPath . '.gz');
			$this->quit($exitCode);
			return;
		}

		$bytes = filesize($exportPath . '.gz');
		$size = GeneralUtility::formatSize($bytes, 'B|Kb|Mb|Gb|Tb|Pb', 1024);
		$this->outputLine('<info>Export complete (%s)</info>', [$size]);

		// Clear cache
		$this->commandDispatcher->executeCommand('cache:flush');

		$this->quit(0);
	}

	/**
	 * create a backup of the database.
	 */
	protected function createBackup() {
		$this->outputLine('Creating a backup first');
		$exportFile = 'backup-' . date('Y-m-d-H:i:s') . '.sql.gz';
		$output = $this->commandDispatcher->executeCommand('vierwd:database:export', ['--file', $exportFile]);
		$this->outputLine(' ' . str_replace("\n", "\n  ", $output));
	}

	/**
	 * get the command line to export all tables containing export worthy data
	 */
	protected function getExportDataTablesCommand(string $type = self::CONNECTION_LOCAL): string {
		$additionalArguments = [
			'--default-character-set=utf8mb4',
			'--set-charset',
			'--net_buffer_length=16000',
			'--extended-insert',
		];

		foreach ($this->getIgnoredTables() as $table) {
			$additionalArguments[] = sprintf('--ignore-table=%s.%s', $this->dbConfig['dbname'], $table);
		}

		$connectionArguments = $type === self::CONNECTION_LOCAL ? $this->buildConnectionArguments() : $this->buildRemoteConnectionArguments();
		$commandLine = array_merge(['mysqldump'], $connectionArguments, $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	/**
	 * get the command line to export all tables where we only need the structure
	 */
	protected function getExportStructureTablesCommand(string $type = self::CONNECTION_LOCAL): string {
		$additionalArguments = [
			'--default-character-set=utf8mb4',
			'--set-charset',
			'--no-data',
		];

		foreach ($this->getIgnoredTables() as $table) {
			$additionalArguments[] = $table;
		}

		$connectionArguments = $type === self::CONNECTION_LOCAL ? $this->buildConnectionArguments() : $this->buildRemoteConnectionArguments();
		$commandLine = array_merge(['mysqldump'], $connectionArguments, $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	/**
	 * show the last changed timestamps for all tables.
	 *
	 * this only works for tables which have the default TYPO3 tstamp or crdate fields
	 */
	public function lastChangeCommand() {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTables();
		foreach ($tables as $table) {
			$hasTstamp = $table->hasColumn('tstamp');
			$hasCrdate = $table->hasColumn('crdate');
			$select = '';
			if ($hasTstamp && $hasCrdate) {
				$select = 'IF(MAX(crdate) > MAX(tstamp), MAX(crdate), MAX(tstamp))';
			} else if ($hasTstamp) {
				$select = 'MAX(tstamp)';
			} else if ($hasCrdate) {
				$select = 'MAX(crdate)';
			} else {
				continue;
			}
			$result = $connection->query('SELECT ' . $select . ' FROM `' . $table->getName() . '`');

			$time = $result->fetchColumn();
			if (!$time) {
				// probably no rows in the table
				continue;
			}
			$this->outputLine(date('Y-m-d H:i', $time) . ' ' . $table->getName());
		}
	}

	protected function isDbEmpty(): bool {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTableNames();

		return !$tables;
	}

	protected function getIgnoredTables(): array {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTableNames();

		$ignoreTables = [
			'sys_log',
			'sys_file_processedfile',
			'tx_extensionmanager_domain_model_extension',
		];
		$ignoreTables = array_intersect($ignoreTables, $tables);
		$prefixes = ['cf_', 'zzz_deleted_', 'cache_', 'index_', 'tx_realurl_'];
		foreach ($tables as $table) {
			foreach ($prefixes as $prefix) {
				if (substr($table, 0, strlen($prefix)) === $prefix) {
					$ignoreTables[] = $table;
				}
			}
		}

		return $ignoreTables;
	}

	/**
	 * stream output of a process to our output
	 */
	protected function buildStreamOutput() {
		return function ($type, $output) {
			if (Process::OUT === $type) {
				// Explicitly just echo out for now (avoid symfony console formatting)
				echo $output;
			} else {
				$this->output('<error>' . $output . '</error>');
			}
		};
	}

	private function buildRemoteConnectionArguments(): array {
		$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

		$dbConfiguration = $configurationManager->getConfigurationValueByPath('DB/Connections/Default');

		$arguments = [];
		if (!empty($dbConfiguration['user'])) {
			$arguments[] = '-u';
			$arguments[] = $dbConfiguration['user'];
		}
		if (!empty($dbConfiguration['password'])) {
			$arguments[] = '-p' . $dbConfiguration['password'];
		}
		if (!empty($dbConfiguration['host'])) {
			$arguments[] = '-h';
			$arguments[] = $dbConfiguration['host'];
		}
		if (!empty($dbConfiguration['port'])) {
			$arguments[] = '-P';
			$arguments[] = $dbConfiguration['port'];
		}
		if (!empty($dbConfiguration['unix_socket'])) {
			$arguments[] = '-S';
			$arguments[] = $dbConfiguration['unix_socket'];
		}
		$arguments[] = $dbConfiguration['dbname'];

		return $arguments;
	}

	/**
	 * copied from \Helhum\Typo3Console\Database\Process\MysqlCommand
	 */
	private function buildConnectionArguments(): array {
		$arguments = [];

		$configFile = $this->createTemporaryMysqlConfigurationFile();
		if ($configFile) {
			$arguments[] = '--defaults-extra-file=' . $configFile;
		}
		if (!empty($this->dbConfig['host'])) {
			$arguments[] = '-h';
			$arguments[] = $this->dbConfig['host'];
		}
		if (!empty($this->dbConfig['port'])) {
			$arguments[] = '-P';
			$arguments[] = $this->dbConfig['port'];
		}
		if (!empty($this->dbConfig['unix_socket'])) {
			$arguments[] = '-S';
			$arguments[] = $this->dbConfig['unix_socket'];
		}
		$arguments[] = $this->dbConfig['dbname'];

		return $arguments;
	}

	/**
	 * copied from \Helhum\Typo3Console\Database\Process\MysqlCommand
	 */
	private function createTemporaryMysqlConfigurationFile() {
		if (empty($this->dbConfig['user']) && !isset($this->dbConfig['password'])) {
			return null;
		}
		if (self::$mysqlTempFile !== null && file_exists(self::$mysqlTempFile)) {
			return self::$mysqlTempFile;
		}
		$userDefinition = '';
		$passwordDefinition = '';
		if (!empty($this->dbConfig['user'])) {
			$userDefinition = sprintf('user="%s"', $this->dbConfig['user']);
		}
		if (!empty($this->dbConfig['password'])) {
			$passwordDefinition = sprintf('password="%s"', $this->dbConfig['password']);
		}
		$confFileContent = <<<EOF
[mysqldump]
$userDefinition
$passwordDefinition

[client]
$userDefinition
$passwordDefinition
EOF;
		self::$mysqlTempFile = tempnam(sys_get_temp_dir(), 'typo3_console_my_cnf_');
		file_put_contents(self::$mysqlTempFile, $confFileContent);
		register_shutdown_function('unlink', self::$mysqlTempFile);

		return self::$mysqlTempFile;
	}
}
