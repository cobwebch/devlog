<?php
/***************************************************************
*  Copyright notice
*
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
 * This class writes log entries to the database table "tx_devlog"
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_devlog
 *
 *  $Id$
 */
class tx_devlog_writers_Database implements tx_devlog_LogWriter {
	/**
	 * This method is used to write a devLog entry to the database
	 *
	 * @param	array	$logEntry: addition information related to the log entry
	 * @return void
	 */
	public function writeEntry($logEntry) {
		$insertFields = array(
			'pid' => $logEntry['pid'],
			'crmsec' => $logEntry['microtime'],
			'crdate' => $GLOBALS['EXEC_TIME'],
			'cruser_id' => $logEntry['user'],
			'ip' => $logEntry['ip'],
			'severity' => $logEntry['severity'],
			'extkey' => $logEntry['key'],
			'msg' => $logEntry['message'],
			'location' => $logEntry['location'],
			'line' => $logEntry['line'],
			'data_var' => $logEntry['data']
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', $insertFields);
	}
}
?>