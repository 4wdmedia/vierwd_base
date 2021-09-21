<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExportCommand extends BaseDatabaseCommand {

	/**
	 * @phpstan-return void
	 */
	protected function configure() {
		$this->setDescription('Export database');
		$this->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Filename for export', 'backup.sql.gz');
		$this->setHelp(implode("\n", [
			'Export the database (all tables) directly to a file.',
			'The mysqldump binary must be available in the path for this command to work.',
			'This obviously only works when MySQL is used as DBMS.',
			'',
			'A filename can be given to save the gzipped output. If no file is given var/database/backup.sql.gz will be used',
			'',
			'<b>Example:</b> <code>%command.full_name% --file db.sql.gz</code>',
		]));
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$file = $input->getOption('file');
		if (!is_string($file)) {
			$output->writeln('<error>Please enter a single file name</error>');
			return 1;
		}

		if ($this->isDbEmpty()) {
			$output->writeln('<error>Database is empty</error>');
			return 1;
		}

		$command1 = $this->getExportDataTablesCommand();
		$command2 = $this->getExportStructureTablesCommand();

		$exportFile = basename($file, '.gz');
		$databaseFolder = Environment::getProjectPath() . '/database';
		if (!is_dir($databaseFolder)) {
			GeneralUtility::mkdir($databaseFolder);
		}
		$exportPath = $databaseFolder . '/' . $exportFile;

		// delete old export file
		if (file_exists($exportPath . '.gz')) {
			unlink($exportPath . '.gz');
		}

		$process1 = Process::fromShellCommandline($command1 . '> ' . escapeshellarg($exportPath));
		$process1->setTimeout(0.0);
		$exitCode = $process1->run();
		if ($exitCode) {
			$output->writeln('<error>Could not export database</error>');
			unlink($exportPath);
			return $exitCode;
		}

		$process2 = Process::fromShellCommandline($command2 . '>> ' . escapeshellarg($exportPath));
		$process2->setTimeout(0.0);
		$exitCode = $process2->run();
		if ($exitCode) {
			$output->writeln('<error>Could not export database</error>');
			unlink($exportPath);
			return $exitCode;
		}

		$process3 = new Process(['gzip', $exportPath]);
		$process3->setTimeout(0.0);
		$exitCode = $process3->run();
		if ($exitCode) {
			$output->writeln('<error>Could not gzip export</error>');
			unlink($exportPath);
			unlink($exportPath . '.gz');
			return $exitCode;
		}

		$bytes = (int)filesize($exportPath . '.gz');
		$size = GeneralUtility::formatSize($bytes, 'B|Kb|Mb|Gb|Tb|Pb', 1024);
		$output->writeln(sprintf('<info>Export complete (%s)</info>', $size));

		// Clear cache
		$this->commandDispatcher->executeCommand('cache:flush');

		return 0;
	}
}
