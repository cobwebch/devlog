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
	 * Extension configuration
	 *
	 * @var array
	 */
	var $configurations = array();

	/**
	 * Constructor
	 *
	 * @global Language $LANG;
	 */
	public function __construct() {
		global $LANG;
		$this->parameters = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
		
		// Load language
		$LANG->includeLLFile('EXT:devlog/Resources/Private/Language/locallang.xml');

		// Get extension configuration
		$this->configurations = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['devlog']);
	}

	/**
	 * Fetches log depending on parameters
	 * 
	 * @global t3lib_DB $TYPO3_DB
	 * @return void
	 */
	public function indexAction() {
		global $TYPO3_DB;

		// Defines list of fields
		$fields = array('uid', 'pid', 'crdate', 'crmsec', 'cruser_id', 'severity', 'extkey', 'msg', 'location', 'line', 'data_var');
		
		$records = $TYPO3_DB->exec_SELECTgetRows(implode(',', $fields), 'tx_devlog', $this->getClause(), $groupBy = '', $this->getOrder(), $this->getLimit());
		
//		$request = $TYPO3_DB->SELECTquery(implode(',', $fields), 'tx_devlog', $this->getClause(), $groupBy = '', $this->getOrder(), $this->getLimit());
//		t3lib_div::debug($request, '$datasource');
		
		foreach ($records as &$record) {
			$record['cruser_formatted'] = $this->formatCruser($record['cruser_id']);
			$record['pid_formatted'] = $this->formatPid($record['pid']);
			$record['extkey_formatted'] = $this->formatExtKey($record['extkey']);
			$record['data_var'] = $this->formatDataVar($record['data_var']);
		}

		$datasource['metaData'] = $this->getMetaData($fields);
		$datasource['total'] = $TYPO3_DB->exec_SELECTcountRows('uid', 'tx_devlog', $this->getClause());
		$datasource['records'] = $records;
		$datasource['success'] = TRUE;
		
		// For ExtDirect (when it will be working with metadata)
		//return $datasource;

//		t3lib_div::debug($datasource, '$datasource');
		// For JsonReader
		echo json_encode($datasource);
	}

	/**
	 * Get clause SQL order
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @return array $metaData
	 */
	protected function getClause() {
		global $TYPO3_DB;

		$clauses = array();
		$severity = filter_input(INPUT_GET, 'severity', FILTER_VALIDATE_INT, array('options'=>array('min_range'=>-1, 'max_range'=>3)));
		if ($severity !== FALSE && $severity !== NULL) {
			$clauses[] = 'severity = ' . $severity;
		}

		$pid = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT, array('options'=>array('min_range'=> 0)));
		if ($pid !== FALSE && $pid !== NULL) {
			$clauses[] = 'pid = ' . $pid;
		}

		$extKey = filter_input(INPUT_GET, 'extKey', FILTER_SANITIZE_STRING);
		if ($extKey) {
			$clauses[] = 'extKey = "' . $extKey . '"';
		}
		
		// Add other parameter
		if (isset($this->parameters['limit']) && $this->parameters['limit'] == '1000') {
			$records = $TYPO3_DB->exec_SELECTgetRows('MAX(crmsec) AS maximum, MIN(crmsec) AS minimum', 'tx_devlog', '');
			if (!empty($records)) {
				$clauses[] = 'crmsec = ' . $records[0]['maximum'];
			}
		}
		elseif (isset($this->parameters['limit']) && (int) $this->parameters['limit'] > 1000) {
			$clauses[] = 'crmsec = ' . $this->parameters['limit'];
		}
		return implode(' AND ', $clauses);
	}

	/**
	 * Get SQL order
	 *
	 * @return array $metaData
	 */
	protected function getOrder() {
		$order = 'uid DESC';
		if (isset($this->parameters['sort']) && isset($this->parameters['dir'])) {
			// check wheter the field is formatted or not
			// if yes removed the "_formatted" suffix to query the database properly
			if (strpos($this->parameters['sort'], '_formatted') > 1) {
				$this->parameters['sort'] = str_replace('_formatted', '', $this->parameters['sort']);
			}

			if ($this->parameters['dir'] == 'ASC' || $this->parameters['dir'] == 'DESC') {
				$order = $this->parameters['sort'] . ' ' . $this->parameters['dir'];
			}
		}
		return $order;
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
			array('name' => 'cruser_formatted', 'type' => 'string'),
			array('name' => 'pid_formatted', 'type' => 'string'),
			array('name' => 'extkey_formatted', 'type' => 'string'),
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
     * Returns a formatted data var
     *
     * @param	string		data var to be formatted
     * @return  string		foramted data var
     */
    function formatDataVar($dataVar) {
		$result = '';
		if ($dataVar !== '') {
			$fullData = @unserialize($dataVar);
			$result = t3lib_div::view_array($fullData);
		}
		return $result;
	}

	/**
     * Returns a formatted extkey
     *
	 * @global $TYPO3_LOADED_EXT
     * @param	string		data var to be formatted
     * @return  string		foramted data var
     */
    function formatExtKey($extKey) {
		global $TYPO3_LOADED_EXT;
		$result = '';
		if (isset($TYPO3_LOADED_EXT[$extKey]['typo3RelPath'])) {
			$iconPath = $TYPO3_LOADED_EXT[$extKey]['typo3RelPath'] . 'ext_icon.gif';
			$result = '<img src="' . $iconPath . '" alt="" />';
		}
		return $result . ' ' . $extKey;
	}

	/**
     * Returns a linked icon with title from a record
     * NOTE: currently this is only called for the pages table, as table names are not stored in the devlog (but a pid may be)
     *
     * @param	integer		ID of the record to link to
     * @return  string		HTML for icon, title and link
     */
    function formatPid($uid) {
			// Retrieve the stored page information
			// (pages were already fetched in getLogFilters)
		$page = t3lib_BEfunc::getRecord('pages', $uid);
		$elementTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);

			// Create icon for record
		$elementIcon = t3lib_iconWorks::getSpriteIconForRecord('pages');

			// Return item with edit link
		$editOnClick = 'top.loadEditId(' . $uid . ')';
		$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $elementIcon . $elementTitle . '</a>';
		return $string;
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
		if (isset($this->parameters['limit']) && (int) $this->parameters['limit'] != -1) {
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