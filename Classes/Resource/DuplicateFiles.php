<?php

namespace Vierwd\VierwdBase\Resource;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check for duplicate Files after upload
 */
class DuplicateFiles {

	public function checkForDuplicateFiles(FileInterface $file, Folder $targetFolder) {
		if (!$file->getSize()) {
			// do not check for existence of empty files
			return;
		}

		$fileHash = $file->getSha1();
		$files = FileIndexRepository::getInstance()->findByContentHash($fileHash);
		$files = array_filter($files, function($indexedFile) use ($file) {
			return $indexedFile['storage'] == $file->getStorage()->getUid() && $indexedFile['uid'] != $file->getUid();
		});

		if ($files) {
			// There is another file in the same storage which is exactly the same. Inform the user about it
			$message = GeneralUtility::makeInstance(FlashMessage::class,
			    'Die Datei existiert bereits an einer anderen Stelle: ' . $files[0]['identifier'],
			    'Doppelte Datei',
			    FlashMessage::WARNING,
			    true // whether message should be stored in session
			);
			$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
			$flashMessageService->getMessageQueueByIdentifier()->enqueue($message);
		}
	}
}