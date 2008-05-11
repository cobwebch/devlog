<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 René Fritz (r.fritz@colorcube.de)
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
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Francois Suter <support@cobweb.ch>
 */
class tx_devlog {
	var $extKey = 'devlog';	// The extension key.
	
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
	function devLog($logArr)	{
		global $TYPO3_CONF_VARS;
	
		if ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['nolog']) return;
			// this is a hack to prevent logging while initialization - $TYPO3_CONF_VARS will be reset while init
		if ($GLOBALS['EXTCONF'][$this->extKey]['nolog']) return;

		
		$insertFields = array();
		$insertFields['pid'] = intval($GLOBALS['TSFE']->id);
		$insertFields['crdate'] = $TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['tstamp'];
		$insertFields['crmsec'] = $TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['mstamp'];
		$insertFields['cruser_id'] = intval($GLOBALS['BE_USER']->user['uid']);
		$insertFields['msg'] = $logArr['msg'];
		$insertFields['extkey'] = $logArr['extKey'];
		$insertFields['severity'] = $logArr['severity'];

			// Try to get information about the place where this method was called from
		if (function_exists('debug_backtrace')) {
			$callPlaceInfo = $this->getCallPlaceInfo(debug_backtrace());
			$insertFields['location'] = $callPlaceInfo['basename'];
			$insertFields['line'] = $callPlaceInfo['line'];
		}

		if (!empty($logArr['dataVar'])) {
			$insertFields['data_var'] = '"'.$GLOBALS['TYPO3_DB']->quoteStr(serialize($logArr['dataVar']).'"', 'tx_devlog');
		}

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', $insertFields);
	}
	
	/**
	 * Given a backtrace, this method tries to find the place where a "devLog" function was called
	 * and return info about the place
	 *
	 * @param	array	$backTrace: function call backtrace, as provided by debug_backtrace()
	 *
	 * @return	array	information about the call place
	 */
	function getCallPlaceInfo($backTrace) {
		foreach ($backTrace as $entry) {
			if ($entry['class'] !== 'tx_devlog' && $entry['function'] === 'devLog') {
				$pathInfo = pathinfo($entry['file']);
				$pathInfo['line'] = $entry['line'];
				return $pathInfo;
			}
		}
		return null;
	}
}



?>