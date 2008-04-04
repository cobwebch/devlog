<?php

########################################################################
# Extension Manager/Repository config file for ext: "devlog"
#
# Auto generated 31-03-2008 21:57
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Developer Log',
	'description' => 'The Developer log extension provides development logging/debugging functionality for usage of t3lib_div::devlog() and a BE module to browse the logs.',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Rene Fritz, Francois Suter',
	'author_email' => 'support@cobweb.ch',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '3.5.0-0.0.0',
			'php' => '3.0.0-0.0.0',
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:21:"class.tx_ccdevlog.php";s:4:"43b7";s:12:"ext_icon.gif";s:4:"0806";s:17:"ext_localconf.php";s:4:"cd26";s:14:"ext_tables.php";s:4:"0565";s:14:"ext_tables.sql";s:4:"47b1";s:20:"icon_tx_ccdevlog.gif";s:4:"7210";s:16:"locallang_db.xml";s:4:"8fd0";s:7:"tca.php";s:4:"5d43";s:14:"doc/manual.sxw";s:4:"4c0c";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"fcce";s:14:"mod1/index.php";s:4:"a14b";s:18:"mod1/locallang.xml";s:4:"9adf";s:22:"mod1/locallang_mod.xml";s:4:"07be";s:19:"mod1/moduleicon.gif";s:4:"c632";}',
	'suggests' => array(
	),
);

?>