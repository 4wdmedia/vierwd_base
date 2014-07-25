<?php

namespace Vierwd\VierwdBase\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class MultiSourceStandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView {

	protected $templatePath = array();

	protected $additionalLayoutRootPath = array();

	protected $additionalPartialRootPath = array();

	public function setTemplatePath($templatePath) {
		$this->templatePath = $this->checkPaths((array)$templatePath);
	}

	public function setAdditionalLayoutRootPath($additionalLayoutRootPath) {
		$this->additionalLayoutRootPath = $this->checkPaths((array)$additionalLayoutRootPath);
	}

	public function setAdditionalPartialRootPath($additionalPartialRootPath) {
		$this->additionalPartialRootPath = $this->checkPaths((array)$additionalPartialRootPath);
	}

	public function checkPaths(array $paths) {
		$paths = array_map('\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility::getFileAbsFileName', $paths);
		return array_filter($paths, 'is_dir');
	}

	public function setFilename($filename) {
		foreach ($this->templatePath as $path) {
			$pathAndFilename = $path . $filename;
			if (file_exists($pathAndFilename)) {
				$this->setTemplatePathAndFilename($pathAndFilename);
				return true;
			}
		}

		return false;
	}

	protected function getPartialSource($partialName) {
		foreach ($this->additionalPartialRootPath as $path) {
			$pathAndFilename = $path . $partialName;
			if (file_exists($pathAndFilename)) {
				$partialSource = file_get_contents($pathAndFilename);
				if ($partialSource) {
					return $partialSource;
				}
			}
		}

		return parent::getPartialSource($partialName);
	}

	protected function getLayoutSource($layoutName = 'Default') {
		foreach ($this->additionalPartialRootPath as $path) {
			$pathAndFilename = $path . $layoutName;
			if (file_exists($pathAndFilename)) {
				$layoutSource = file_get_contents($pathAndFilename);
				if ($layoutSource) {
					return $layoutSource;
				}
			}
		}

		return parent::getLayoutSource($layoutName);
	}
}