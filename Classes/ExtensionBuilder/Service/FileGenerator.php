<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

	public function renderTemplate($filePath, $variables) {
		$codeTemplateAdditionalRootPath = GeneralUtility::getFileAbsFileName($this->settings['codeTemplateAdditionalRootPath']);

		$variables['settings'] = $this->settings;
		/* @var \TYPO3\CMS\Fluid\View\StandaloneView $standAloneView */
		$standAloneView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$standAloneView->setLayoutRootPaths(array(
			$codeTemplateAdditionalRootPath,
			$this->codeTemplateRootPath,
		));
		$standAloneView->setPartialRootPaths(array(
			$codeTemplateAdditionalRootPath . 'Partials',
			$this->codeTemplateRootPath . 'Partials',
		));
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
		$needsLicenseHeader = TRUE;
		foreach ($comments as $comment) {
			// we do not use GPL
			if (strpos($comment, 'Copyright notice') !== FALSE) {
				$needsLicenseHeader = FALSE;
			}
		}

		if ($needsLicenseHeader) {
			$licenseHeader = $this->renderTemplate(
				'Partials/Classes/licenseHeader.phpt',
				array('persons' => $this->extension->getPersons())
			);
			$classObject->addComment($licenseHeader);
		}
	}
}