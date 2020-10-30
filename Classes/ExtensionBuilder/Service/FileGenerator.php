<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

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

		$extensionSettings = $this->extension->getSettings();
		if ($needsLicenseHeader && empty($extensionSettings['skipDocComment'])) {
			$licenseHeader = $this->renderTemplate(
				'Partials/Classes/licenseHeader.phpt',
				['extension' => $this->extension]
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

		parent::writeFile($targetFile, $fileContents);
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
