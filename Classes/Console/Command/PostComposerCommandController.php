<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Doctrine\DBAL\Exception\ConnectionException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PostComposerCommandController extends CommandController {

	/**
	 * @var CommandDispatcher
	 */
	private $commandDispatcher;

	public function __construct(CommandDispatcher $commandDispatcher = null) {
		$this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
	}

	/**
	 * ensure some folders exist after composer installation
	 */
	public function runCommand() {
		// create typo3temp, if it does not exist
		if (!file_exists(Environment::getPublicPath() . '/typo3temp')) {
			GeneralUtility::mkdir(Environment::getPublicPath() . '/typo3temp');
			$this->outputLine('<info>Created typo3temp.</info>');
		}

		if (!empty($_SERVER['VIERWD_CONFIG'])) {
			// create symlink to static resources, if the link does not exist and this is the dev-environment
			$staticResources = Environment::getPublicPath() . '/static-resources';
			if (!is_link($staticResources)) {
				$composerFile = Environment::getProjectPath() . '/composer.json';
				$composerInfo = json_decode(file_get_contents($composerFile), true);
				if ($composerInfo && isset($composerInfo['extra'], $composerInfo['extra']['extensionName'])) {
					$target = 'typo3conf/ext/' . $composerInfo['extra']['extensionName'] . '/Resources/Public/static';
					$target = escapeshellarg($target);
					$staticResources = escapeshellarg($staticResources);
					`ln -s $target $staticResources`;
					$this->outputLine('<info>Added Link for static-resources.</info>');
				}
			}

			// create AdditionalConfiguration.php if it does not exist
			$additionalConfiguration = Environment::getLegacyConfigPath() . '/AdditionalConfiguration.php';
			$sampleAdditionalConfiguration = Environment::getLegacyConfigPath() . '/AdditionalConfiguration.sample.php';
			if (!file_exists($additionalConfiguration) && file_exists($sampleAdditionalConfiguration)) {
				file_put_contents($additionalConfiguration, file_get_contents($sampleAdditionalConfiguration));
				$this->outputLine('<info>Added AdditionalConfiguration.php.</info>');
			}
		}

		// check if database is fully available
		if ($this->hasValidDatabaseConnection()) {
			$this->commandDispatcher->executeCommand('cache:flush');
		}
		$this->commandDispatcher->executeCommand('cache:flush', ['--force', '--files-only']);

		$this->quit(0);
	}

	protected function hasValidDatabaseConnection(): bool {
		if (!file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php') || !file_exists(Environment::getLegacyConfigPath() . '/LocalConfiguration.php')) {
			return false;
		}

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
		try {
			$connection->connect();
			return count($connection->getSchemaManager()->listTableNames()) > 0;
		} catch (ConnectionException $e) {
			return false;
		}
	}
}
