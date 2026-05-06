<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use function Safe\filemtime;

/**
 * Add FORWARD MEDIA richtext settings.
 * - Transform RTE PageTS configuration to stylesSet for CKEditor
 * - Add CSS for editor toolbar
 * - Add cache-buster for contentsCss
 */
class RichtextConfiguration implements FormDataProviderInterface {

	/**
	 * @param array $result Given result array
	 * @return array Modified result array
	 */
	public function addData(array $result): array {
		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'text') {
				continue;
			}

			if (!isset($fieldConfig['config']['enableRichtext']) || (bool)$fieldConfig['config']['enableRichtext'] !== true) {
				continue;
			}

			// add custom CSS for ckeditor
			$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
			$pageRenderer->addCssFile('EXT:vierwd_base/Resources/Public/Css/ckeditor.css');

			$rteConfiguration = $fieldConfig['config']['richtextConfiguration'];

			// Generate based on classes configuration
			$stylesSet = $this->generateStylesSet($rteConfiguration);
			if ($stylesSet) {
				$existingStylesSet = $result['processedTca']['columns'][$fieldName]['config']['richtextConfiguration']['editor']['config']['stylesSet'] ?: [];
				$result['processedTca']['columns'][$fieldName]['config']['richtextConfiguration']['editor']['config']['stylesSet'] = array_merge($stylesSet, $existingStylesSet);
			}

			// replace contentsCss with cache-busted URL
			if (is_array($rteConfiguration['editor']['config']['contentsCss'] ?? null)) {
				$contentsCss = array_map(function ($cssFile) {
					if (is_string($cssFile) && str_starts_with($cssFile, 'EXT:')) {
						$cssFile = GeneralUtility::getFileAbsFileName($cssFile);
						$webPath = PathUtility::getAbsoluteWebPath($cssFile);
						return $webPath . '?' . filemtime($cssFile);
					}
					return $cssFile;
				}, $rteConfiguration['editor']['config']['contentsCss']);
				$result['processedTca']['columns'][$fieldName]['config']['richtextConfiguration']['editor']['config']['contentsCss'] = $contentsCss;
			}
		}

		return $result;
	}

	private function generateStylesSet(array $rteConfiguration): array {
		if (!isset($rteConfiguration['classesAnchor.']) || !is_array($rteConfiguration['classesAnchor.'])) {
			return [];
		}

		// Generate styleset based on classesAnchor
		$stylesSet = [];
		foreach ($rteConfiguration['classesAnchor.'] as $classConfiguration) {
			$class = $classConfiguration['class'];
			$name = $rteConfiguration['classes.'][$class . '.']['name'] ?: $class;
			$stylesSet[$class] = [
				'name' => $name,
				'element' => 'a',
				'attributes' => [
					'class' => $class,
				],
			];
		}

		return array_values($stylesSet);
	}

}
