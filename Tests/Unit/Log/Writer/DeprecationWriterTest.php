<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Log\Writer;

use Exception;
use ReflectionProperty;

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Log\Writer\DeprecationWriter;

class DeprecationWriterTest extends UnitTestCase {

	use ProphecyTrait;

	private LogRecord $logRecord;

	public function setUp(): void {
		parent::setUp();
		$this->logRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.deprecationWriter.simpleRecord.'), LogLevel::INFO, 'test record');
	}

	public function getMessages(DeprecationWriter $writer): array {
		$property = new ReflectionProperty($writer, 'messages');
		$property->setAccessible(true);
		$messages = $property->getValue($writer);
		assert(is_array($messages));
		return $messages;
	}

	public function testNoLogWrittenWithoutApplicationType(): void {
		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->onlyMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('registerShutdownFunction')->will(self::throwException(new Exception('Method should not be called')));
		$subject->method('getVierwdClass')->will(self::throwException(new Exception('Method should not be called')));
		self::assertEquals($subject, $subject->writeLog($this->logRecord));
		self::assertEquals([], $this->getMessages($subject));
	}

	public function testNoLogWrittenInBackendContext(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->onlyMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('registerShutdownFunction')->will(self::throwException(new Exception('Method should not be called')));
		$subject->method('getVierwdClass')->will(self::throwException(new Exception('Method should not be called')));
		self::assertEquals($subject, $subject->writeLog($this->logRecord));
		self::assertEquals([], $this->getMessages($subject));
	}

	public function testLogNotWrittenWithoutVierwdClasses(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->onlyMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn(null);

		self::assertEquals($subject, $subject->writeLog($this->logRecord));
		self::assertEquals([], $this->getMessages($subject));
	}

	public function testLogWrittenWithVierwdClasses(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->onlyMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn([
			'class' => 'DummyClass',
			'function' => 'dummyFunction',
		]);

		self::assertEquals($subject, $subject->writeLog($this->logRecord));
		self::assertEquals(1, count($this->getMessages($subject)));
	}

	public function testLogWrittenWithVierwdFile(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->onlyMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn([
			'file' => 'dummy-file.php',
			'line' => 1,
		]);

		self::assertEquals($subject, $subject->writeLog($this->logRecord));
		self::assertEquals(1, count($this->getMessages($subject)));
	}

	public function testGetCheckTraceWithoutVierwdClasses(): void {
		/** @var DeprecationWriter&AccessibleObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([[
			'class' => GeneralUtility::class,
			'line' => 1,
		]]);
		self::assertEquals(false, $subject->_call('checkTrace'));
	}

	public function testGetCheckTraceWithVierwdClasses(): void {
		/** @var DeprecationWriter&AccessibleObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([[
			'class' => self::class,
			'line' => 1,
		]]);
		self::assertEquals(true, $subject->_call('checkTrace'));
	}

	public function testGetVierwdClass(): void {
		$className = 'Vierwd\\VierwdBase\\TestClass';
		$traceEntry = [
			'class' => $className,
			'line' => 1,
		];

		/** @var DeprecationWriter&AccessibleObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([$traceEntry]);
		self::assertEquals($traceEntry, $subject->_call('getVierwdClass'));
	}

	public function testGetVierwdClassWithoutVierwdClass(): void {
		$className = GeneralUtility::class;
		$traceEntry = [
			'class' => $className,
			'line' => 1,
		];

		/** @var DeprecationWriter&AccessibleObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([$traceEntry]);
		self::assertEquals(null, $subject->_call('getVierwdClass'));
	}
}
