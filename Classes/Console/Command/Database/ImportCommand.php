<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCommand extends BaseDatabaseCommand {

	protected function configure(): void {
		$this->setDescription('Import database from a file.');
		$this->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Filename for import', 'backup.sql.gz');
		$this->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a backup before import');
		$this->setHelp('This completly overwrites the current DB. As a security measure, we export the DB before importing a new one');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$file = $input->getOption('file');
		if (!is_string($file)) {
			$output->writeln('<error>Please enter a single file name</error>');
			return 1;
		}

		$importFile = $file ? basename($file) : 'backup.sql.gz';
		$databaseFolder = Environment::getProjectPath() . '/database';
		$importPath = $databaseFolder . '/' . $importFile;
		if (!file_exists($importPath)) {
			$output->writeln(sprintf('<error>Import file %s not found</error>', $importFile));
			return 1;
		}


		// Show info about import
		$bytes = (int)filesize($importPath);
		$size = GeneralUtility::formatSize($bytes, 'B|Kb|Mb|Gb|Tb|Pb', 1024);
		$output->writeln(sprintf('DB Import of %s (%s)', $importFile, $size));


		if (!$this->isDbEmpty() && !$input->getOption('no-backup')) {
			// Create backup first
			$this->createBackup();
		}


		// Perform import
		$commandLine = array_merge(['mysql'], $this->buildConnectionArguments(), ['--default-character-set=utf8mb4']);
		$mysqlProcess = new Process($commandLine);
		$commandLine = 'gzip -c -d ' . escapeshellarg($importPath) . ' | ' . $mysqlProcess->getCommandLine();

		$process = Process::fromShellCommandline($commandLine);
		$process->setTimeout(0.0);
		$process->run($this->buildStreamOutput());
		$output->writeln('<info>Import complete</info>');


		// Clear cache
		$output->writeln($this->commandDispatcher->executeCommand('cache:flush'));

		return 0;
	}
}
