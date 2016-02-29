<?php
namespace Devlog\Devlog\Writer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Devlog\Devlog\Utility\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes log entries to a given file
 */
class FileWriter extends AbstractWriter
{
    /**
     * Handle to the log file.
     *
     * @var resource
     */
    protected $fileHandle;

    /**
     * FileWriter constructor.
     *
     * @param Logger $logger
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($logger)
    {
        parent::__construct($logger);
        $configuration = $this->logger->getExtensionConfiguration();
        $absoluteFilePath = GeneralUtility::getFileAbsFileName(
                $configuration->getLogFilePath()
        );
        // If the file path is valid, try opening the file
        $this->fileHandle = @fopen(
                $absoluteFilePath,
                'a'
        );
        // Throw an exception if log file could not be opened properly
        if (!$this->fileHandle) {
            throw new \UnexpectedValueException(
                    sprintf(
                            'Log file %s could not be opened.',
                            $configuration->getLogFilePath()
                    ),
                    1416486470
            );
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        @fclose($this->fileHandle);
    }

    /**
     * Writes the entry to the log file.
     *
     * @param \Devlog\Devlog\Domain\Model\Entry $entry
     * @return void
     */
    public function write($entry)
    {
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