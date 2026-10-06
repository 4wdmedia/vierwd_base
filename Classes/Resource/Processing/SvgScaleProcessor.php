<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource\Processing;

use DOMDocument;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Safe\file_put_contents;
use function Safe\filesize;

/**
 * Helper class to locally perform a resize of an SVG
 */
class SvgScaleProcessor {

	#[AsEventListener()]
	public function __invoke(BeforeFileProcessingEvent $event): void {
		$processedFile = $event->getProcessedFile();
		$file = $event->getFile();
		$context = $event->getTaskType();
		$configuration = $event->getConfiguration();

		$this->processTask($processedFile, $file, $context, $configuration);

		$event->setProcessedFile($processedFile);
	}

	/**
	 * slot for FileProcessingService::preFileProcess.
	 * TYPO3 only processes it's hard-coded taskTypes. We hook into the preProcessFile signal and process our custom type,
	 * so that it will be skipped by the TYPO3 processing
	 */
	public function processTask(ProcessedFile $processedFile, FileInterface $file, string $context, array $configuration): void {
		if ($file->getExtension() !== 'svg') {
			return;
		}

		if ($context !== SvgScaleTask::CONTEXT_SVGSCALE) {
			return;
		}

		if ($processedFile->isProcessed()) {
			return;
		}

		$taskTypeRegistry = GeneralUtility::makeInstance(TaskTypeRegistry::class);
		$task = $taskTypeRegistry->getTaskForType($processedFile->getTaskIdentifier(), $processedFile, $processedFile->getProcessingConfiguration());

		if (!$this->checkForExistingTargetFile($task)) {
			$targetFile = $task->getTargetFile();
			$sourceFile = $task->getSourceFile();

			if ($sourceFile->getProperty('width') == $configuration['width'] && $sourceFile->getProperty('height') == $configuration['height']) {
				$processedFile->setUsesOriginalFile();
			} else {
				$tempFile = Environment::getVarPath() . '/transient/svg/';
				GeneralUtility::mkdir_deep($tempFile);
				$tempFile .= substr(md5($sourceFile->getIdentifier() . $sourceFile->getModificationTime() . serialize($configuration)), 0, 10) . '.svg';
				if (!file_exists($tempFile)) {
					$fileContents = $sourceFile->getContents();
					if (!$fileContents) {
						$processedFile->setUsesOriginalFile();
						$task->setExecuted(true);
						trigger_error('SVG file is empty: ' . $sourceFile->getIdentifier(), E_USER_WARNING);
						return;
					}

					// create it
					$document = new DOMDocument();

					if (!@$document->loadXML($fileContents) || !$document->documentElement) {
						$processedFile->setUsesOriginalFile();
						$task->setExecuted(true);
						trigger_error('Could not load SVG: ' . $sourceFile->getIdentifier(), E_USER_WARNING);
						return;
					}

					$document->documentElement->setAttribute('width', (string)$configuration['width']);
					$document->documentElement->setAttribute('height', (string)$configuration['height']);
					file_put_contents($tempFile, $document->saveXML($document->documentElement));
				}

				if (file_exists($tempFile)) {
					$targetFile->setName($task->getTargetFileName());

					$configuration = $task->getConfiguration();
					$properties = [
						'width' => $configuration['width'],
						'height' => $configuration['height'],
						'size' => filesize($tempFile),
						'checksum' => $task->getConfigurationChecksum(),
					];
					$task->getTargetFile()->updateProperties($properties);

					$processedFile->updateWithLocalFile($tempFile);
					$task->setExecuted(true);
				} else {
					$task->setExecuted(false);
				}
			}
		}

		if ($task->isExecuted() && $task->isSuccessful()) {
			$processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
			$processedFileRepository->add($processedFile, $task);
		}
	}

	private function checkForExistingTargetFile(TaskInterface $task): bool {
		// the storage of the processed file, not of the original file!
		$storage = $task->getTargetFile()->getStorage();
		$processingFolder = $storage->getProcessingFolder($task->getSourceFile());

		// explicitly check for the raw filename here, as we check for files that existed before we even started
		// processing, i.e. that were processed earlier
		if ($processingFolder->hasFile($task->getTargetFileName())) {
			// When the processed file already exists set it as processed file
			$task->getTargetFile()->setName($task->getTargetFileName());

			$task->setExecuted(true);

			// If the processed file is stored on a remote server, we must fetch a local copy of the file, as we
			// have no API for fetching file metadata from a remote file.
			$localProcessedFile = $storage->getFileForLocalProcessing($task->getTargetFile(), false);
			$configuration = $task->getConfiguration();
			$properties = [
				'width' => $configuration['width'],
				'height' => $configuration['height'],
				'size' => filesize($localProcessedFile),
				'checksum' => $task->getConfigurationChecksum(),
			];
			$task->getTargetFile()->updateProperties($properties);

			return true;
		} else {
			return false;
		}
	}

}
