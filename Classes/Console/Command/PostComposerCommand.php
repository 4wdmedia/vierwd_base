<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command;

use Doctrine\DBAL\Exception\ConnectionException;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
	name: 'vierwd:post-composer',
	description: 'Tasks to run after composer install/update',
)]
class PostComposerCommand extends Command {

	protected function configure(): void {
		$this->setHelp('Ensure some folders exist after composer installation');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// create typo3temp, if it does not exist
		if (!file_exists(Environment::getPublicPath() . '/typo3temp')) {
			GeneralUtility::mkdir(Environment::getPublicPath() . '/typo3temp');
			$output->writeln('<info>Created typo3temp.</info>');
		}

		if (!empty($_SERVER['VIERWD_CONFIG'])) {
			// create symlink to static resources, if the link does not exist and this is the dev-environment
			$staticResources = Environment::getPublicPath() . '/static-resources';
			if (!is_link($staticResources)) {
				$composerFile = Environment::getProjectPath() . '/composer.json';
				$composerInfo = json_decode((string)file_get_contents($composerFile), true);
				if (is_array($composerInfo) && isset($composerInfo['extra']['vierwd/typo3-base']['composerName'])) {
					$target = '_assets/' . md5('/vendor/' . $composerInfo['extra']['vierwd/typo3-base']['composerName'] . '/') . '/static';
					$target = escapeshellarg($target);
					$staticResources = escapeshellarg($staticResources);
					shell_exec("ln -s $target $staticResources");
					$output->writeln('<info>Added Link for static-resources.</info>');
				}
			}

			// create AdditionalConfiguration.php if it does not exist
			$additionalConfiguration = Environment::getConfigPath() . '/AdditionalConfiguration.php';
			$sampleAdditionalConfiguration = Environment::getConfigPath() . '/AdditionalConfiguration.sample.php';
			if (!file_exists($additionalConfiguration) && file_exists($sampleAdditionalConfiguration)) {
				file_put_contents($additionalConfiguration, file_get_contents($sampleAdditionalConfiguration));
				$output->writeln('<info>Added AdditionalConfiguration.php.</info>');
			}
		}

		$typo3Command = Environment::getProjectPath() . '/vendor/bin/typo3';
		$commandDispatcher = CommandDispatcher::create($typo3Command);

		// check if database is fully available
		if ($this->hasValidDatabaseConnection()) {
			$commandDispatcher->executeCommand('cache:flush');
		} else {
			$commandDispatcher->executeCommand('cache:flush', ['--group', 'system']);
			$commandDispatcher->executeCommand('cache:flush', ['--group', 'di']);
		}

		return Command::SUCCESS;
	}

	protected function hasValidDatabaseConnection(): bool {
		if (!file_exists(Environment::getConfigPath() . '/PackageStates.php') || !file_exists(Environment::getConfigPath() . '/LocalConfiguration.php')) {
			return false;
		}

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
		try {
			return count($connection->createSchemaManager()->listTableNames()) > 0;
		} catch (ConnectionException $e) {
			return false;
		}
	}

}
