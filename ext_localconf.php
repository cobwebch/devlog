<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Load Devlog Classes
require_once(t3lib_extMgm::extPath('devlog', 'class.tx_devlog_exception.php'));


	// Define the timestamp for the current run
	// TODO: move to tx_devlog constructor (as static variables)
if (!$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['mstamp']) {
	$parts = explode(' ', microtime());
		// Timestamp with microseconds to make sure 2 log runs can always be distinguished
		// even when happening very close to one another
		// TODO: improve with microtime(true), but requires PHP > 5
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['mstamp'] = (string)$parts[1] . (string)intval((float)$parts[0] * 10000.0);
		// Normal timestamp
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['tstamp'] = $parts[1];
}

	// Register the logging method with the appropriate hook
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'][$_EXTKEY] = 'EXT:'.$_EXTKEY.'/class.tx_devlog.php:&tx_devlog->devLog';

	// @todo: choose one technique for loading data
	//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']['TYPO3.Devlog.Remote'] = 'EXT:devlog/class.tx_devlog_remote.php:tx_devlog_remote';
$TYPO3_CONF_VARS['BE']['AJAX']['LogController::indexAction'] = 'EXT:devlog/class.tx_devlog_remote.php:tx_devlog_remote->indexAction';
$TYPO3_CONF_VARS['BE']['AJAX']['LogController::getDataVar'] = 'EXT:devlog/class.tx_devlog_remote.php:tx_devlog_remote->getDataVar';
$TYPO3_CONF_VARS['BE']['AJAX']['LogController::getLastLogTime'] = 'EXT:devlog/class.tx_devlog_remote.php:tx_devlog_remote->getLastLogTime';

	// Register log writers
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['writers']['db'] = 'tx_devlog_writers_Database';
?>