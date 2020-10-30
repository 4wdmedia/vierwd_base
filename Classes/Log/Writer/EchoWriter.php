<?php

namespace Vierwd\VierwdBase\Log\Writer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Echo writer - just echo everything, if used on console
 */
class EchoWriter extends AbstractWriter {

	/** @var OutputInterface */
	private $output = null;

	public function __construct(array $options = []) {
		parent::__construct($options);

		if (!defined('TYPO3_REQUESTTYPE') || !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
			// only output messages in CLI context
			return;
		}

		while (ob_get_level()) {
			ob_end_flush();
		}

		$this->output = GeneralUtility::makeInstance(StreamOutput::class, fopen('php://stdout', 'a', false));
	}

	/**
	 * Writes the log record
	 *
	 * @param LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 */
	public function writeLog(LogRecord $record) {
		if ($this->output === null) {
			return $this;
		}

		$levelName = strtoupper($record->getLevel());
		$timestamp = date('c', (int)$record->getCreated());

		$color = '<fg=blue;bg=default>';

		$normalizedLevel = LogLevel::normalizeLevel($record->getLevel());

		if ($normalizedLevel < LogLevel::normalizeLevel(LogLevel::NOTICE)) {
			$color = '<fg=yellow>';
		}
		if ($normalizedLevel < LogLevel::normalizeLevel(LogLevel::WARNING)) {
			$color = '<fg=red>';
		}

		$message = sprintf(
			'%s ' . $color . '[%s]</> %s',
			$timestamp,
			$levelName,
			$record->getMessage()
		);

		$this->output->writeln($message);

		return $this;
	}
}
