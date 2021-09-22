<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\XClass\Core;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\XClass\Core\Resource\StorageRepository;

class StorageRepositoryTest extends UnitTestCase {

	public function testCaseSensitivity(): void {
		$mock = $this->getAccessibleMock(StorageRepository::class, ['dummy'], [], '', false);

		self::assertEquals(true, $mock->_call('testCaseSensitivity', '/any/path'));
	}
}
