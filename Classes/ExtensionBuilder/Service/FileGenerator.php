<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

	public function renderTemplate($filePath, $variables) {
		$codeTemplateAdditionalRootPath = GeneralUtility::getFileAbsFileName($this->settings['codeTemplateAdditionalRootPath']);

		$variables['settings'] = $this->settings;
		/* @var \TYPO3\CMS\Fluid\View\StandaloneView $standAloneView */
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
		foreach ($comments as $comment) {
			// we do not use GPL
			if (strpos($comment, 'Copyright notice') !== false) {
				$needsLicenseHeader = false;
			}
		}

		if ($needsLicenseHeader) {
			$licenseHeader = $this->renderTemplate(
				'Partials/Classes/licenseHeader.phpt',
				['persons' => $this->extension->getPersons()]
			);
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
		} catch (\Exception $e) {
			throw new \Exception('Could not generate TCA files, error: ' . $e->getMessage() . $e->getFile());
		}
	}
}
