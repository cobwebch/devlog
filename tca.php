<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_devlog'] = array(
	'ctrl' => $TCA['tx_devlog']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'severity,extkey,msg,location,data_var'
	),
	'feInterface' => $TCA['tx_devlog']['feInterface'],
	'columns' => array(
		'crdate' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.crdate',
			'config' => array(
				'type' => 'input',
				'eval' => 'datetime',
				'readOnly' => true
			)
		),
		'severity' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'tx_devlog_tceforms->displaySeverity',

			)
		),
		'extkey' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.extkey',
			'config' => array(
				'type' => 'none'
			)
		),
		'msg' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.msg',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'tx_devlog_tceforms->displayMessage',
			)
		),
		'location' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.location',
			'config' => array(
				'type' => 'none'
			)
		),
		'line' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.line',
			'config' => array(
				'type' => 'none'
			)
		),
		'data_var' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.data_var',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'tx_devlog_tceforms->displayAdditionalData',
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'crdate;;;;1-1-1, severity;;;;1-1-1, extkey, msg, location;;1, data_var;;;;1-1-1')
	),
	'palettes' => array(
		'1' => array('showitem' => 'line', 'canNotCollapse' => true)
	)
);
?>