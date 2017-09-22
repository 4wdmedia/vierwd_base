<?php

return [
	'CType' => 'vierwd_imprint',

	// if this element is only available for admins, set to true to disable warning
	// after login, if no editor has access to this CType
	'adminOnly' => true,

	// You can configure different groups of ContentElements
	// define the Group-Name in _groups.php
	'group' => 'vierwd',

	// Name and description are visible to editor.
	// You can remove the name, if a list_type is set.
	// In that case the plugin-name configured for that list_type will be used (as set in extension_builder)
	'name' => 'Impressum',
	'description' => '',

	// icon identifier. Examples: content-image, content-header, content-plugin, content-text
	// @see TYPO3\CMS\Core\Imaging\IconRegistry
	'iconIdentifier' => 'content-text',

	// Smarty Template file for this Content Element.
	// This will only be used, if list_type and switchableControllerActions is empty.
	// Specify a path relative to Resource/Private/Templates or start the path with EXT:...
	'template' => 'FCE/Imprint.tpl',

	// Easy configuration for backend form:
	// The basic form only has the CType, hidden header, access control and appearance (layout and section frame)
	// If a flexform is configured, it will also automatically show up
	// You can add some fields in this comma-separated list.
	// - bodytext shows a textarea field
	// - richtext shows the RTE (overwriting bodytext)
	// - simpleheaders shows the header and header_layout fields
	// - fullheaders also shows all other header fields (overwriting simpleheaders)
	// - image shows the image-tab
	// - media shows the media-tab
	'tcaType' => 'simpleheaders',
];
