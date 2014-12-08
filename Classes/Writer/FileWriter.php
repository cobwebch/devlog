<?php
namespace Devlog\Devlog\Writer;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 François Suter <typo3@cobweb.ch>, Cobweb Development Sarl
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes log entries to a given file
 */
class FileWriter extends AbstractWriter {
	/**
	 * Handle to the log file.
	 *
	 * @var resource
	 */
	protected $fileHandle;

	public function __construct($logger) {
		parent::__construct($logger);
		$configuration = $this->logger->getExtensionConfiguration();
		$absoluteFilePath = GeneralUtility::getFileAbsFileName(
			$configuration['logFilePath']
		);
		// If the file path is not valid, throw an exception
		if (empty($absoluteFilePath)) {
			throw new \Exception(
				sprintf(
					'Path to log file %s is invalid.',
					$configuration['logFilePath']
				),
				1416486859
			);
		}
		// If the file path is valid, try opening the file
		$this->fileHandle = @fopen(
			$absoluteFilePath,
			'a'
		);
		// Throw an exception if log file could not be opened properly
		if (!$this->fileHandle) {
			throw new \Exception(
				sprintf(
					'Log file %s could not be opened.',
					$configuration['logFilePath']
				),
				1416486470
			);
		}
	}

	public function __destruct() {
		@fclose($this->fileHandle);
	}

	/**
	 * Writes the entry to the log file.
	 *
	 * @param \Devlog\Devlog\Domain\Model\Entry $entry
	 * @return void
	 */
	public function write($entry) {
		$logLine = '';
		$logLine .= date('c', $entry->getCrdate());
		switch ($entry->getSeverity()) {
			case 0:
				$severity = 'INFO';
				break;
			case 1:
				$severity = 'NOTICE';
				break;
			case 2:
				$severity = 'WARNING';
				break;
			case 3:
				$severity = 'ERROR';
				break;
			default:
				$severity = 'OK';
		}
		$logLine .= ' [' . $severity . ']';
		$logLine .= ' ' . $entry->getMessage();
		$logLine .= ' (' . $entry->getLocation() . ' ' . $entry->getLine() . ')';
		$logLine .= "\n";
		@fwrite(
			$this->fileHandle,
			$logLine
		);
	}
}