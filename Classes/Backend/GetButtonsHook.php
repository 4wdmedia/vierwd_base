<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * TYPO3 7 moved all save-actions into a drop-down. Only the default save action can be reached with one click.
 * The other save actions would need two clicks (SplitButton). We change the SplitButton to multiple buttons in a group.
 * Only the label for the first button will be shown.
 */
class GetButtonsHook {

	/** @var IconFactory */
	private $iconFactory;

	/** @var LanguageService */
	private $languageService;

	public function __construct(IconFactory $iconFactory, LanguageService $languageService) {
		$this->iconFactory = $iconFactory;
		$this->languageService = $languageService;
	}

	/**
	 * @param array<string, array> $params
	 * @return array<string, array>
	 */
	public function adjustSaveAndClose(array $params) {
		$buttonBars = $params['buttons'];

		if (empty($buttonBars) || empty($buttonBars['left'])) {
			return $buttonBars;
		}

		// find the save button and replace it
		foreach ($buttonBars['left'] as &$buttonGroup) {
			foreach ($buttonGroup as $button) {
				if ($button instanceof InputButton && $button->getName() === '_savedok') {
					$saveAndClose = new InputButton();

					$saveAndClose->setForm($button->getForm());
					$saveAndClose->setIcon($this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));
					$saveAndClose->setName('_saveandclosedok');
					$saveAndClose->setTitle($this->languageService->sL(
						'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'
					));
					$saveAndClose->setValue('1');
					$saveAndClose->setShowLabelText(false);
					$buttonGroup[] = $saveAndClose;
					break 2;
				}
			}
			unset($buttonGroup);
		}

		return $buttonBars;
	}

}
