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

		// TODO: Add saveAndClose for TYPO3 9

		return $buttonBars;
	}
}