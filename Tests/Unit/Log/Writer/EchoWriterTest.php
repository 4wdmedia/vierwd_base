<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Log\Writer;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\StreamOutput;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Vierwd\VierwdBase\Log\Writer\EchoWriter;

class EchoWriterTest extends UnitTestCase {

	private function setUpEnvironment(bool $cli): void {
		$environment = new Environment();

		$environment::initialize(
			Environment::getContext(),
			$cli,
			Environment::isComposerMode(),
			Environment::getProjectPath(),
			Environment::getPublicPath(),
			Environment::getVarPath(),
			Environment::getConfigPath(),
			Environment::getCurrentScript(),
			'UNIX'
		);
	}

	public function testWriteLogWithoutCLIMode(): void {
		$this->setUpEnvironment(false);

		$subject = $this->getAccessibleMock(EchoWriter::class, ['dummy']);
		$simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.echoWriter.simpleRecord.'), LogLevel::INFO, 'test record');
		$subject->writeLog($simpleRecord);
		$this->assertEquals(null, $subject->output, 'No output is created when environment is not CLI');
	}

	public function testWriteLogInfoWithCLIMode(): void {
		$this->setUpEnvironment(true);

		$outputMock = $this->prophesize(StreamOutput::class);
		$outputMock->writeln(Argument::containingString('test record'))->shouldBeCalled();

		GeneralUtility::addInstance(StreamOutput::class, $outputMock->reveal());

		$subject = $this->getAccessibleMock(EchoWriter::class, ['dummy']);
		$simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.echoWriter.simpleRecord.'), LogLevel::INFO, 'test record');
		$subject->writeLog($simpleRecord);
	}

	public function testWriteLogNoticeWithCLIMode(): void {
		$this->setUpEnvironment(true);

		$outputMock = $this->prophesize(StreamOutput::class);
		$outputMock->writeln(Argument::containingString('test record'))->shouldBeCalled();

		GeneralUtility::addInstance(StreamOutput::class, $outputMock->reveal());

		$subject = $this->getAccessibleMock(EchoWriter::class, ['dummy']);
		$simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.echoWriter.simpleRecord.'), LogLevel::NOTICE, 'test record');
		$subject->writeLog($simpleRecord);
	}

	public function testWriteLogErrorWithCLIMode(): void {
		$this->setUpEnvironment(true);

		$outputMock = $this->prophesize(StreamOutput::class);
		$outputMock->writeln(Argument::containingString('test record'))->shouldBeCalled();

		GeneralUtility::addInstance(StreamOutput::class, $outputMock->reveal());

		$subject = $this->getAccessibleMock(EchoWriter::class, ['dummy']);
		$simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.echoWriter.simpleRecord.'), LogLevel::ERROR, 'test record');
		$subject->writeLog($simpleRecord);
	}
}
