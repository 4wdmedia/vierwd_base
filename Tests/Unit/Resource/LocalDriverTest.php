<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Backend;

use Nimut\TestingFramework\TestCase\UnitTestCase;

use Vierwd\VierwdBase\Resource\LocalDriver;

class LocalDriverTest extends UnitTestCase {

	/**
	 * @dataProvider getSanitizeFileNameTestData
	 */
	public function testSanitizeFileName(string $expected, string $fileName): void {
		$subject = new LocalDriver();
		$this->assertEquals($expected, $subject->sanitizeFileName($fileName));
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
