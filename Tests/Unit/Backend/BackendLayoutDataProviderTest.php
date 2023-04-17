<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Backend;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Backend\BackendLayoutDataProvider;

class BackendLayoutDataProviderTest extends UnitTestCase {

	protected bool $resetSingletonInstances = true;

	/** @phpstan-ignore-next-line Root is never read, but that's ok */
	private vfsStreamDirectory $root;

	private TimeTracker $mockTimeTracker;

	public function setUp(): void {
		parent::setUp();

		// TimeTracker is used by TypoScriptParser, when an error occurs during parsing
		$this->mockTimeTracker = $this->getMockBuilder(TimeTracker::class)
			->disableOriginalConstructor()
			->getMock();
		GeneralUtility::setSingletonInstance(TimeTracker::class, $this->mockTimeTracker);

		$standardLayout = <<<EOT
			title = Standard
			icon = EXT:vierwd_base/Resources/Public/Icons/Extension.svg
			config (
				backend_layout {
					colCount = 3
					rowCount = 1
					rows {
						1 {
							columns {
								1 {
									name = Inhalt
									colPos = 0
									colspan = 2
								}
								2 {
									name = Marginalie
									colPos = 1
								}
							}
						}
					}
				}
			)
EOT;
		$this->root = vfsStream::setup('root', null, [
			'noBackendLayouts' => [],
			'invalidFiles' => [
				'README.md' => 'This file cannot be parsed and should not create a backend layout',
				'fileWithparseError.ts' => 'This file cannot be parsed and should not create a backend layout',
			],
			'singleBackendLayout' => [
				'standard.ts' => $standardLayout,
			],
			'multipleBackendLayouts' => [
				'standard.ts' => $standardLayout,
				'standard2.ts' => $standardLayout,
			],
			'backendLayoutAndSubdirectory' => [
				'standard.ts' => $standardLayout,
				'subfolder.ts' => [
					'ignored.ts' => 'Subfolders are not scanned',
				],
			],
		]);
	}

	public function tearDown(): void {
		GeneralUtility::purgeInstances();

		parent::tearDown();

		unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths']);
	}

	public function testAddBackendLayouts(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/singleBackendLayout')];

		$dataProviderContextMock = $this->getMockBuilder(DataProviderContext::class)
			->disableOriginalConstructor()
			->getMock();

		$backendLayoutCollectionMock = $this->getMockBuilder(BackendLayoutCollection::class)
			->onlyMethods(['add'])
			->disableOriginalConstructor()
			->getMock();
		$backendLayoutCollectionMock->expects(self::once())->method('add');

		$backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
			->disableOriginalConstructor()
			->getMock();

		$testSubject = $this->getMockBuilder(BackendLayoutDataProvider::class)
			->onlyMethods(['createBackendLayout'])
			->getMock();
		$testSubject->expects(self::once())->method('createBackendLayout')->willReturn($backendLayoutMock);

		$testSubject->addBackendLayouts($dataProviderContextMock, $backendLayoutCollectionMock);
	}

	public function testAddMultipleBackendLayouts(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/multipleBackendLayouts')];

		$dataProviderContextMock = $this->getMockBuilder(DataProviderContext::class)
			->disableOriginalConstructor()
			->getMock();

		$backendLayoutCollectionMock = $this->getMockBuilder(BackendLayoutCollection::class)
			->onlyMethods(['add'])
			->disableOriginalConstructor()
			->getMock();
		$backendLayoutCollectionMock->expects(self::exactly(2))->method('add');

		$backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
			->disableOriginalConstructor()
			->getMock();

		$testSubject = $this->getMockBuilder(BackendLayoutDataProvider::class)
			->onlyMethods(['createBackendLayout'])
			->getMock();
		$testSubject->expects(self::exactly(2))->method('createBackendLayout')->willReturn($backendLayoutMock);

		$testSubject->addBackendLayouts($dataProviderContextMock, $backendLayoutCollectionMock);
	}


	public function testGetBackendLayout(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/multipleBackendLayouts')];

		$backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
			->disableOriginalConstructor()
			->getMock();

		$testSubject = $this->getMockBuilder(BackendLayoutDataProvider::class)
			->onlyMethods(['createBackendLayout'])
			->getMock();
		$testSubject->expects(self::once())->method('createBackendLayout')->willReturn($backendLayoutMock);

		self::assertEquals($backendLayoutMock, $testSubject->getBackendLayout('standard', 1));
	}

	public function testGetInvalidBackendLayout(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/multipleBackendLayouts')];

		$testSubject = new BackendLayoutDataProvider();
		self::assertEquals(null, $testSubject->getBackendLayout('backendLayoutDoesNotExist', 1));
	}

	public function testIgnoreSubfolders(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/backendLayoutAndSubdirectory')];

		$testSubject = new BackendLayoutDataProvider();
		self::assertEquals(null, $testSubject->getBackendLayout('ignored', 1));
	}

	public function testConstructorWithoutPaths(): void {
		unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']);

		$testSubject = new BackendLayoutDataProvider();
		self::assertEquals(null, $testSubject->getBackendLayout('backendLayoutDoesNotExist', 1));
	}

	public function testBackendLayoutWithParseError(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/invalidFiles')];

		$testSubject = new BackendLayoutDataProvider();
		self::assertEquals(null, $testSubject->getBackendLayout('fileWithparseError', 1));
	}

	public function testCreateBackendLayout(): void {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'] = [vfsStream::url('root/singleBackendLayout')];

		$backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
			->disableOriginalConstructor()
			->getMock();
		GeneralUtility::addInstance(BackendLayout::class, $backendLayoutMock);

		$testSubject = new BackendLayoutDataProvider();
		self::assertEquals($backendLayoutMock, $testSubject->getBackendLayout('standard', 1));
	}

}
