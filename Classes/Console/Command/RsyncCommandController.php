<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Service\FlexFormService;

class RsyncCommandController extends CommandController {

	/**
	 * Import database from the current ServiceArea or Live-Server.
	 * This completly overwrites the current DB. As a security measure, we export the DB before importing a new one
	 *
	 * @param bool $dryRun
	 */
	public function downCommand(bool $dryRun = false) {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !$config['ssh']) {
			$this->outputLine('<error>No SSH config found</error>');
			$this->quit(1);
		}

		if ($config['ssh']['serverPath'] === '~/kundenbereich/') {
			// serverPath still has default value
			$this->outputLine('<error>No ssh server path set in extension configuration</error>');
			$this->quit(1);
		}

		$folders = $this->getRsyncFolders();
		if (!$folders) {
			$this->outputLine('<error>No folders configured for rsync</error>');
			$this->quit(1);
		}
		$folders = $this->transformFolders($folders, $config['ssh']);

		$command = array_filter(array_merge([
			'rsync',
			($dryRun ? '--dry-run' : ''),
			'--exclude', '_processed_',
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
		$importProcess->inheritEnvironmentVariables();
		$importProcess->run($this->buildStreamOutput());

		if ($importProcess->getExitCode()) {
			$this->outputLine('<error>Import failed</error>');
		} else if (!$dryRun) {
			$this->outputLine('<info>Import complete</info>');
		}
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

		return $folders;
	}

	protected function transformFolders(array $folders, array $sshConfig): array {
		$publicPrefix = trim(str_replace(Environment::getProjectPath(), '', Environment::getPublicPath()), '/') . '/';
		if ($publicPrefix !== '/') {
			$publicPrefix = '/' . $publicPrefix;
		}
		$serverPath = trim($sshConfig['serverPath'], '/') .'/';

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
}
