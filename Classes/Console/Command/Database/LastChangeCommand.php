<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LastChangeCommand extends Command {

	protected function configure() {
		$this->setDescription('Show the last changed timestamps for all tables.');
		$this->setHelp('This only works for tables which have the default TYPO3 tstamp or crdate fields');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');
		$schemaManager = $connection->getSchemaManager();
		$tables = $schemaManager->listTables();
		foreach ($tables as $table) {
			$hasTstamp = $table->hasColumn('tstamp');
			$hasCrdate = $table->hasColumn('crdate');
			$select = '';
			if ($hasTstamp && $hasCrdate) {
				$select = 'IF(MAX(crdate) > MAX(tstamp), MAX(crdate), MAX(tstamp))';
			} else if ($hasTstamp) {
				$select = 'MAX(tstamp)';
			} else if ($hasCrdate) {
				$select = 'MAX(crdate)';
			} else {
				continue;
			}
			$result = $connection->query('SELECT ' . $select . ' FROM `' . $table->getName() . '`');

			$time = $result->fetchColumn();
			if (!$time) {
				// probably no rows in the table
				continue;
			}
			$output->writeln(date('Y-m-d H:i', $time) . ' ' . $table->getName());
		}

		return 0;
	}
}
