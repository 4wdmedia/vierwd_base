<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexFilesAfterImport {

	private Indexer $indexer;

	public function __construct(StorageRepository $storageRepository) {
		$storage = $storageRepository->getDefaultStorage();
		assert($storage !== null);
		$this->indexer = GeneralUtility::makeInstance(Indexer::class, $storage);
	}

	public function __invoke(PackageInitializationEvent $event): void {
		$this->indexer->processChangesInStorages();
	}

}
