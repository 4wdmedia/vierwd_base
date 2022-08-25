<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent;

class IndexFilesAfterImport {

	private Indexer $indexer;

	public function __construct(ResourceFactory $factory) {
		$storage = $factory->getDefaultStorage();
		assert($storage !== null);
		$this->indexer = GeneralUtility::makeInstance(Indexer::class, $storage);
	}

	public function __invoke(AfterExtensionFilesHaveBeenImportedEvent $event): void {
		$this->indexer->processChangesInStorages();
	}

}
