<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Form\Element;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Element\ImageManipulationElement;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Vierwd\VierwdSmarty\View\StandaloneSmartyView;

class HotspotEditorElement extends AbstractNode {

	public function render(): array {
		$html = $this->getHtml();

		$result = $this->initializeResultArray();

		if ($html) {
			$result['html'] = $html;
			$result['stylesheetFiles'][] = 'EXT:vierwd_base/Resources/Public/Css/hotspot-editor.css';
			$result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@vierwd/vierwd_base/HotspotEditor.js');
		}

		return $result;
	}

	protected function getHtml(): string {
		$imageFile = $this->getImage();

		if (!$imageFile->getProperty('width')) {
			return '';
		}

		$variantCollection = $this->getCropVariantCollection();
		$cropVariants = $variantCollection->asArray();
		if (!$cropVariants) {
			return '';
		}

		$croppedImages = [];
		$scaledImages = [];

		foreach ($cropVariants as $variantName => $variantConfig) {
			$crop = $variantCollection->getCropArea($variantName)->makeAbsoluteBasedOnFile($imageFile);
			$imageSetup = ['crop' => $crop];
			$croppedImage = $imageFile->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
			$croppedImages[$variantName] = $croppedImage;

			$imageSetup = ['maxWidth' => 1200, 'maxHeight' => 1200, 'crop' => $crop];
			$scaledImage = $imageFile->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
			$scaledImages[$variantName] = $scaledImage;
		}

		$view = GeneralUtility::makeInstance(StandaloneSmartyView::class);
		$view->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths(['EXT:vierwd_base/Resources/Private/Templates/']);

		$view->assignMultiple([
			'cropVariants' => $cropVariants,
			'croppedImages' => $croppedImages,
			'scaledImages' => $scaledImages,
			'parameterArray' => $this->data['parameterArray'],
			'currentVariant' => key($cropVariants),
		]);

		$content = $view->render('Backend/Form/HotspotElement.tpl');

		return $content;
	}

	protected function getImage(): File {
		$sysFileReferenceData = $this->data['databaseRow'];

		if (!is_array($sysFileReferenceData['uid_local']) || count($sysFileReferenceData['uid_local']) !== 1) {
			throw new \Exception('Invalid file-reference: No uid_local or too many images?', 1536675838);
		}

		$imageData = $sysFileReferenceData['uid_local'][0];
		$imageFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($imageData['uid'], $imageData['row']);

		return $imageFile;
	}

	protected function getCropVariantCollection(): CropVariantCollection {
		if ($this->data['processedTca']['columns']['crop']['config']['cropVariants'] ?? null) {
			$cropVariants = $this->data['processedTca']['columns']['crop']['config']['cropVariants'];
		} else {
			// unfortunatly the default crop variants are not available publicly
			$reflectionClass = new \ReflectionClass(ImageManipulationElement::class);
			$defaultConfig = $reflectionClass->getDefaultProperties()['defaultConfig'];
			assert(is_array($defaultConfig));
			$cropVariants = $defaultConfig['cropVariants'];
		}
		$variantCollection = CropVariantCollection::create($this->data['databaseRow']['crop'], $cropVariants);

		return $variantCollection;
	}

}
