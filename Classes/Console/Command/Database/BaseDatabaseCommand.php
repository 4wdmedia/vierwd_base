<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class BaseDatabaseCommand extends Command {

	protected const CONNECTION_LOCAL = 'local';
	protected const CONNECTION_REMOTE = 'remote';

	private static ?string $mysqlTempFile = null;

	private ConnectionConfiguration $connectionConfiguration;

	private array $dbConfig = [];

	/** @phpstan-ignore-next-line Uninitialized property. Give it default value or assign it in the constructor. */
	protected InputInterface $input;

	/** @phpstan-ignore-next-line Uninitialized property. Give it default value or assign it in the constructor. */
	protected OutputInterface $output;

	/** @phpstan-ignore-next-line Uninitialized property. Give it default value or assign it in the constructor. */
	protected CommandDispatcher $commandDispatcher;

	public function __construct(string $name = null, ConnectionConfiguration $connectionConfiguration = null) {
		parent::__construct($name);
		$this->connectionConfiguration = $connectionConfiguration ?: new ConnectionConfiguration();
	}

	protected function initialize(InputInterface $input, OutputInterface $output): void {
		parent::initialize($input, $output);

		$this->input = $input;
		$this->output = $output;

		$this->dbConfig = $this->connectionConfiguration->build();

		$typo3cms = Environment::getProjectPath() . '/vendor/bin/typo3cms';
		$this->commandDispatcher = CommandDispatcher::create($typo3cms);
	}

	/**
	 * create a backup of the database.
	 */
	protected function createBackup(): void {
		$this->output->writeln('Creating a backup first');
		$exportFile = 'backup-' . date('Y-m-d-H:i:s') . '.sql.gz';
		$output = $this->commandDispatcher->executeCommand('vierwd:database:export', ['--file', $exportFile]);
		$this->output->writeln(' ' . str_replace("\n", "\n  ", $output));
	}

	/**
	 * get the command line to export all tables containing export worthy data
	 */
	protected function getExportDataTablesCommand(string $type = self::CONNECTION_LOCAL): string {
		$additionalArguments = [
			'--skip-lock-tables',
			'--default-character-set=utf8mb4',
			'--set-charset',
			'--net_buffer_length=16000',
			'--extended-insert',
		];

		if ($type === self::CONNECTION_LOCAL) {
			$dbConfig = $this->dbConfig;
		} else {
			$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
			$dbConfig = $configurationManager->getConfigurationValueByPath('DB/Connections/Default');
		}

		assert(is_array($dbConfig));

		foreach ($this->getIgnoredTables() as $table) {
			$additionalArguments[] = sprintf('--ignore-table=%s.%s', $dbConfig['dbname'], $table);
		}

		$connectionArguments = $type === self::CONNECTION_LOCAL ? $this->buildConnectionArguments() : $this->buildRemoteConnectionArguments();
		$commandLine = array_merge(['mysqldump'], $connectionArguments, $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	/**
	 * get the command line to export all tables where we only need the structure
	 */
	protected function getExportStructureTablesCommand(string $type = self::CONNECTION_LOCAL, bool $allTables = false): string {
		$additionalArguments = [
			'--skip-lock-tables',
			'--default-character-set=utf8mb4',
			'--set-charset',
			'--no-data',
		];

		if (!$allTables) {
			foreach ($this->getIgnoredTables() as $table) {
				$additionalArguments[] = $table;
			}
		}

		$connectionArguments = $type === self::CONNECTION_LOCAL ? $this->buildConnectionArguments() : $this->buildRemoteConnectionArguments();
		$commandLine = array_merge(['mysqldump'], $connectionArguments, $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	protected function isDbEmpty(): bool {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTableNames();

		return !$tables;
	}

	protected function getIgnoredTables(): array {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		assert(is_array($config));

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTableNames();

		$ignoreTables = [
			'sys_log',
			'sys_file_processedfile',
			'tx_extensionmanager_domain_model_extension',
			'tx_crawler_queue',
		];
		if (isset($config['additionalIgnoredTables']) && is_array($config['additionalIgnoredTables'])) {
			// set additional ignored tables in ext_localconf with `$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vierwd_base']['additionalIgnoredTables']`
			$ignoreTables = array_merge($ignoreTables, $config['additionalIgnoredTables']);
		}
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
	protected function buildStreamOutput(): \Closure {
		return function ($type, $output): void {
			if (Process::OUT === $type) {
				// Explicitly just echo out for now (avoid symfony console formatting)
				echo $output;
			} else {
				$this->output->writeln('<error>' . $output . '</error>');
			}
		};
	}

	private function buildRemoteConnectionArguments(): array {
		$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

		$dbConfiguration = $configurationManager->getConfigurationValueByPath('DB/Connections/Default');
		assert(is_array($dbConfiguration));

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
	protected function buildConnectionArguments(): array {
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
	private function createTemporaryMysqlConfigurationFile(): ?string {
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
		$mysqlTempFile = tempnam(sys_get_temp_dir(), 'typo3_console_my_cnf_');
		if ($mysqlTempFile === false) {
			throw new \Exception('Could not create temporary file for MySQL connection', 1604071267);
		}
		self::$mysqlTempFile = $mysqlTempFile;
		file_put_contents(self::$mysqlTempFile, $confFileContent);
		register_shutdown_function('unlink', self::$mysqlTempFile);

		return self::$mysqlTempFile;
	}
}
