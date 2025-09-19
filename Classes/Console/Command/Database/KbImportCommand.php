<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Vierwd\VierwdBase\Console\Command\ServerTrait;

#[AsCommand(
	name: 'vierwd:database:kb-import',
	description: 'Import database from the current ServiceArea or Live-Server.',
)]
class KbImportCommand extends BaseDatabaseCommand {

	use ServerTrait;

	protected function configure(): void {
		$this->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a backup before import');
		$this->addOption('no-data', null, InputOption::VALUE_NONE, 'Only create tables. Do not import data');
		$this->addOption('content-only', null, InputOption::VALUE_NONE, 'Only import content tables (tt_content, pages and sys_file_reference)');
		$servers = $this->getConfiguredServers();
		$this->addArgument('server', InputArgument::OPTIONAL, 'From which server do you want to sync? ' . implode(', ', array_keys($servers)), 'live');
		$this->setHelp('This completly overwrites the current DB. As a security measure, we export the DB before importing a new one');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !is_array($config) || !$config['ssh']) {
			$output->writeln('<error>No SSH config found</error>');
			return Command::FAILURE;
		}

		try {
			$server = $input->getArgument('server');
			$serverPath = $this->getConfiguredServerPath($input);
			assert(is_string($server));
		} catch (\Throwable $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
		[$host, $serverPath] = explode(':', $serverPath);

		$this->ensureMysqlConfigExists();

		if (!$this->isDbEmpty() && !$input->getOption('no-backup')) {
			// Create backup first
			$this->createBackup();
		}

		$noData = $input->getOption('no-data');
		$contentOnly = $input->getOption('content-only');
		assert(is_bool($contentOnly));

		$commandLine = array_merge([
			'mysql',
			'--defaults-file=' . $this->getMysqlDefaultsFilePath(),
			'--default-character-set=utf8mb4',
		]);
		$localMysqlProcess = new Process($commandLine);

		if ($noData) {
			$commandLine = $this->getExportStructureTablesCommand($server, $serverPath, true) . ' 2>/dev/null';
		} else {
			$commandLine = $this->getExportDataTablesCommand($server, $serverPath, $contentOnly) . ' 2>/dev/null | grep -vF \'/*M!999999\- enable the sandbox mode */\'; ' . $this->getExportStructureTablesCommand($server, $serverPath) . ' 2>/dev/null | grep -vF \'/*M!999999\- enable the sandbox mode */\'';
		}
		$remoteMysqlProcess = Process::fromShellCommandline($commandLine);

		$command = [
			'ssh',
			'-C',
			$config['ssh']['arguments'],
			$host,
			$remoteMysqlProcess->getCommandLine(),
		];
		$importProcess = new Process(array_filter($command));

		$importProcess = Process::fromShellCommandline($importProcess->getCommandLine() . ' | ' . $localMysqlProcess->getCommandLine());
		$output->writeln('<comment>' . $importProcess->getCommandLine() . '</comment>', OutputInterface::VERBOSITY_VERY_VERBOSE);
		$importProcess->setTimeout(0.0);
		$exitCode = $importProcess->run($this->buildStreamOutput());
		if ($exitCode) {
			$output->writeln('<error>Could not import database</error>');
			$output->writeln('<error>' . $importProcess->getErrorOutput() . '</error>');
			return $exitCode;
		}

		$output->writeln('<info>Import complete</info>');

		// Clear cache
		$this->commandDispatcher->executeCommand('cache:flush');

		return Command::SUCCESS;
	}

}
