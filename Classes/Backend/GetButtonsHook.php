<?php

namespace Vierwd\VierwdBase\Backend;

/**
 * TYPO3 7 moved all save-actions into a drop-down. Only the default save action can be reached with one click.
 * The other save actions would need two clicks (SplitButton). We change the SplitButton to multiple buttons in a group.
 * Only the label for the first button will be shown.
 */
class GetButtonsHook {
	public function adjustSaveAndClose(array $params) {
		$buttonBars = $params['buttons'];

		foreach ($buttonBars as $position => $buttons) {
			foreach ($buttons as $priority => $subButtons) {
				foreach ($subButtons as $subButtonKey => $button) {
					if ($button instanceof \TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton) {
						// check if it is the save-button
						$buttonItems = $button->getButton();
						if (!isset($buttonItems['primary']) || !$buttonItems['primary']->getIcon() || $buttonItems['primary']->getIcon()->getIdentifier() !== 'actions-document-save') {
							continue;
						}

						$buttonItems['primary']->setShowLabelText(true);

						$saveButtons = array_merge([$buttonItems['primary']], $buttonItems['options']);

						array_splice($buttonBars[$position][$priority], $subButtonKey, 1, $saveButtons);
						break 3;
					}
				}
			}
		}

		return $buttonBars;
	}
}