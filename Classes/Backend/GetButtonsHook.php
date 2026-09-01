<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Backend;

use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * TYPO3 7 moved all save-actions into a drop-down. Only the default save action can be reached with one click.
 * The other save actions would need two clicks (SplitButton). We change the SplitButton to multiple buttons in a group.
 * Only the label for the first button will be shown.
 */
class GetButtonsHook {

	private readonly LanguageService $languageService;

	public function __construct(private readonly IconFactory $iconFactory, LanguageServiceFactory $languageServiceFactory, private readonly PageRenderer $pageRenderer) {
		$this->languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
	}

	#[AsEventListener('vierwdSaveAndClose')]
	public function __invoke(ModifyButtonBarEvent $event): void {
		$buttonBars = $event->getButtons();

		if (empty($buttonBars) || empty($buttonBars['left'])) {
			return;
		}

		$this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(JavaScriptModuleInstruction::create('@vierwd/vierwd_base/SaveAndClose.js'));

		// find the save button and replace it
		foreach ($buttonBars['left'] as &$buttonGroup) {
			foreach ($buttonGroup as $button) {
				if ($button instanceof InputButton && $button->getName() === '_savedok') {
					$saveAndClose = new InputButton();

					$saveAndClose->setForm($button->getForm());
					$saveAndClose->setIcon($this->iconFactory->getIcon('actions-document-save-close', IconSize::SMALL));
					$saveAndClose->setName('_saveandclosedok');
					$saveAndClose->setTitle($this->languageService->sL(
						'core.core:rm.saveCloseDoc'
					));
					$saveAndClose->setValue('1');
					$saveAndClose->setShowLabelText(false);
					$buttonGroup[] = $saveAndClose;
					break 2;
				}
			}
			unset($buttonGroup);
		}
		$event->setButtons($buttonBars);
	}

}
