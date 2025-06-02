<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendLayoutDataProvider implements DataProviderInterface {

	/** @var array<array> */
	protected array $backendLayouts = [];

	public function __construct() {
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'])) {
			return;
		}

		$tokenizer = GeneralUtility::makeInstance(LossyTokenizer::class);
		$astBuilder = GeneralUtility::makeInstance(AstBuilder::class);

		$paths = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_base']['paths'];
		foreach ($paths as $path) {
			foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
				if (!$file instanceof \SplFileInfo || is_dir($file->getPathname())) {
					continue;
				}
				$content = (string)file_get_contents($file->getPathname());
				$lineStream = $tokenizer->tokenize($content);
				$rootNode = GeneralUtility::makeInstance(RootNode::class);
				$typoScriptConfig = $astBuilder->build($lineStream, $rootNode)->toArray();

				$key = $file->getBasename('.' . $file->getExtension());
				$typoScriptConfig['identifier'] = $key;
				$this->backendLayouts[$key] = $typoScriptConfig;
			}
		}
	}

	public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection): void {
		foreach ($this->backendLayouts as $data) {
			$backendLayout = $this->createBackendLayout($data);
			if ($backendLayout) {
				$backendLayoutCollection->add($backendLayout);
			}
		}
	}

	/**
	 * {@inheritdoc}
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
	protected function createBackendLayout(array $data): ?BackendLayout {
		if (!isset($data['identifier'], $data['title'], $data['config'])) {
			return null;
		}
		assert(is_string($data['identifier']));
		assert(is_string($data['title']));
		assert(is_string($data['config']) || is_array($data['config']));
		$backendLayout = BackendLayout::create($data['identifier'], $data['title'], $data['config']);
		if (!empty($data['icon']) && is_string($data['icon'])) {
			$backendLayout->setIconPath($data['icon']);
		}
		$backendLayout->setData($data);
		return $backendLayout;
	}

}
