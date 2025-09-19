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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

#[AsCommand(
	name: 'vierwd:publish',
	description: 'Upload JavaScript and CSS to server with no need to commit it to git.',
)]
class PublishCommand extends Command {

	use ServerTrait;

	protected function configure(): void {
		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a trial run with no changes made');
		$servers = $this->getConfiguredServers();
		$this->addArgument('server', InputArgument::REQUIRED, 'To which server do you want to publish? ' . implode(', ', array_keys($servers)));
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');

		try {
			$basePath = $this->getConfiguredServerPath($input);
			$basePath = rtrim($basePath, '/');
		} catch (\Throwable $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		$extensionName = $this->getExtensionName();
		$localPath = ExtensionManagementUtility::extPath($extensionName, 'Resources/');
		$serverPath = $basePath . '/packages/' . $extensionName . '/Resources/';

		$prettyServerPath = preg_replace('/^([^:]*@[^:]*:)(.*)$/', '$1<options=bold>$2</>', $basePath) . '/packages/' . $extensionName . '/Resources/';
		$output->writeln(sprintf('<info>Publishing to %s</info>', $prettyServerPath));

		$command = array_filter([
			'rsync',
			($dryRun ? '--dry-run' : ''),
			'--include', '*.css',
			'--include', '*.js',
			'--include', '*.json',
			'--include', '*.gzip',
			'--include', '*.brotli',
			'--include', 'Private',
			'--include', 'Private/Css',
			'--include', 'Private/Css/vars.json',
			'--include', 'Private/SSR/',
			'--include', 'Public',
			'--include', 'Public/static',
			'--include', 'Public/static/build',
			'--include', 'Public/static/lib',
			'--include', 'Public/static/script',
			'--include', 'Public/static/script/lib',
			'--include', 'Public/static/script/polyfills',
			'--exclude', '*',
			'--times',
			'--compress',
			'--recursive',
			'--itemize-changes',
			'--delete',
			$localPath,
			$serverPath,
		]);

		$importProcess = new Process($command);
		$importProcess->setTimeout(0.0);
		$importProcess->run($this->buildStreamOutput($output));

		if ($importProcess->getExitCode()) {
			$output->writeln('<error>Publish failed</error>');
		} else if (!$dryRun) {
			$output->writeln('<info>Publish complete</info>');
		}

		return 0;
	}

	protected function getExtensionName(): string {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['publishExtensionName'])) {
			return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['publishExtensionName'];
		}

		$composerFile = Environment::getProjectPath() . '/composer.json';
		if (!file_exists($composerFile)) {
			throw new \Exception('composer.json not found', 1602247094);
		}

		$composerContents = file_get_contents($composerFile);
		if ($composerContents === false) {
			throw new \Exception('Could not read composer.json', 1602247133);
		}

		$composerConfig = json_decode($composerContents, true);
		if (!is_array($composerConfig)) {
			throw new \Exception('Could not parse composer.json', 1602247173);
		}

		$extensionName = ArrayUtility::getValueByPath($composerConfig, 'extra/vierwd/extensionName');

		if (!$extensionName || !is_string($extensionName)) {
			throw new \Exception('Could not find extensionName', 1602247310);
		}

		return $extensionName;
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
