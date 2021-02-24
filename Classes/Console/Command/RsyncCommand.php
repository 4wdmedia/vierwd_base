<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RsyncCommand extends Command {

	/**
	 * @phpstan-return void
	 */
	protected function configure() {
		$this->setDescription('Import database from the current ServiceArea or Live-Server');
		$this->setHelp('This completly overwrites the current DB. As a security measure, we export the DB before importing a new one');
		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a trial run with no changes made');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !$config['ssh']) {
			$output->writeln('<error>No SSH config found</error>');
			return 1;
		}

		if ($config['ssh']['serverPath'] === '~/kundenbereich/') {
			// serverPath still has default value
			$output->writeln('<error>No ssh server path set in extension configuration</error>');
			return 1;
		}

		$folders = $this->getRsyncFolders();
		if (!$folders) {
			$output->writeln('<error>No folders configured for rsync</error>');
			return 1;
		}
		$folders = $this->transformFolders($folders, $config['ssh']);

		$excludeFrom = '';
		if (file_exists(Environment::getProjectPath() . '/rsync-excludes.txt')) {
			$excludeFrom = '--exclude-from=rsync-excludes.txt';
		}

		$command = array_filter(array_merge([
			'rsync',
			($dryRun ? '--dry-run' : ''),
			'--exclude', '_processed_',
			$excludeFrom,
			'--times',
			'--links',
			'--compress',
			'--recursive',
			'--itemize-changes',
			'--delete',
			'--relative',
		], $folders, [
			Environment::getProjectPath(),
		]));

		$importProcess = new Process($command);
		$importProcess->setTimeout(0.0);
		$importProcess->run($this->buildStreamOutput($output));

		if ($importProcess->getExitCode()) {
			$output->writeln('<error>Import failed</error>');
		} else if (!$dryRun) {
			$output->writeln('<info>Import complete</info>');
		}

		return 0;
	}

	protected function getRsyncFolders(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
		$storages = $queryBuilder->select('configuration')
			->from('sys_file_storage')
			->andWhere('configuration LIKE "<%"')
			->execute();

		$flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
		$folders = [];
		foreach ($storages as $storage) {
			$configuration = $flexFormService->convertFlexFormContentToArray($storage['configuration']);
			if (!$configuration || !isset($configuration['pathType']) || $configuration['pathType'] !== 'relative') {
				continue;
			}
			$folders[] = $configuration['basePath'];
		}

		if (!$folders) {
			// check if fileadmin exists and use that
			if (is_dir(Environment::getPublicPath() . '/fileadmin/')) {
				$folders[] = 'fileadmin/';
			}
		}

		if (is_dir(Environment::getPublicPath() . '/uploads/')) {
			$folders[] = 'uploads/';
		}

		return $folders;
	}

	protected function transformFolders(array $folders, array $sshConfig): array {
		$publicPrefix = trim(str_replace(Environment::getProjectPath(), '', Environment::getPublicPath()), '/') . '/';
		if ($publicPrefix !== '/') {
			$publicPrefix = '/' . $publicPrefix;
		}
		$serverPath = rtrim($sshConfig['serverPath'], '/') . '/';

		$folders = array_map(function(string $path) use ($sshConfig, $serverPath, $publicPrefix) {
			$path = $serverPath . '.' . $publicPrefix . $path;

			// normalize path a bit (replace ../)
			$path = preg_replace('/[^\/]+\/\.\.\//', '', $path);
			return $sshConfig['liveUser'] . '@' . $sshConfig['liveHost'] . ':' . $path;
		}, $folders);

		return $folders;
	}

	/**
	 * stream output of a process to our output
	 */
	protected function buildStreamOutput(OutputInterface $symfonyOutput): \closure {
		return function ($type, $output) use ($symfonyOutput) {
			if (Process::OUT === $type) {
				// Explicitly just echo out for now (avoid symfony console formatting)
				echo $output;
			} else {
				$symfonyOutput->write('<error>' . $output . '</error>');
			}
		};
	}
}
