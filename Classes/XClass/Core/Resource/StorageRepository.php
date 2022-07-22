<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Core\Resource;

/**
 * Override check for case-sensitivity.
 * We develop on OS X, which has a non-case-sensitive file system. But when we upload
 * the project to a Linux-server, the file-system is case-sensitive, so it must be
 * enabled on our machines as well.
 */
class StorageRepository extends \TYPO3\CMS\Core\Resource\StorageRepository {

	protected function testCaseSensitivity($absolutePath) {
		return true;
	}

}
