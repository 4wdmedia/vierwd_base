<?php

########################################################################
# Extension Manager/Repository config file for ext "vierwd_typo3version".
#
# Auto generated 13-07-2012 16:35
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 Installation',
	'description' => 'Adds some database configs during installation process',
	'category' => 'distribution',
	'author' => 'Robert Vock',
	'author_email' => 'robert.vock@4wdmedia.de',
	'author_company' => '4wd media',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'feedit' => '*',
			'scheduler' => '*',
			'realurl' => '*',
			'dd_googlesitemap' => '*',
			'extension_builder' => '*',
			'vierwd_typo3version' => '*',
			'vierwd_smarty' => '*',
			'vierwd_errors' => '*',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:3:{s:12:"ext_icon.gif";s:4:"59e6";s:17:"ext_localconf.php";s:4:"d4ac";s:16:"typo3version.php";s:4:"9131";}',
);

?>