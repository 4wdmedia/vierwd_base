<?php

namespace Vierwd\VierwdBase\Composer;

use Composer\EventDispatcher\ScriptExecutionException;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\PhpExecutableFinder;

class Typo3Installation {

	/**
	 * @see \Composer\EventDispatcher\EventDispatcher::getPhpExecCommand
	 */
	protected static function getPhpExecCommand() {
		$finder = new PhpExecutableFinder();
		$phpPath = $finder->find();
		if (!$phpPath) {
			throw new \RuntimeException('Failed to locate PHP binary');
		}

		$memoryFlag = ' -d memory_limit=' . ini_get('memory_limit');

		return ProcessExecutor::escape($phpPath) . $memoryFlag;
	}

	/**
	 * switch vendor directory with .vendor-prev directory.
	 * composer install/update should be as atomic as possible
	 */
	public static function switchVendor(Event $event) {
		$consoleIO = $event->getIO();

		if (!is_dir('.vendor-prev')) {
			$consoleIO->error('.vendor-prev was not found');
			return;
		}

		if (!is_dir('vendor')) {
			$consoleIO->error('vendor was not found. Run composer install');
			return;
		}

		rename('vendor', '.vendor-switch');
		rename('.vendor-prev', 'vendor');
		rename('.vendor-switch', '.vendor-prev');

		$consoleIO->notice('Switched vendor folders');
	}

	/**
	 * perform a composer install into another vendor directory and change the paths
	 * after the update
	 */
	public static function safeInstall(Event $event) {
		$consoleIO = $event->getIO();

		$process = new ProcessExecutor($consoleIO);

		$composerCommand = self::getPhpExecCommand() . ' ' . ProcessExecutor::escape(getenv('COMPOSER_BINARY'));

		// $COMPOSER_BINARY config vendor-dir .vendor-prev
		$setVendorDir = $composerCommand . ' config vendor-dir .vendor-prev';
		if (0 !== ($exitCode = $process->execute($setVendorDir))) {
			throw new ScriptExecutionException('Error Output: ' . $process->getErrorOutput(), $exitCode);
		}

		$consoleIO->notice('Changed vendor directory config option');

		// $COMPOSER_BINARY install --no-dev || exit 1
		$setConfig = $composerCommand . ' install --no-dev';
		if (0 !== ($exitCode = $process->execute($setConfig))) {
			throw new ScriptExecutionException('Error Output: ' . $process->getErrorOutput(), $exitCode);
		}

		// Switch vendor folders
		$triggeredEvent = new Event('switch-vendor', $event->getComposer(), $event->getIO(), $event->isDevMode());
		$event->getComposer()->getEventDispatcher()->dispatch($triggeredEvent->getName(), $triggeredEvent);

		// $COMPOSER_BINARY config vendor-dir vendor
		$resetVendorDir = $composerCommand . ' config vendor-dir vendor';
		if (0 !== ($exitCode = $process->execute($resetVendorDir))) {
			throw new ScriptExecutionException('Error Output: ' . $process->getErrorOutput(), $exitCode);
		}

		$consoleIO->notice('Reset vendor directory config option');
	}

	/**
	 * symlink all extensions which are loaded via composer
	 */
	public static function linkExtensions(Event $event) {
		$consoleIO = $event->getIO();

		$composer = $event->getComposer();

		$requires = $composer->getPackage()->getRequires();

		$packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
		$packages = array_filter($packages, function(PackageInterface $package) {
			return $package->getType() === 'typo3-cms-extension';
		});

		$installationManager = $composer->getInstallationManager();
		$cwd = getcwd();

		foreach ($packages as $package) {
			$extensionKey = '';
			foreach ($package->getReplaces() as $packageName => $version) {
				if (strpos($packageName, '/') === false) {
					$extensionKey = trim($packageName);
					break;
				}
			}

			if (!$extensionKey) {
				list(, $extensionKey) = explode('/', $package->getName(), 2);
				$extensionKey = str_replace('-', '_', $extensionKey);
			}

			if (!$extensionKey) {
				$consoleIO->error('Could not determine extensionKey for ' . $package->getName());
				continue;
			}

			$path = $installationManager->getInstallPath($package);
			if (!$path) {
				$consoleIO->error('Could not get installation path for ' . $extensionKey);
				continue;
			}

			if (substr($path, 0, strlen($cwd)) !== $cwd) {
				$consoleIO->error('Installation path is not within current working directory: ' . $path);
				continue;
			}

			$path = substr($path, strlen($cwd));
			`ln -sf ../..$path typo3conf/ext/$extensionKey`;
		}
	}
}
