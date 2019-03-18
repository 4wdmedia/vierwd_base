<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

	public function renderTemplate($filePath, $variables) {
		if (isset($this->codeTemplateRootPaths)) {
			// extension_builder starting with f4cc45ae has support for multiple template root paths
			return parent::renderTemplate($filePath, $variables);
		}

		// add support for multiple template root paths

		$codeTemplateAdditionalRootPath = GeneralUtility::getFileAbsFileName($this->settings['codeTemplateAdditionalRootPath']);

		$variables['settings'] = $this->settings;
		/** @var \TYPO3\CMS\Fluid\View\StandaloneView $standAloneView */
		$standAloneView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$standAloneView->setLayoutRootPaths([
			$codeTemplateAdditionalRootPath,
			$this->codeTemplateRootPath,
		]);
		$standAloneView->setPartialRootPaths([
			$codeTemplateAdditionalRootPath . 'Partials',
			$this->codeTemplateRootPath . 'Partials',
		]);
		$standAloneView->setFormat('txt');
		if (file_exists($codeTemplateAdditionalRootPath . $filePath)) {
			$templatePathAndFilename = $codeTemplateAdditionalRootPath .  $filePath;
		} else {
			$templatePathAndFilename = $this->codeTemplateRootPath .  $filePath;
		}
		$standAloneView->setTemplatePathAndFilename($templatePathAndFilename);
		$standAloneView->assignMultiple($variables);
		$renderedContent = $standAloneView->render();

		// remove all double empty lines (coming from fluid)
		return preg_replace('/^\\s*\\n[\\t ]*$/m', '', $renderedContent);
	}

	protected function addLicenseHeader($classObject) {
		$comments = $classObject->getComments();
		$needsLicenseHeader = true;
		foreach ($comments as $key => $comment) {
			// we do not use GPL
			if (strpos($comment, 'Copyright notice') !== false) {
				$needsLicenseHeader = false;
				if (substr($comment, -2) !== "\n") {
					$comment = trim($comment) . "\n";
					$comments[$key] = $comment;
					$classObject->setComments($comments);
				}
				break;
			}
		}

		if ($needsLicenseHeader) {
			$licenseHeader = $this->renderTemplate(
				'Partials/Classes/licenseHeader.phpt',
				['persons' => $this->extension->getPersons()]
			) . "\n";

			$classObject->addComment($licenseHeader);
		}
	}

	protected function generateTCAFiles() {
		// Generate TCA
		try {
			parent::generateTCAFiles();

			$domainObjectsWithoutOverrides = [];
			foreach ($this->extension->getDomainObjectsInHierarchicalOrder() as $domainObject) {
				if (!$domainObject->isMappedToExistingTable() && !$domainObject->getHasChildren()) {
					if (!isset($domainObjectsWithoutOverrides[$domainObject->getDatabaseTableName()])) {
						$domainObjectsWithoutOverrides[$domainObject->getDatabaseTableName()] = [];
					}
					$domainObjectsWithoutOverrides[$domainObject->getDatabaseTableName()][] = $domainObject;
				}
			}
			$tablesNeedingTypeFields = $this->extension->getTablesForTypeFieldDefinitions();
			foreach ($domainObjectsWithoutOverrides as $tableName => $domainObjects) {
				$addRecordTypeField = in_array($tableName, $tablesNeedingTypeFields);
				$fileContents = $this->generateTCAOverride($domainObjects, $addRecordTypeField);
				$this->writeFile(
					$this->configurationDirectory . 'TCA/Overrides/' . $tableName . '.php',
					$fileContents
				);
			}
		} catch (\Throwable $e) {
			throw new \Exception('Could not generate TCA files, error: ' . $e->getMessage() . $e->getFile());
		}
	}

	protected function writeFile($targetFile, $fileContents) {
		// replace spaces with tabs
		$fileContents = preg_replace_callback('/^(    )+/m', function($matches)  {
			return str_replace('    ', "\t", $matches[0]);
		}, $fileContents);

		if ($fileContents[strlen($fileContents) - 1] != "\n") {
			// add newline at EOF
			$fileContents .= "\n";
		}

		return parent::writeFile($targetFile, $fileContents);
	}

	protected function insertSplitToken($targetFile, $fileContents) {
		$fileContents = parent::insertSplitToken($targetFile, $fileContents);

		if ($fileContents && $fileContents[strlen($fileContents) - 1] != "\n") {
			// add newline at EOF
			$fileContents .= "\n";
		}

		return $fileContents;
	}
}
