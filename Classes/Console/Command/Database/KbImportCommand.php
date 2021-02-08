<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class KbImportCommand extends BaseDatabaseCommand {

	/**
	 * @phpstan-return void
	 */
	protected function configure() {
		$this->setDescription('Import database from the current ServiceArea or Live-Server.');
		$this->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a backup before import');
		$this->addOption('no-data', null, InputOption::VALUE_NONE, 'Only create tables. Do not import data');
		$this->setHelp('This completly overwrites the current DB. As a security measure, we export the DB before importing a new one');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !$config['ssh']) {
			$output->writeln('<error>No SSH config found</error>');
			return 1;
		}

		if (!$this->isDbEmpty() && !$input->getOption('no-backup')) {
			// Create backup first
			$this->createBackup();
		}

		$noData = $input->getOption('no-data');

		$commandLine = array_merge(['mysql'], $this->buildConnectionArguments(), ['--default-character-set=utf8mb4']);
		$localMysqlProcess = new Process($commandLine);

		$commandLine = $this->getExportDataTablesCommand(self::CONNECTION_REMOTE) . ' 2>/dev/null; ' . $this->getExportStructureTablesCommand(self::CONNECTION_REMOTE) . ' 2>/dev/null';
		$remoteMysqlProcess = Process::fromShellCommandline($commandLine);

		$command = [
			'ssh',
			'-C',
			$config['ssh']['arguments'],
			$config['ssh']['liveUser'] . '@' . $config['ssh']['liveHost'],
			$remoteMysqlProcess->getCommandLine(),
		];
		$importProcess = new Process(array_filter($command));

		$importProcess = Process::fromShellCommandline($importProcess->getCommandLine() . ' | ' . $localMysqlProcess->getCommandLine());
		$importProcess->setTimeout(0.0);
		$importProcess->run($this->buildStreamOutput());
		$output->writeln('<info>Import complete</info>');

		// Clear cache
		if ($noData) {
			$this->commandDispatcher->executeCommand('cache:flush', ['--files-only']);
		} else {
			$this->commandDispatcher->executeCommand('cache:flush');
		}

		return 0;
	}
}
