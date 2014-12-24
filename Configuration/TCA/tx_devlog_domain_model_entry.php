<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

return array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry',
		'label' => 'message',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate DESC, sorting ASC',
		'dividers2tabs' => TRUE,
		'searchFields' => 'severity,extkey,message,location,ip,line,extra_data',
		'typeicon_column' => 'severity',
		'typeicon_classes' => array(
			'default' => 'extensions-devlog-entry',
			'-1' => 'status-dialog-ok',
			'0' => 'status-dialog-information',
			'1' => 'status-dialog-notification',
			'2' => 'status-dialog-warning',
			'3' => 'status-dialog-error'
		),
	),
	'interface' => array(
		'showRecordFieldList' => 'severity, extkey, message, location, line, ip, extra_data',
	),
	'types' => array(
		'1' => array('showitem' => 'crdate, cruser_id, severity, extkey, message, location, line, ip, extra_data'),
	),
	'columns' => array(
		'crdate' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.crdate',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'eval' => 'datetime'
			)
		),
		'cruser_id' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.cruser_id',
			'config' => array(
				'type' => 'select',
				'readOnly' => TRUE,
				'items' => array(
					array()
				),
				'foreign_table' => 'be_users',
				'size' => 1
			)
		),
		'severity' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.severity',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'Devlog\\Devlog\\Utility\\TceForms->displaySeverity',
			)
		),
		'extkey' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.extkey',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'message' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.message',
			'config' => array(
				'type' => 'text',
				'readOnly' => TRUE,
				'cols' => 50,
				'rows' => 5,
				'eval' => 'trim'
			)
		),
		'location' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.location',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'line' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.line',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'ip' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.ip',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'extra_data' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:devlog/Resources/Private/Language/locallang_db.xlf:tx_devlog_domain_model_entry.extra_data',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'Devlog\\Devlog\\Utility\\TceForms->displayExtraData',
			)
		),
	),
);
