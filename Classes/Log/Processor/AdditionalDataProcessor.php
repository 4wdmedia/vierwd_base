<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Log\Processor;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;

class AdditionalDataProcessor extends AbstractProcessor {

	protected array $additionalData = [];

	/**
	 * Set the number of levels to be shift from the backtrace
	 *
	 * @param array $additionalData additional data for log entry
	 */
	public function setAdditionalData(array $additionalData): AbstractProcessor {
		$this->additionalData = $additionalData;
		return $this;
	}

	public function addAdditionalData(array $additionalData): AbstractProcessor {
		$this->additionalData = $additionalData + $this->additionalData;
		return $this;
	}

	public function removeAdditionalData(array $additionalDataKeys): AbstractProcessor {
		$this->additionalData = array_diff_key($this->additionalData, array_flip($additionalDataKeys));
		return $this;
	}

	public function getCurrentAdditionalData(): array {
		return $this->additionalData;
	}

	public function processLogRecord(LogRecord $logRecord): LogRecord {
		// Only keep data which is not set for log record.
		// This allows overwriting the additional data within a log message
		$additionalData = array_diff_key($this->additionalData, $logRecord->getData());
		$logRecord->addData($additionalData);

		return $logRecord;
	}

}
