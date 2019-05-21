<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

		$command = array_filter([
			'rsync',
			($dryRun ? '--dry-run' : ''),
			'--exclude', '_processed_',
			'--times',
			'--links',
			'--compress',
			'--recursive',
			'--itemize-changes',
			'--delete',
			$config['ssh']['liveUser'] . '@' . $config['ssh']['liveHost'] . ':' . $config['ssh']['serverPath'] . 'public/fileadmin/',
			Environment::getPublicPath() . '/fileadmin/',
		]);

		$importProcess = new Process($command);
		$importProcess->setTimeout(0.0);
		$importProcess->inheritEnvironmentVariables();
		$importProcess->run($this->buildStreamOutput());

		if (!$dryRun) {
			$this->outputLine('<info>Import complete</info>');
		}
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
