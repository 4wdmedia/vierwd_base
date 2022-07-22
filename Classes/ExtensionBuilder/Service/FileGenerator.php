<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

	protected function generateTCAFiles(): void {
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

	/**
	 * @phpstan-return void
	 */
	protected function writeFile($targetFile, $fileContents): void {
		// replace spaces with tabs
		$fileContents = (string)preg_replace_callback('/^(    )+/m', function($matches)  {
			return str_replace('    ', "\t", $matches[0]);
		}, $fileContents);

		if ($fileContents[strlen($fileContents) - 1] != "\n") {
			// add newline at EOF
			$fileContents .= "\n";
		}

		parent::writeFile($targetFile, $fileContents);
	}

	protected function insertSplitToken(string $targetFile, string $fileContents): string {
		$fileContents = parent::insertSplitToken($targetFile, $fileContents);

		if ($fileContents && $fileContents[strlen($fileContents) - 1] != "\n") {
			// add newline at EOF
			$fileContents .= "\n";
		}

		return $fileContents;
	}

}
