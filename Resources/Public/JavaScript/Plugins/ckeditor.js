/* global CKEDITOR: false */
'use strict';

(function () {
	CKEDITOR.plugins.add('forward', {
		afterInit: function(editor) {
			editor._.elementsPath.filters.push(function(element, name) {
				if (element.$.tagName === 'BODY') {
					return;
				}
				var classes = element.$.className.split(/\s+/).filter(function(className) {
					return className && className.substr(0, 4) !== 'cke_';
				});
				return element.$.tagName.toLowerCase() + (classes.length ? '.' + classes.join('.') : '');
			});

			editor.on('saveSnapshot', function(ev) {
				if (editor.getSelection(1) && editor.elementPath()) {
					// trigger a selectionChange. This will update the path in the footer.
					// Unfortunatly there is no better way to trigger an update of the elementspath-plugin
					editor.fire('selectionChange', {selection: editor.getSelection(1), path: editor.elementPath()});
				}
			});
		}
	});
})();
