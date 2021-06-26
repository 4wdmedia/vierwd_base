<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\XClass\Core;

use Nimut\TestingFramework\TestCase\UnitTestCase;

use Vierwd\VierwdBase\XClass\Core\Resource\StorageRepository;

class StorageRepositoryTest extends UnitTestCase {

	public function testCaseSensitivity(): void {
		$mock = $this->getAccessibleMock(StorageRepository::class, ['dummy'], [], '', false);

		$this->assertEquals(true, $mock->_call('testCaseSensitivity', '/any/path'));
	}
}
