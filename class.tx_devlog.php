<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Rene Fritz (r.fritz@colorcube.de)
*  (c) 2010 Francois Suter (typo3@cobweb.ch)
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
***************************************************************/


/**
 * devlog function for the 'devlog' extension.
 *
 * @author		Rene Fritz <r.fritz@colorcube.de>
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_devlog
 *
 *  $Id$
 */
class tx_devlog {
	public $extKey = 'devlog';	// The extension key
	public $extConf = array(); // The extension configuration
	protected $rowCount; // The number of rows in the devlog table

	/**
	 * Constructor
	 * The constructor just reads the extension configuration and stores it in a member variable
	 */
	public function __construct() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * Developer log
	 *
	 * $logArr = array('msg'=>$msg, 'extKey'=>$extKey, 'severity'=>$severity, 'dataVar'=>$dataVar);
	 * 'msg'		string		Message (in english).
	 * 'extKey'		string		Extension key (from which extension you are calling the log)
	 * 'severity'	integer		Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
	 * 'dataVar'	array		Additional data you want to pass to the logger.
	 *
	 * @param	array		$logArr: log data array
	 * @return	void
	 */
	public function devLog($logArr) {
			// If the DB object is not yet instantiated or not connected to the DB, abort writing to the log
		if (!isset($GLOBALS['TYPO3_DB']) || !is_object($GLOBALS['TYPO3_DB']) || !$GLOBALS['TYPO3_DB']->link) {
			return;
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['nolog']) {
			return;
		}
			// this is a hack to prevent logging while initialization - $TYPO3_CONF_VARS will be reset while init
		if ($GLOBALS['EXTCONF'][$this->extKey]['nolog']) {
			return;
		}

			// If the severity is below the minimum logging level, don't log the entry
		if ($logArr['severity'] < $this->extConf['minLogLevel']) {
			return;
		}

			// If the key is in the list of keys to exclude, don't log the entry
		if (t3lib_div::inList($this->extConf['excludeKeys'], $logArr['extKey'])) {
			return;
		}

			// Check if the maximum number of rows has been exceeded
		if (!empty($this->extConf['maxRows'])) {
			$this->checkRowLimit();
		}

		$logEntry = array();
			// Try to get a page id that makes sense
		$pid = 0;
			// In the FE context, this is obviously the current page
		if (TYPO3_MODE == 'FE') {
			$pid = $GLOBALS['TSFE']->id;

			// In other contexts, a global variable may be set with a relevant pid
		} elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'])) {
			$pid = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'];
		}
		$logEntry['pid'] = $pid;
//		$logEntry['crdate'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['tstamp'];
		$logEntry['microtime'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['mstamp'];
		$logEntry['user'] = $GLOBALS['BE_USER']->user['uid'];
		$logEntry['ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');

			// Clean up the message
		$logEntry['message'] = t3lib_div::removeXSS($logArr['msg']);
			// There's no reason to have any markup in the extension key
		$logEntry['key'] = strip_tags($logArr['extKey']);
			// Severity can only be a number
		$logEntry['severity'] = intval($logArr['severity']);

			// Get information about the place where this method was called from
		$callPlaceInfo = $this->getCallPlaceInfo(debug_backtrace());
		$logEntry['location'] = $callPlaceInfo['basename'];
		$logEntry['line'] = $callPlaceInfo['line'];

		if (!empty($logArr['dataVar'])) {
			$extraData = $logArr['dataVar'];
			if (is_array($logArr['dataVar'])) {
				$extraData = array($logArr['dataVar']);
			}
			$serializedData = serialize($extraData);
			if (!isset($this->extConf['dumpSize']) || strlen($serializedData) <= $this->extConf['dumpSize']) {
				$logEntry['data'] = $serializedData;
			} else {
				$logEntry['data'] = serialize(array('tx_devlog_error' => 'toolong'));
			}
		}

		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['writers'] as $class) {
			/** @var $writerObject tx_devlog_LogWriter */
			$writerObject = t3lib_div::makeInstance($class);
			if ($writerObject instanceof tx_devlog_LogWriter) {
				$writerObject->writeEntry($logEntry);
			}
		}

			// Increase the (cached) number of rows
		$this->numRows++;
	}

	/**
	 * Given a backtrace, this method tries to find the place where a "devLog" function was called
	 * and return info about the place
	 *
	 * @param	array	$backTrace: function call backtrace, as provided by debug_backtrace()
	 *
	 * @return	array	information about the call place
	 */
	protected function getCallPlaceInfo($backTrace) {
		foreach ($backTrace as $entry) {
			if ($entry['class'] !== 'tx_devlog' && $entry['function'] === 'devLog') {
				$pathInfo = pathinfo($entry['file']);
				$pathInfo['line'] = $entry['line'];
				return $pathInfo;
			}
		}
		return null;
	}

	/**
	 * This method checks whether the number of rows in the devlog table exceeds the limit
	 * If yes, 10% of that amount is deleted, with older records going first
	 *
	 * @return	void
	 */
	protected function checkRowLimit() {
			// Get the total number of rows, if not already defined
		if (!isset($this->numRows)) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid)', 'tx_devlog', '');
			$result = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$this->numRows = $result[0];
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
			// Check if number of rows is above the limit and clean up if necessary
		if ($this->numRows > $this->extConf['maxRows']) {
				// Select the row from which to start cleaning up
				// To achieve this, order by creation date (so oldest come first)
				// then offset by 10% of maxRows and get the next record
				// This will return a timestamp that is used as a cut-off date
			$numRowsToRemove = round(0.1 * $this->extConf['maxRows']);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('crdate', 'tx_devlog', '', '', 'crdate', $numRowsToRemove.',1');
			$result = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$crdate = $result[0];
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
				// Delete all rows older or same age as previously found timestamp
				// This will problably delete a bit more than 10% of maxRows, but will at least
				// delete complete log runs
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_devlog', "crdate <= '".$crdate."'");
			$numRemovedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
				// Update (cached) number of rows
			$this->numRows -= $numRemovedRows;
				// Optimize the table (if option is active)
			if ($this->extConf['optimize']) {
				$GLOBALS['TYPO3_DB']->sql_query('OPTIMIZE table tx_devlog');
			}
		}
	}
}
?>