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

		$liveServer = [
			'live' => $config['ssh']['liveUser'] . '@' . $config['ssh']['liveHost'] . ':' . $config['ssh']['serverPath'],
		];

		return $liveServer + $servers;
	}

	protected function getConfiguredServerPath(InputInterface $input): string {
		$servers = $this->getConfiguredServers();

		$server = $input->getArgument('server');
		if (!isset($servers[$server])) {
			throw new \Exception('Invalid server: ' . $server, 1622039016);
		}

		return $servers[$server];
	}

}
