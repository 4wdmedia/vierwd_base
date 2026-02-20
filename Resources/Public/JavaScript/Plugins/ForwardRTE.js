import {Plugin} from '@ckeditor/ckeditor5-core';

class ForwardRTE extends Plugin {
	init() {
		const editor = this.editor;

		// Wait for editor to be ready before manipulating DOM
		editor.on('ready', () => {
			// Create status bar element
			const statusBar = document.createElement('div');
			statusBar.className = 'ck-status-bar';
			statusBar.style.cssText = `
				padding: 5px 10px;
				font-size: 12px;
			`;

			// Insert after editor
			editor.ui.view.element.parentNode.insertBefore(
				statusBar,
				editor.ui.view.element.nextSibling
			);

			// Update status bar content
			this.updateStatusBar(statusBar);

			// Listen for selection changes via DOM events
			const editableElement = editor.ui.view.editable.element;
			editableElement.addEventListener('selectionchange', () => {
				this.updateStatusBar(statusBar);
			});

			// Listen for keyup and mouseup to catch selection changes
			editableElement.addEventListener('keyup', () => {
				this.updateStatusBar(statusBar);
			});

			editableElement.addEventListener('mouseup', () => {
				this.updateStatusBar(statusBar);
			});

			// Listen for content changes
			editor.model.document.on('change', () => {
				this.updateStatusBar(statusBar);
			});
		});
	}

	updateStatusBar(statusBar) {
		const editor = this.editor;
		const editableElement = editor.ui.view.editable.element;

		if (!editableElement) {
			statusBar.innerHTML = 'Editor not ready';
			return;
		}

		// Get DOM selection
		const selection = window.getSelection();
		if (!selection.rangeCount) {
			statusBar.innerHTML = 'No selection';
			return;
		}

		const range = selection.getRangeAt(0);
		let currentElement = range.commonAncestorContainer;

		// If we're in a text node, get the parent element
		if (currentElement.nodeType === Node.TEXT_NODE) {
			currentElement = currentElement.parentElement;
		}

		// Build HTML path
		const path = this.buildElementPath(currentElement, editableElement);
		statusBar.innerHTML = path;
	}

	buildElementPath(element, editableElement) {
		if (!element || !editableElement) {
			return '';
		}

		const path = [];
		let current = element;

		// Traverse up the DOM tree until we reach the editable element
		while (current && current !== editableElement && current !== document.body) {
			if (current.nodeType === Node.ELEMENT_NODE) {
				let elementName = current.tagName.toLowerCase();

				// Add class information if available
				if (current.className && current.className.trim()) {
					// Filter out CKEditor internal classes
					const classes = current.className.split(/\s+/)
						.filter(cls => !cls.startsWith('ck-'))
						.join('.');
					if (classes) {
						elementName += '.' + classes;
					}
				}

				// Add id information if available
				if (current.id && !current.id.startsWith('ck-')) {
					elementName += '#' + current.id;
				}

				path.unshift(elementName);
			}
			current = current.parentElement;
		}

		return path.join(' ');
	}
}

export {ForwardRTE};
