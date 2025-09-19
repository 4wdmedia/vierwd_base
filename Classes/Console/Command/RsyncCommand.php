<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
	name: 'vierwd:rsync:down',
	description: 'Copy all file storage from server to local dev environment.',
)]
class RsyncCommand extends Command {

	use ServerTrait;

	protected function configure(): void {
		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a trial run with no changes made');
		$servers = $this->getConfiguredServers();
		$this->addArgument('server', InputArgument::OPTIONAL, 'From which server do you want to sync? ' . implode(', ', array_keys($servers)), 'live');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!is_array($config) || !$config['ssh']) {
			$output->writeln('<error>No SSH config found</error>');
			return Command::FAILURE;
		}

		$dryRun = $input->getOption('dry-run');

		try {
			$serverPath = $this->getConfiguredServerPath($input);
		} catch (\Throwable $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}

		$folders = $this->getRsyncFolders();
		if (!$folders) {
			$output->writeln('<error>No folders configured for rsync</error>');
			return Command::FAILURE;
		}
		$folders = $this->transformFolders($folders, $serverPath);

		$excludeFrom = '';
		if (file_exists(Environment::getProjectPath() . '/rsync-excludes.txt')) {
			$excludeFrom = '--exclude-from=rsync-excludes.txt';
		}

		$sshArguments = $config['ssh']['arguments'] ?? '';
		if ($sshArguments) {
			$sshArguments = [
				'-e',
				'ssh ' . $sshArguments,
			];
		} else {
			$sshArguments = [];
		}

		$command = array_filter(array_merge([
			'rsync',
			($dryRun ? '--dry-run' : ''),
		], $sshArguments, [
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
		$output->writeln('<comment>' . $importProcess->getCommandLine() . '</comment>', OutputInterface::VERBOSITY_VERY_VERBOSE);
		$importProcess->setTimeout(0.0);
		$importProcess->run($this->buildStreamOutput($output));

		if ($importProcess->getExitCode()) {
			$output->writeln('<error>Import failed</error>');
			return Command::FAILURE;
		} else if (!$dryRun) {
			$output->writeln('<info>Import complete</info>');
		}

		return Command::SUCCESS;
	}

	protected function getRsyncFolders(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
		$storages = $queryBuilder->select('configuration')
			->from('sys_file_storage')
			->andWhere($queryBuilder->expr()->like('configuration', $queryBuilder->createNamedParameter('<%')))
			->andWhere($queryBuilder->expr()->eq('driver', $queryBuilder->createNamedParameter('Local')))
			->executeQuery()
			->fetchAllAssociative()
		;

		$flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
		$folders = [];
		foreach ($storages as $storage) {
			assert(is_string($storage['configuration']));
			$configuration = $flexFormService->convertFlexFormContentToArray($storage['configuration']);
			if (!$configuration || !isset($configuration['pathType']) || $configuration['pathType'] !== 'relative') {
				continue;
			}
			$folders[] = $configuration['basePath'];
		}

		$folders = array_filter($folders, function(string $path): bool {
			return !str_starts_with($path, 'typo3conf/ext/');
		});

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

	protected function transformFolders(array $folders, string $serverPath): array {
		$publicPrefix = trim(str_replace(Environment::getProjectPath(), '', Environment::getPublicPath()), '/') . '/';
		if ($publicPrefix !== '/') {
			$publicPrefix = '/' . $publicPrefix;
		}
		$serverPath = rtrim($serverPath, '/') . '/';

		$folders = array_map(function(string $path) use ($serverPath, $publicPrefix) {
			$path = $serverPath . '.' . $publicPrefix . $path;

			// normalize path a bit (replace ../)
			return preg_replace('/[^\/]+\/\.\.\//', '', $path);
		}, $folders);

		return $folders;
	}

	/**
	 * stream output of a process to our output
	 */
	protected function buildStreamOutput(OutputInterface $symfonyOutput): \Closure {
		return function ($type, $output) use ($symfonyOutput): void {
			if (Process::OUT === $type) {
				// Explicitly just echo out for now (avoid symfony console formatting)
				echo $output;
			} else {
				$symfonyOutput->write('<error>' . $output . '</error>');
			}
		};
	}

}
