<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendLayoutDataProvider implements DataProviderInterface {

	/** @var array<array> */
	protected array $backendLayouts = [];

	public function __construct() {
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'])) {
			return;
		}

		$paths = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'];
		foreach ($paths as $path) {
			$parser = GeneralUtility::makeInstance(TypoScriptParser::class);

			foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
				if (!$file instanceof \SplFileInfo || is_dir($file->getPathname())) {
					continue;
				}
				$content = (string)file_get_contents($file->getPathname());
				$parser->parse($content);
				if (!$parser->errors) {
					$key = $file->getBasename('.' . $file->getExtension());
					$parser->setup['identifier'] = $key;
					$this->backendLayouts[$key] = $parser->setup;
				}
			}
		}
	}

	public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection): void {
		foreach ($this->backendLayouts as $key => $data) {
			$backendLayout = $this->createBackendLayout($data);
			$backendLayoutCollection->add($backendLayout);
		}
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
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
	 */
	protected function createBackendLayout(array $data): BackendLayout {
		$backendLayout = BackendLayout::create($data['identifier'], $data['title'], $data['config']);
		if (!empty($data['icon'])) {
			$backendLayout->setIconPath($data['icon']);
		}
		$backendLayout->setData($data);
		return $backendLayout;
	}

}
