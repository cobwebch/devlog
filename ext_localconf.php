<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Define the timestamp for the current run

if (!$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['mstamp']) {
	$parts = explode(' ',microtime());
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['mstamp'] = (string)$parts[1].(string)intval((float)$parts[0]*10000.0);
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['tstamp'] = $parts[1];
}

// Register the logging method with the appropriate hook

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'][$_EXTKEY] = 'EXT:'.$_EXTKEY.'/class.tx_devlog.php:&tx_devlog->devLog';
?>