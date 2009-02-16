<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
		
	t3lib_extMgm::addModule('tools','txdevlogM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}

// Includes
require_once(t3lib_extMgm::extPath('devlog', 'class.tx_devlog_tceforms.php'));

t3lib_extMgm::allowTableOnStandardPages('tx_devlog');

$TCA['tx_devlog'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog',		
		'label' => 'msg',	
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate,uid',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_devlog.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'severity, extkey, msg, location, line, data_var',
	)
);


?>