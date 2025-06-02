<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Backend;

use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Resource\LocalDriver;

class LocalDriverTest extends UnitTestCase {

	protected bool $resetSingletonInstances = true;

	#[DataProvider('getSanitizeFileNameTestData')]
	public function testSanitizeFileName(string $expected, string $fileName): void {
		$charsetConverter = $this->getMockBuilder(CharsetConverter::class)->getMock();
		$charsetConverter->method('specCharsToASCII')->willReturnCallback(function(string $charset, string $string): string {
			return $string;
		});
		GeneralUtility::setSingletonInstance(CharsetConverter::class, $charsetConverter);

		$subject = new LocalDriver();
		self::assertEquals($expected, $subject->sanitizeFileName($fileName));
	}

	public static function getSanitizeFileNameTestData(): array {
		return [
			['Test.txt', 'Test.txt'],
			['test.txt', 'test.txt'],
			['Test.txt', 'Test.TxT'],
			['Test.txt', 'Test.TXT'],
			['test.txt', 'test.TXT'],
		];
	}

}
