<?php
declare(strict_types = 1);

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

	protected array $buffer = [];

	protected bool $sendMail = true;

	protected int $minErrorLevel;

	protected string $sender;

	protected string $receiver;

	protected string $subject = 'TYPO3 Errors on site %s';

	public function __construct(array $options) {
		if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) {
			$this->sender = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
		} else {
			$this->sender = 'typo3.reports@4wdmedia.de';
		}
		parent::__construct($options);
	}

	/**
	 * @param int|string $minErrorLevel
	 */
	public function setMinErrorLevel($minErrorLevel): void {
		$this->sendMail = false;
		$this->minErrorLevel = LogLevel::normalizeLevel($minErrorLevel);
	}

	public function setSender(string $sender): void {
		$this->sender = $sender;
	}

	public function setReceiver(string $receiver): void {
		$this->receiver = $receiver;
	}

	public function setSubject(string $subject): void {
		$this->subject = $subject;
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected function registerShutdownFunction(): void {
		register_shutdown_function([$this, 'sendMail']);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 */
	public function writeLog(LogRecord $record) {
		if (!$this->sendMail && LogLevel::normalizeLevel($record->getLevel()) <= $this->minErrorLevel) {
			$this->sendMail = true;
			$this->registerShutdownFunction();
		}

		$levelName = strtoupper($record->getLevel());
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

		$message->from($this->sender);
		$message->to($this->receiver);
		$message->subject($subject);
		$message->text(implode("\n", $this->buffer));

		$message->send();
	}
}
