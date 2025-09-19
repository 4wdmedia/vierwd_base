<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
	name: 'vierwd:database:import',
	description: 'Import database from a file.',
)]
class ImportCommand extends BaseDatabaseCommand {

	protected function configure(): void {
		$this->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Filename for import', 'backup.sql.gz');
		$this->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not create a backup before import');
		$this->setHelp('This completly overwrites the current DB. As a security measure, we export the DB before importing a new one');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$file = $input->getOption('file');
		if (!is_string($file)) {
			$output->writeln('<error>Please enter a single file name</error>');
			return Command::FAILURE;
		}

		$importFile = $file ? basename($file) : 'backup.sql.gz';
		$databaseFolder = Environment::getProjectPath() . '/database';
		$importPath = $databaseFolder . '/' . $importFile;
		if (!file_exists($importPath)) {
			$output->writeln(sprintf('<error>Import file %s not found</error>', $importFile));
			return Command::FAILURE;
		}

		$this->ensureMysqlConfigExists();

		// Show info about import
		$bytes = (int)filesize($importPath);
		$size = GeneralUtility::formatSize($bytes, 'B|Kb|Mb|Gb|Tb|Pb', 1024);
		$output->writeln(sprintf('DB Import of %s (%s)', $importFile, $size));


		if (!$this->isDbEmpty() && !$input->getOption('no-backup')) {
			// Create backup first
			$this->createBackup();
		}


		// Perform import
		$commandLine = array_merge([
			'mysql',
			'--defaults-file=' . $this->getMysqlDefaultsFilePath(),
			'--default-character-set=utf8mb4',
		]);
		$mysqlProcess = new Process($commandLine);
		$commandLine = 'gzip -c -d ' . escapeshellarg($importPath) . ' | ' . $mysqlProcess->getCommandLine();

		$process = Process::fromShellCommandline($commandLine);
		$output->writeln('<comment>' . $process->getCommandLine() . '</comment>', OutputInterface::VERBOSITY_VERY_VERBOSE);
		$process->setTimeout(0.0);
		$exitCode = $process->run($this->buildStreamOutput());
		if ($exitCode) {
			$output->writeln('<error>Could not import database</error>');
			$output->writeln('<error>' . $process->getErrorOutput() . '</error>');
			return $exitCode;
		}

		$output->writeln('<info>Import complete</info>');

		// Clear cache
		$output->writeln($this->commandDispatcher->executeCommand('cache:flush'));

		return Command::SUCCESS;
	}

}
