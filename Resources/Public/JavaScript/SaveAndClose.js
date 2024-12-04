/* global TYPO3: false */

const saveAndCloseButton = document.querySelector('[name="_saveandclosedok"]');
if (saveAndCloseButton) {
	saveAndCloseButton.addEventListener('click', event => {
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		TYPO3.FormEngine.saveAndCloseDocument();
	});
}
