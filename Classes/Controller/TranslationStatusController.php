<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Controller;

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use Vierwd\VierwdSmarty\Controller\SmartyController;

class TranslationStatusController extends SmartyController {

	/**
	 * get a list of language files. Returns an array with extensionName and fileName.
	 * searches only for vierwd-extensions
	 */
	protected function getLanguageFiles(): array {
		$extensions = ExtensionManagementUtility::getLoadedExtensionListArray();
		$extensions = array_filter($extensions, function(string $extensionName) {
			return StringUtility::beginsWith($extensionName, 'vierwd_');
		});
		sort($extensions);

		$files = [];
		foreach ($extensions as $extensionName) {
			$path = ExtensionManagementUtility::extPath($extensionName, 'Resources/Private/Language');
			if (!is_dir($path)) {
				continue;
			}

			$languageFiles = glob($path . '/*.xlf') ?: [];

			// remove language files which are translations
			$languageFiles = array_filter($languageFiles, function(string $pathName) {
				$fileName = basename($pathName);
				return substr_count($fileName, '.') === 1;
			});

			if (!$languageFiles) {
				continue;
			}

			// only use filename
			$languageFiles = array_map('basename', $languageFiles);

			$files[$extensionName] = [];
			foreach ($languageFiles as $fileName) {
				$files[$extensionName][$fileName] = $fileName;
			}
		}

		return $files;
	}

	protected function getAvailableLanguages(string $pathName): array {
		$directory = dirname($pathName);
		$fileName = basename($pathName);
		$translations = glob($directory . '/*.' . $fileName) ?: [];

		// get language prefix
		$translations = array_map(function(string $pathName) {
			$translationName = basename($pathName);
			$language = explode('.', $translationName, 2)[0];
			return $language;
		}, $translations);

		return $translations;
	}

	protected function loadLanguageComparison(string $extensionName = '', string $fileName = '', bool $showAllLabels = false): void {
		$localizationFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

		$fileReference = ExtensionManagementUtility::extPath($extensionName, 'Resources/Private/Language/' . $fileName);

		$translations = [];

		/** @var array $data */
		$data = $localizationFactory->getParsedData($fileReference, 'default');
		// get the source label
		$data = array_map(function(array $row) {
			return $row[0]['source'];
		}, $data['default']);
		$translations['default'] = $data;

		$availableLanguages = $this->getAvailableLanguages($fileReference);

		foreach ($availableLanguages as $languageKey) {
			/** @var array $data */
			$data = $localizationFactory->getParsedData($fileReference, $languageKey);
			$data = $data[$languageKey];

			$data = array_map(function($row) {
				return $row[0]['target'];
			}, $data);

			if ($data) {
				$translations[$languageKey] = $data;
			}
		}

		if (count($translations) < 2 && !$showAllLabels) {
			return;
		}

		$allKeys = array_unique(array_merge(...array_values(array_map(function(array $data) {
			return array_keys($data);
		}, $translations))));

		if ($showAllLabels) {
			$diffKeys = $allKeys;
		} else {
			$keys = array_keys(array_intersect_key(...array_values($translations)));
			// $keys are available in all arrays

			$diffKeys = array_diff($allKeys, $keys);
		}
		sort($diffKeys);

		$this->view->assign('translationKeys', $diffKeys);
		$this->view->assign('translations', $translations);
	}

	public function indexAction(string $extensionName = '', string $fileName = '', bool $showAllLabels = false): void {
		$languageFiles = $this->getLanguageFiles();
		$this->view->assign('languageFiles', $languageFiles);
		$this->view->assign('currentShowAllLabels', $showAllLabels);

		if (!isset($languageFiles[$extensionName], $languageFiles[$extensionName][$fileName])) {
			$extensionName = '';
			$fileName = '';
		}

		$this->view->assign('currentExtensionName', $extensionName);
		$this->view->assign('currentFileName', $fileName);

		if ($extensionName && $fileName) {
			$this->loadLanguageComparison($extensionName, $fileName, $showAllLabels);
		}
	}
}
