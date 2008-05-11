<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_devlog'] = Array (
	'ctrl' => $TCA['tx_devlog']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'severity,extkey,msg,location,data_var'
	),
	'feInterface' => $TCA['tx_devlog']['feInterface'],
	'columns' => Array (
		'severity' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity',		
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.0', '0'),
					Array('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.1', '1'),
					Array('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.2', '2'),
					Array('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.3', '3'),
					Array('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.4', '-1'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'extkey' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.extkey',		
			'config' => Array (
				'type' => 'input',	
				'size' => '20',	
				'max' => '40',	
				'eval' => 'trim',
			)
		),
		'msg' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.msg',		
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '3',
			)
		),
		'location' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.location',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',	
				'eval' => 'trim',
			)
		),
		'line' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.line',		
			'config' => Array (
				'type' => 'input',	
				'size' => '10',	
				'max' => '20',	
				'eval' => 'int',
			)
		),
		'data_var' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:devlog/locallang_db.xml:tx_devlog.data_var',		
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		)
	),
	'types' => Array (
		'0' => Array('showitem' => 'severity;;;;1-1-1, extkey, msg, location, data_var')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);
?>