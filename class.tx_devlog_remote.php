<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Fabien Udriot <fabien.udriot@ecodev.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id$
***************************************************************/

/**
 * Classes used as ExtDirect's router
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package	TYPO3
 * @subpackage	tx_devlog
 */
class tx_devlog_remote {

	/**
	 * Get / Post parameters
	 * 
	 * @var array
	 */
	var $parameters = array();

	/**
	 * Constructor
	 * 
	 */
	public function __construct() {
		$this->parameters = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
	}

	/**
	 * Fetches log depending on parameters
	 * 
	 * @global t3lib_DB $TYPO3_DB
	 * @return array
	 */
	public function indexAction() {
		global $TYPO3_DB;

		// Defines list of fields
		$fields = array();
		$fields[] = 'uid';
		$fields[] = 'pid';
		$fields[] = 'crdate';
		$fields[] = 'crmsec';
		$fields[] = 'cruser_id';
		$fields[] = 'severity';
		$fields[] = 'extkey';
		$fields[] = 'msg';
		$fields[] = 'location';
		$fields[] = 'line';
		$fields[] = 'data_var';
		
		$records = $TYPO3_DB->exec_SELECTgetRows(implode(',', $fields), 'tx_devlog', '', $groupBy = '', $orderBy = 'uid DESC', $this->getLimit());
		foreach ($records as &$record) {
			$record['cruser_formated'] = $this->formatCruser($record['cruser_id']);
			$record['severity_formated'] = $this->formatSeverity($record['severity']);
			$record['pid_formated'] = $this->formatPid($record['pid']);
		}

		$datasource['metaData'] = $this->getMetaData($fields);
		$datasource['total'] = $TYPO3_DB->exec_SELECTcountRows('uid', 'tx_devlog', '');
		$datasource['records'] = $records;
		$datasource['success'] = TRUE;
		// For ExtDirect
		//return $datasource;

		// For JsonReader
		echo json_encode($datasource);
	}

	/**
	 * Get datasource's meta data
	 *
	 * @param array $fields: list of field
	 * @return array $metaData
	 */
	protected function getMetaData($fields) {

		// ExtJS api: http://www.extjs.com/deploy/dev/docs/?class=Ext.data.JsonReader
//		metaData: {
//        // used by store to set its sortInfo
//        "sortInfo":{
//           "field": "name",
//           "direction": "ASC"
//        },
//        // paging data (if applicable)
//        "start": 0,
//        "limit": 2,
//        // custom property
//        "foo": "bar"
//    },
		$metaData['idProperty'] = 'uid';
		$metaData['root'] = 'records';
		$metaData['totalProperty'] = 'total';
		$metaData['successProperty'] = 'success';
		$metaData['fields'] = array(
			// Additional fields
			array('name' => 'cruser_formated', 'type' => 'string'),
			array('name' => 'severity_formated', 'type' => 'string'),
			array('name' => 'pid_formated', 'type' => 'string'),
		);

		// merges additiionnal fields with "regular" fields
		$metaData['fields'] = array_merge($this->getFieldMetaData($fields), $metaData['fields']);
		return $metaData;
	}

	/**
	 * Get MetaData for fields
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @param array $fields: list of field
	 * @return array $fieldsMetaData: list of metadata for the given $fields
	 */
	protected function getFieldMetaData($fields) {
		global $TYPO3_DB;
		$fieldsInTable = $TYPO3_DB->admin_get_fields('tx_devlog');

		foreach ($fields as $fieldName) {
			if ($fieldName == 'crdate' || $fieldName == 'crmsec') {
				$fieldsMetaData[] = array('name' => $fieldName, 'type' => 'date', 'dateFormat' => 'timestamp');
			}
			elseif (isset($fieldsInTable[$fieldName])) {
				$fieldType = $fieldsInTable[$fieldName]['Type'];
				if (strpos($fieldType, 'int') !== FALSE) {
					$fieldsMetaData[] = array('name' => $fieldName, 'type' => 'int');
				}
				else { // means this is a string
					$fieldsMetaData[] = array('name' => $fieldName, 'type' => 'string');
				}
			}
		}
		return $fieldsMetaData;
	}

	/**
     * Returns a linked icon with title from a record
     * NOTE: currently this is only called for the pages table, as table names are not stored in the devlog (but a pid may be)
     *
     * @param	integer		ID of the record to link to
     * @return  string		HTML for icon, title and link
     */
    function formatPid($uid) {
		if (empty($uid)) {
			return '';
		}
		else {
				// Retrieve the stored page information
				// (pages were already fetched in getLogFilters)
			$page = t3lib_BEfunc::getRecord('pages', $uid);
			$elementTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);
//			$row = $this->records['pages'][$uid];
//			$iconAltText = t3lib_BEfunc::getRecordIconAltText($row, 'pages');

				// Create icon for record
//			$elementIcon = t3lib_iconworks::getIconImage('pages', $row, $BACK_PATH, 'class="c-recicon" title="' . $iconAltText . '"');
			$elementIcon = t3lib_iconWorks::getSpriteIcon('apps-pagetree-page-default');

				// Return item with edit link
			$editOnClick = 'top.loadEditId(' . $uid . ')';
			$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $elementIcon . $elementTitle . '</a>';
			return $string;
		}
    }

	/**
	 * Returns the serverity icon
	 *
	 * @return string
	 */
	protected function formatSeverity($severity) {
		switch ($severity) {
			case -1 : // OK
				$spriteName = 'status-dialog-ok';
				break;
			case 0 : // Info
				$spriteName = 'status-dialog-information';
				break;
			case 1 : // Notice
				$spriteName = 'status-dialog-notification';
				break;
			case 2 : // Warning
				$spriteName = 'status-dialog-warning';
				break;
			case 3 : // Error
				$spriteName = 'status-dialog-error';
				break;
		}

		return t3lib_iconWorks::getSpriteIcon($spriteName);
	}

	/**
	 * This method gets the title and the icon for a given record of a given table
	 * It returns these as a HTML string
	 *
	 * @param	integer		$uid: primary key of the record
	 * @return	string		HTML to display
	 */
	protected function formatCruser($uid = 0) {
		global $TCA;
		$row = t3lib_BEfunc::getRecord('be_users', $uid);
		$elementTitle = t3lib_BEfunc::getRecordTitle('be_users', $row, 1);
		$spriteName = $TCA['be_users']['ctrl']['typeicon_classes'][$row['admin']];
		$elementIcon = t3lib_iconWorks::getSpriteIcon($spriteName);
		return $elementIcon . $elementTitle;
	}
	
	/**
	 * Returns LIMIT 3 OFFSET 0
	 *
	 * @return string
	 */
	protected function getLimit() {
		$request = '';
		if (isset($this->parameters['limit'])) {
			$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
			$start = 0;
			if (isset($this->parameters['start'])) {
				$start = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
			}
			$request = $limit . ' OFFSET ' . $start;
		}
		return $request;
	}

}

?>