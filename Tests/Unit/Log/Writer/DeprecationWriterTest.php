<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Log\Writer;

use Exception;
use ReflectionProperty;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use Vierwd\VierwdBase\Log\Writer\DeprecationWriter;

class DeprecationWriterTest extends UnitTestCase {

	/** @var LogRecord */
	private $logRecord;

	public function setUp(): void {
		$this->logRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.vierwd_base.log.deprecationWriter.simpleRecord.'), LogLevel::INFO, 'test record');
	}

	public function getMessages(DeprecationWriter $writer): array {
		$property = new ReflectionProperty($writer, 'messages');
		$property->setAccessible(true);
		return $property->getValue($writer);
	}

	public function testNoLogWrittenWithoutApplicationType(): void {
		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->setMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('registerShutdownFunction')->will($this->throwException(new Exception('Method should not be called')));
		$subject->method('getVierwdClass')->will($this->throwException(new Exception('Method should not be called')));
		$this->assertEquals($subject, $subject->writeLog($this->logRecord));
		$this->assertEquals([], $this->getMessages($subject));
	}

	public function testNoLogWrittenInBackendContext(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->setMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('registerShutdownFunction')->will($this->throwException(new Exception('Method should not be called')));
		$subject->method('getVierwdClass')->will($this->throwException(new Exception('Method should not be called')));
		$this->assertEquals($subject, $subject->writeLog($this->logRecord));
		$this->assertEquals([], $this->getMessages($subject));
	}

	public function testLogNotWrittenWithoutVierwdClasses(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->setMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn(null);

		$this->assertEquals($subject, $subject->writeLog($this->logRecord));
		$this->assertEquals([], $this->getMessages($subject));
	}

	public function testLogWrittenWithVierwdClasses(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->setMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn([
			'class' => 'DummyClass',
			'function' => 'dummyFunction',
		]);

		$this->assertEquals($subject, $subject->writeLog($this->logRecord));
		$this->assertEquals(1, count($this->getMessages($subject)));
	}

	public function testLogWrittenWithVierwdFile(): void {
		$requestMock = $this->prophesize(ServerRequestInterface::class);
		$requestMock->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
		$GLOBALS['TYPO3_REQUEST'] = $requestMock->reveal();

		$subject = $this->getMockBuilder(DeprecationWriter::class)
			->setMethods(['registerShutdownFunction', 'getVierwdClass'])
			->getMock();
		$subject->method('getVierwdClass')->willReturn([
			'file' => 'dummy-file.php',
			'line' => 1,
		]);

		$this->assertEquals($subject, $subject->writeLog($this->logRecord));
		$this->assertEquals(1, count($this->getMessages($subject)));
	}

	public function testGetCheckTraceWithoutVierwdClasses(): void {
		/** @var DeprecationWriter&AccessibleMockObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([[
			'class' => GeneralUtility::class,
			'line' => 1,
		]]);
		$this->assertEquals(false, $subject->_call('checkTrace'));
	}

	public function testGetCheckTraceWithVierwdClasses(): void {
		/** @var DeprecationWriter&AccessibleMockObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([[
			'class' => self::class,
			'line' => 1,
		]]);
		$this->assertEquals(true, $subject->_call('checkTrace'));
	}

	public function testGetVierwdClass(): void {
		$className = 'Vierwd\\VierwdBase\\TestClass';
		$traceEntry = [
			'class' => $className,
			'line' => 1,
		];

		/** @var DeprecationWriter&AccessibleMockObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([$traceEntry]);
		$this->assertEquals($traceEntry, $subject->_call('getVierwdClass'));
	}

	public function testGetVierwdClassWithoutVierwdClass(): void {
		$className = GeneralUtility::class;
		$traceEntry = [
			'class' => $className,
			'line' => 1,
		];

		/** @var DeprecationWriter&AccessibleMockObjectInterface&MockObject $subject */
		$subject = $this->getAccessibleMock(DeprecationWriter::class, ['getTrace'], [], '', false);
		$subject->method('getTrace')->willReturn([$traceEntry]);
		$this->assertEquals(null, $subject->_call('getVierwdClass'));
	}
}
