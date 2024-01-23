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

		$typo3Command = Environment::getProjectPath() . '/vendor/bin/typo3';
		$this->commandDispatcher = CommandDispatcher::create($typo3Command);
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
	protected function getExportDataTablesCommand(?string $dbName = null, ?string $server = null, bool $contentOnly = false): string {
		$additionalArguments = [
			'--defaults-file=' . $this->getMysqlDefaultsFilePath($server),
			'--skip-lock-tables',
			'--default-character-set=utf8mb4',
			'--set-charset',
			'--net_buffer_length=16000',
			'--extended-insert',
		];

		if (!$dbName) {
			$dbName = $this->dbConfig['dbname'];
			if ($server) {
				$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
				$dbConfig = $configurationManager->getConfigurationValueByPath('DB/Connections/Default');
				assert(is_array($dbConfig));
				$dbName = $dbConfig['dbname'];
			}
		}

		if ($contentOnly) {
			$additionalArguments[] = 'pages';
			$additionalArguments[] = 'sys_file_reference';
			$additionalArguments[] = 'tt_content';
		} else {
			foreach ($this->getIgnoredTables() as $table) {
				$additionalArguments[] = sprintf('--ignore-table=%s.%s', $dbName, $table);
			}
		}

		$commandLine = array_merge(['mysqldump'], $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	/**
	 * get the command line to export all tables where we only need the structure
	 */
	protected function getExportStructureTablesCommand(?string $server = null, bool $allTables = false): string {
		$additionalArguments = [
			'--defaults-file=' . $this->getMysqlDefaultsFilePath($server),
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

		$commandLine = array_merge(['mysqldump'], $additionalArguments);
		$process = new Process($commandLine);
		return $process->getCommandLine();
	}

	protected function isDbEmpty(): bool {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->createSchemaManager();
		$tables = $schemaManager->listTableNames();

		return !$tables;
	}

	protected function getIgnoredTables(): array {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		assert(is_array($config));

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		// Get all tables (even the filtered tables)
		$connection->getConfiguration()->setSchemaAssetsFilter(null);
		$schemaManager = $connection->createSchemaManager();
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

	protected function getMysqlDefaultsFilePath(?string $serverPath = null): string {
		if (!$serverPath) {
			return Environment::getProjectPath() . '/.my.cnf';
		}

		if (str_contains($serverPath, ':')) {
			[, $serverPath] = explode(':', $serverPath);
		}
		$serverPath = rtrim($serverPath, '/');

		return $serverPath . '/.my.cnf';
	}

}
