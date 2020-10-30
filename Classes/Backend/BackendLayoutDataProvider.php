<?php

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendLayoutDataProvider implements DataProviderInterface {

	/**
	 * @var array<array>
	 */
	protected $backendLayouts = [];

	public function __construct() {
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'])) {
			return;
		}

		$paths = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'];
		foreach ($paths as $path) {
			$parser = GeneralUtility::makeInstance(TypoScriptParser::class);

			foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
				if (is_dir($file)) {
					continue;
				}
				$content = file_get_contents($file->getPathname());
				$parser->parse($content);
				$key = $file->getBasename('.' . $file->getExtension());
				$this->backendLayouts[$key] = $parser->setup;
			}
		}
	}

	/**
	 * @param DataProviderContext $dataProviderContext
	 * @param BackendLayoutCollection $backendLayoutCollection
	 * @return void
	 */
	public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection) {
		foreach ($this->backendLayouts as $key => $data) {
			$data['uid'] = $key;
			$backendLayout = $this->createBackendLayout($data);
			$backendLayoutCollection->add($backendLayout);
		}
	}

	/**
	 * Gets a backend layout by (regular) identifier.
	 *
	 * @param string $identifier
	 * @param int $pageId
	 * @return NULL|BackendLayout
	 */
	public function getBackendLayout($identifier, $pageId) {
		$backendLayout = null;
		if (array_key_exists($identifier, $this->backendLayouts)) {
			return $this->createBackendLayout($this->backendLayouts[$identifier]);
		}
		return $backendLayout;
	}

	/**
	 * Creates a new backend layout using the given record data.
	 *
	 * @param array<string, mixed> $data
	 * @return BackendLayout
	 */
	protected function createBackendLayout(array $data): BackendLayout {
		$backendLayout = BackendLayout::create($data['uid'], $data['title'], $data['config']);
		$backendLayout->setIconPath($this->getIconPath($data['icon']));
		$backendLayout->setData($data);
		return $backendLayout;
	}

	/**
	 * Gets and sanitizes the icon path.
	 *
	 * @param string $icon Name of the icon file
	 */
	protected function getIconPath(string $icon): string {
		$iconPath = '';
		if (!empty($icon)) {
			$iconPath = $icon;
		}
		return $iconPath;
	}

}
