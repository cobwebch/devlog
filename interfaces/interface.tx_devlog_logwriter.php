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
 * Interface that must be implemented for all classes that want to write devLog entries to some output system
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_devlog
 *
 *  $Id$
 */
interface tx_devlog_LogWriter {
	/**
	 * This method is used to write a devLog entry to some support (database, file, etc.) or output
	 * It receives an array with the following information:
	 *
	 * pid =>		page where the call happened (if relevant, may be empty)
	 * severity =>	severity of the log entry
	 * message =>	main message of the log entry
	 * key =>		some key to identify the origin of the log entry (e.g. an extension's key)
	 * microtime =>	the microtime at which the entry happened (microtime is used to make sure we have different times for near-concurrent entries)
	 * location =>	the file where the call was triggered
	 * line =>		the line at which the call was triggered
	 * user =>		the BE user who was logged in at the time where the entry was written (may be empty)
	 * date =>		the timestamp at which the entry happened (less precise, but more usable than the microtime)
	 * ip =>		the IP address of the client machine which was making the request that triggered the entry
	 * data =>		additional information related to the entry, as a PHP array
	 *
	 * @abstract
	 * @param	array	$logEntry: addition information related to the log entry
	 * @return void
	 */
	public function writeEntry($logEntry);
}
?>