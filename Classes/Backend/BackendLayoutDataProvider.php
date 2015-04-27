<?php
namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendLayoutDataProvider implements \TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface {

	protected $backendLayouts = array();

	public function __construct() {
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'])) {
			return;
		}

		$paths = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'];
		foreach ($paths as $path) {
			$parser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');

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
	 * @param integer $pageId
	 * @return NULL|BackendLayout
	 */
	public function getBackendLayout($identifier, $pageId){
		$backendLayout = NULL;
		if (array_key_exists($identifier, $this->backendLayouts)) {
			return $this->createBackendLayout($this->backendLayouts[$identifier]);
		}
		return $backendLayout;
	}

	/**
	 * Creates a new backend layout using the given record data.
	 *
	 * @param array $data
	 * @return BackendLayout
	 */
	protected function createBackendLayout(array $data) {
		$backendLayout = BackendLayout::create($data['uid'], $data['title'], $data['config']);
		$backendLayout->setIconPath($this->getIconPath($data['icon']));
		$backendLayout->setData($data);
		return $backendLayout;
	}

	/**
	 * Gets and sanitizes the icon path.
	 *
	 * @param string $icon Name of the icon file
	 * @return string
	 */
	protected function getIconPath($icon) {
		$iconPath = '';
		if (!empty($icon)) {
			$iconPath = $icon;
		}
		return $iconPath;
	}

}