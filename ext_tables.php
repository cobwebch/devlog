<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE=='BE') {
	t3lib_extMgm::addModule('tools', 'txdevlogM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}

// Includes
require_once(t3lib_extMgm::extPath('devlog', 'class.tx_devlog_tceforms.php'));

t3lib_extMgm::allowTableOnStandardPages('tx_devlog');

$TCA['tx_devlog'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog',		
		'label' => 'msg',	
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate DESC,uid',
		'rootLevel' => -1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_devlog.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'severity, extkey, msg, location, line, data_var',
	)
);

// Add context sensitive help (csh) to the backend module and to the tx_devlog table
t3lib_extMgm::addLLrefForTCAdescr('_MOD_tools_txdevlogM1', 'EXT:devlog/locallang_csh_txdevlog.xml');
t3lib_extMgm::addLLrefForTCAdescr('tx_devlog', 'EXT:devlog/locallang_csh_txdevlog.xml');


$pathToExtension = t3lib_extMgm::extRelPath('devlog');
$icons = array(
	'error' => $pathToExtension . 'Resources/Public/images/icons/error.png',
	'info' => $pathToExtension . 'Resources/Public/images/icons/information.png',
	'notification' => $pathToExtension . 'Resources/Public/images/icons/notification.png',
	'ok' => $pathToExtension . 'Resources/Public/images/icons/ok.png',
	'warning' => $pathToExtension . 'Resources/Public/images/icons/warning.png',
);

t3lib_SpriteManager::addSingleIcons($icons, 'devlog');
?>
