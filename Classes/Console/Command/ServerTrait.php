<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ServerTrait {

	protected function getConfiguredServers(): array {
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('vierwd_base');
		if (!$config || !is_array($config) || !$config['ssh']) {
			throw new \RuntimeException('No SSH config found. Please complete the extension configuration for vierwd_base', 1637242166);
		}

		if ($config['ssh']['serverPath'] === '~/kundenbereich/') {
			// serverPath still has default value
			throw new \RuntimeException('No ssh server path set. Please complete the extension configuration for vierwd_base', 1637242227);
		}

		$servers = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['servers'] ?? []);

		$serverPath = $config['ssh']['serverPath'];
		if (str_starts_with($serverPath, '~/')) {
			// Strip home directory prefix.
			// rsync < 3.2 does not expand the home directory and it works fine without the prefix,
			// because SSH starts within the home directory.
			$serverPath = substr($serverPath, 2);
		}

		$liveServer = [
			'live' => $config['ssh']['liveUser'] . '@' . $config['ssh']['liveHost'] . ':' . $serverPath,
		];

		return $liveServer + $servers;
	}

	protected function getConfiguredServerPath(InputInterface $input): string {
		$servers = $this->getConfiguredServers();

		$server = $input->getArgument('server');
		if (!isset($servers[$server])) {
			throw new \Exception('Invalid server: ' . $server, 1622039016);
		}

		$serverPath = $servers[$server];

		return $serverPath;
	}

}
