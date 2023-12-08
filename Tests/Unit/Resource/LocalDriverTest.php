<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Backend;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Resource\LocalDriver;

class LocalDriverTest extends UnitTestCase {

	protected bool $resetSingletonInstances = true;

	/**
	 * @dataProvider getSanitizeFileNameTestData
	 */
	public function testSanitizeFileName(string $expected, string $fileName): void {
		$this->markTestIncomplete('LocalDriver::sanitizeFileName initializes CharsetConverter, which accesses ExtensionManagementUtility');

		// @phpstan-ignore-next-line markTestIncomplete finishes code execution
		$subject = new LocalDriver();
		self::assertEquals($expected, $subject->sanitizeFileName($fileName));
	}

	public function getSanitizeFileNameTestData(): array {
		return [
			['Test.txt', 'Test.txt'],
			['test.txt', 'test.txt'],
			['Test.txt', 'Test.TxT'],
			['Test.txt', 'Test.TXT'],
			['test.txt', 'test.TXT'],
		];
	}

}
