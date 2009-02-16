<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_devlog'] = array(
	'ctrl' => $TCA['tx_devlog']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'severity,extkey,msg,location,data_var'
	),
	'feInterface' => $TCA['tx_devlog']['feInterface'],
	'columns' => array(
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
				'type' => 'none',
				'eval' => 'trim',
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
				'type' => 'none',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'line' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.line',
			'config' => array(
				'type' => 'none',
				'size' => '10',
				'max' => '20',
				'eval' => 'int',
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
		'0' => Array('showitem' => 'severity;;;;1-1-1, extkey, msg, location;;1, data_var')
	),
	'palettes' => array(
		'1' => Array('showitem' => 'line')
	)
);
?>