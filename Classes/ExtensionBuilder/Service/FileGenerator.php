<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

class FileGenerator extends \EBT\ExtensionBuilder\Service\FileGenerator {

	public function renderTemplate($filePath, $variables) {
		$variables['settings'] = $this->settings;

		$codeTemplateAdditionalRootPath = $this->settings['codeTemplateAdditionalRootPath'];
		if ($codeTemplateAdditionalRootPath) {
			$standAloneView = $this->objectManager->get('\\Vierwd\\VierwdBase\\View\\MultiSourceStandaloneView');
			$standAloneView->setTemplatePath(array(
				$codeTemplateAdditionalRootPath,
				$this->codeTemplateRootPath
			));
			$standAloneView->setLayoutRootPath($this->codeTemplateRootPath);
			$standAloneView->setPartialRootPath($this->codeTemplateRootPath . '/Partials');

			$standAloneView->setAdditionalLayoutRootPath($codeTemplateAdditionalRootPath);
			$standAloneView->setAdditionalPartialRootPath($codeTemplateAdditionalRootPath . '/Partials');

			$standAloneView->setFormat('txt');

			$standAloneView->setFilename($filePath);
		} else {
			$standAloneView = $this->objectManager->get('\\TYPO3\\CMS\\Fluid\\View\\StandaloneView');
			$standAloneView->setLayoutRootPath($this->codeTemplateRootPath);
			$standAloneView->setPartialRootPath($this->codeTemplateRootPath . '/Partials');
			$standAloneView->setFormat('txt');
			$templatePathAndFilename = $this->codeTemplateRootPath .  $filePath;
			$standAloneView->setTemplatePathAndFilename($templatePathAndFilename);
		}

		$standAloneView->assignMultiple($variables);
		$renderedContent = $standAloneView->render();
			// remove all double empty lines (coming from fluid)
		return preg_replace('/^\\s*\\n[\\t ]*$/m', '', $renderedContent);
	}

	protected function addLicenseHeader($classObject) {
		$comments = $classObject->getComments();
		$needsLicenseHeader = TRUE;
		foreach($comments as $comment) {
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