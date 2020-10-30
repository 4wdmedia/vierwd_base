<?php

namespace Vierwd\VierwdBase\Log\Writer;

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mail writer, capture all LogRecords starting with an ErrorLevel, but only send an
 * email, if at least one Record is larger than `$minErrorLevel`.
 * Usage in ext_localconf.php:
 * ```
 * $GLOBALS['TYPO3_CONF_VARS']['LOG']['Vierwd']['VierwdExample']['Service']['ProjectImport']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::INFO] = [
 *     \Vierwd\VierwdBase\Log\Writer\MailWriter::class => [
 *         'receiver' => 'typo3.reports@4wdmedia.de',
 *         'subject' => 'Projektimport',
 *         'minErrorLevel' => \TYPO3\CMS\Core\Log\LogLevel::ERROR,
 *     ],
 * ];
 * ```
 */
class MailWriter extends AbstractWriter {

	protected $buffer = [];

	protected $sendMail = true;

	protected $minErrorLevel;

	protected $sender;

	protected $receiver;

	protected $subject = 'TYPO3 Errors on site %s';

	public function __construct(array $options) {
		if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) {
			$this->sender = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
		} else {
			$this->sender = 'typo3.reports@4wdmedia.de';
		}
		parent::__construct($options);
	}

	public function setMinErrorLevel(int $minErrorLevel) {
		$this->sendMail = false;
		$this->minErrorLevel = $minErrorLevel;
	}

	public function setSender($sender) {
		$this->sender = $sender;
	}

	public function setReceiver($receiver) {
		$this->receiver = $receiver;
	}

	public function setSubject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 * Writes the log record
	 *
	 * @param LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 */
	public function writeLog(LogRecord $record) {
		if (!$this->sendMail && $record->getLevel() <= $this->minErrorLevel) {
			$this->sendMail = true;
			register_shutdown_function([$this, 'sendMail']);
		}

		$levelName = LogLevel::getName($record->getLevel());
		$timestamp = date('c', (int)$record->getCreated());
		$data = $record->getData() ? json_encode($record->getData(), JSON_PRETTY_PRINT) : '';
		$this->buffer[] = sprintf('%s [%s] %s %s', $timestamp, $levelName, $record->getMessage(), $data);

		return $this;
	}

	public function sendMail(): void {
		if (!$this->buffer) {
			return;
		}

		$message = GeneralUtility::makeInstance(MailMessage::class);

		$subject = sprintf($this->subject, $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);

		$message->setFrom($this->sender);
		$message->setTo($this->receiver);
		$message->setSubject($subject);
		$message->setBody(implode("\n", $this->buffer));

		$message->send();
	}
}
