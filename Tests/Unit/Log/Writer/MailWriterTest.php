<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Log\Writer;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Vierwd\VierwdBase\Log\Writer\MailWriter;

class MailWriterTest extends UnitTestCase {

	/** @var LogRecord */
	private $logRecord;

	public function setUp(): void {
		$this->logRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.mailWriter.simpleRecord.'), LogLevel::INFO, 'test record');
	}

	public function testWritingLogSendsMail(): void {
		$mailMessage = $this->prophesize(MailMessage::class);
		$mailMessage->from('sender@4wdmedia.de')->shouldBeCalled();
		$mailMessage->to('receiver@4wdmedia.de')->shouldBeCalled();
		$mailMessage->subject('subject')->shouldBeCalled();
		$mailMessage->text(Argument::any())->shouldBeCalled();
		$mailMessage->send()->shouldBeCalled();

		GeneralUtility::addInstance(MailMessage::class, $mailMessage->reveal());

		$subject = $this->getMockBuilder(MailWriter::class)
			->setConstructorArgs([[
				'sender' => 'sender@4wdmedia.de',
				'receiver' => 'receiver@4wdmedia.de',
				'subject' => 'subject',
				'minErrorLevel' => LogLevel::INFO,
			]])
			->setMethods(['registerShutdownFunction'])
			->getMock();
		$subject->writeLog($this->logRecord);
		$subject->sendMail();
	}
}
