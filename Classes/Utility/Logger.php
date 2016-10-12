<?php
namespace Devlog\Devlog\Utility;

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

use Devlog\Devlog\Domain\Model\Entry;
use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use Devlog\Devlog\Writer\WriterInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The main logging class.
 *
 * Calls the various writers to actually store the log entries somewhere.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class Logger implements SingletonInterface
{
    /**
     * Devlog extension configuration
     *
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration = null;

    /**
     * @var array List of instances of each available log writer
     */
    protected $logWriters = array();

    /**
     * @var bool Flag used to turn logging off
     */
    protected $isLoggingEnabled = true;

    /**
     * @var string Unique ID of the current run
     */
    protected $runId;

    /**
     * @var int Counter for entries within the current run
     */
    protected $counter = 0;

    public function __construct()
    {
        // Read the extension configuration
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        // Use microtime as unique ID (in format "sec.msec")
        $microtimeParts = explode(' ', microtime());
        $this->runId = $microtimeParts[1] . $microtimeParts[0];

        // Create a list of instances of each available log writer
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['writers'] as $logWriterClass) {
            try {
                $logWriter = GeneralUtility::makeInstance(
                        $logWriterClass,
                        $this
                );
                if ($logWriter instanceof WriterInterface) {
                    $this->logWriters[] = $logWriter;
                }
            } catch (\Exception $e) {
                // TODO: report somewhere that writer could not be instantiated (sys_log?)
            }
        }
    }

    /**
     * Logs calls passed to \TYPO3\CMS\Core\Utility\GeneralUtility::devLog().
     *
     * $logData = array('msg'=>$msg, 'extKey'=>$extKey, 'severity'=>$severity, 'dataVar'=>$dataVar);
     *        'msg'        string        Message (in english).
     *        'extKey'    string        Extension key (from which extension you are calling the log)
     *        'severity'    integer        Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     *        'dataVar'    array        Additional data you want to pass to the logger.
     *
     * @param array $logData Log data
     * @return void
     */
    public function log($logData)
    {
        // If logging is disabled, abort immediately
        if (!$this->isLoggingEnabled) {
            return;
        }
        // Add IP address for validation
        $logData['ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        // If the log entry doesn't pass the basic filters, exit early doing nothing
        if (!$this->isEntryAccepted($logData)) {
            return;
        }
        // Disable logging while inside the devlog, to avoid recursive calls
        $this->isLoggingEnabled = false;

        // Create an entry and fill it with data
        /** @var Entry $entry */
        $entry = GeneralUtility::makeInstance(Entry::class);
        $entry->setRunId(
                $this->runId
        );
        $entry->setSorting(
                $this->counter
        );
        $this->counter++;
        $entry->setCrdate(time());
        $entry->setMessage(
                GeneralUtility::removeXSS($logData['msg'])
        );
        $entry->setExtkey(
                strip_tags($logData['extKey'])
        );
        $entry->setSeverity(
                (int)$logData['severity']
        );
        $entry->setExtraData($logData['dataVar']);

        // Try to get a page id that makes sense
        $pid = 0;
        // In the FE context, this is obviously the current page
        if (TYPO3_MODE === 'FE') {
            $pid = $GLOBALS['TSFE']->id;

        // In other contexts, a global variable may be set with a relevant pid
        } elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'])) {
            $pid = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'];
        }
        $entry->setPid($pid);

        $entry->setCruserId(
                (isset($GLOBALS['BE_USER']->user['uid'])) ? $GLOBALS['BE_USER']->user['uid'] : 0
        );
        $entry->setIp(
                $logData['ip']
        );

        // Get information about the place where this method was called from
        try {
            $callPlaceInfo = $this->getCallPlaceInfo();
            $entry->setLocation($callPlaceInfo['basename']);
            $entry->setLine($callPlaceInfo['line']);
        } catch (\OutOfBoundsException $e) {
            // Do nothing
        }

        // Loop on all writers to output the log entry to some backend
        /** @var \Devlog\Devlog\Writer\WriterInterface $logWriter */
        foreach ($this->logWriters as $logWriter) {
            $logWriter->write($entry);
        }
        $this->isLoggingEnabled = true;
    }

    /**
     * Checks whether the given log data passes the filters or not.
     *
     * @param array $logData Log information
     * @return bool
     */
    public function isEntryAccepted($logData)
    {
        // Skip entry if severity is below minimum level
        if ($logData['severity'] < $this->extensionConfiguration->getMinimumLogLevel()) {
            return false;
        }
        // Skip entry if key is in excluded list
        if (GeneralUtility::inList($this->extensionConfiguration->getExcludeKeys(), $logData['extKey'])) {
            return false;
        }
        // Skip entry if referrer does not match IP mask
        if (!$this->isIpAddressAccepted($logData['ip'])) {
            return false;
        }
        return true;
    }

    /**
     * Checks if given IP address is acceptable.
     *
     * @param string $ipAddress IP address to check
     * @return bool
     */
    public function isIpAddressAccepted($ipAddress)
    {
        $ipFilter = $this->extensionConfiguration->getIpFilter();
        // Re-use global IP mask if so defined
        if (strtolower($ipFilter) === 'devipmask') {
            $ipFilter = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
        }
        return GeneralUtility::cmpIP($ipAddress, $ipFilter);
    }

    /**
     * Given a backtrace, this method tries to find the place where a "devLog" function was called
     * and returns info about that place.
     *
     * @return    array    information about the call place
     */
    protected function getCallPlaceInfo()
    {
        $backTrace = debug_backtrace();
        foreach ($backTrace as $entry) {
            if ($entry['function'] === 'devLog') {
                $pathInfo = pathinfo($entry['file']);
                $pathInfo['line'] = $entry['line'];
                return $pathInfo;
            }
        }
        throw new \OutOfBoundsException(
                'No devLog() call found withing debug stack.',
                1414338781
        );
    }

    /**
     * Returns the extension's configuration.
     *
     * @return ExtensionConfiguration
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * Sets the extension configuration.
     *
     * This should normally not be used. It is designed for unit testing.
     *
     * @param ExtensionConfiguration $extensionConfiguration
     * @return void
     */
    public function setExtensionConfiguration($extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Returns the logging enabled flag.
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return $this->isLoggingEnabled;
    }

    /**
     * Sets the logging enabled flag.
     *
     * @param bool $flag
     * @return void
     */
    public function setIsLoggingEnabled($flag)
    {
        $this->isLoggingEnabled = (bool)$flag;
    }
}