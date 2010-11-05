<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */
$extensionPath = t3lib_extMgm::extPath('devlog');
return array(
	'tx_devlog_logwriter' => $extensionPath . 'interfaces/interface.tx_devlog_logwriter.php',
	'tx_devlog_writers_database' => $extensionPath . 'writers/class.tx_devlog_writers_database.php',
);
?>
