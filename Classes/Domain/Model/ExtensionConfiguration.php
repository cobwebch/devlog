<?php

namespace Devlog\Devlog\Domain\Model;

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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Object containing the extension configuration.
 *
 * NOTE: this is not a true Extbase object.
 *
 * @author Stefan Froemken <froemken@gmail.com>
 */
class ExtensionConfiguration implements SingletonInterface
{
    /**
     * Raw configuration
     *
     * @var array
     */
    protected $configurationArray = array();

    /**
     * Minimum log level
     *
     * @var int
     */
    protected $minimumLogLevel = -1;

    /**
     * Exclude Keys
     *
     * @var string
     */
    protected $excludeKeys = '';

    /**
     * Ip Filter
     *
     * @var string
     */
    protected $ipFilter = '';

    /**
     * Refresh Frequency
     *
     * @var int
     */
    protected $refreshFrequency = 2;

    /**
     * Entries per Page
     *
     * @var int
     */
    protected $entriesPerPage = 25;

    /**
     * Maximum Rows
     *
     * @var int
     */
    protected $maximumRows = 1000;

    /**
     * optimizeTable
     *
     * @var bool
     */
    protected $optimizeTable = true;

    /**
     * Maximum extra data size
     *
     * @var int
     */
    protected $maximumExtraDataSize = 1000000;

    /**
     * Log file path
     *
     * @var string
     */
    protected $logFilePath = '';

    /**
     * Constructor.
     *
     * Reads the global configuration and calls the setter methods.
     */
    public function __construct()
    {
        // Get global configuration
        $this->configurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['devlog']);
        if (is_array($this->configurationArray)) {
            // Call setter method foreach configuration entry
            foreach ($this->configurationArray as $key => $value) {
                $methodName = 'set' . ucfirst($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($value);
                }
            }
        }
    }

    /**
     * Returns the extension configuration as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->configurationArray;
    }

    /**
     * Returns the minimumLogLevel.
     *
     * @return int $minimumLogLevel
     */
    public function getMinimumLogLevel()
    {
        return $this->minimumLogLevel;
    }

    /**
     * Sets the minimumLogLevel.
     *
     * @param int $minimumLogLevel
     * @return void
     */
    public function setMinimumLogLevel($minimumLogLevel)
    {
        $this->minimumLogLevel = (int)$minimumLogLevel;
    }

    /**
     * Returns the excludeKeys.
     *
     * @return string $excludeKeys
     */
    public function getExcludeKeys()
    {
        return $this->excludeKeys;
    }

    /**
     * Sets the excludeKeys.
     *
     * @param string $excludeKeys
     * @return void
     */
    public function setExcludeKeys($excludeKeys)
    {
        $this->excludeKeys = (string)$excludeKeys;
    }

    /**
     * Returns the ipFilter.
     *
     * @return string $ipFilter
     */
    public function getIpFilter()
    {
        return $this->ipFilter;
    }

    /**
     * Sets the ipFilter.
     *
     * @param string $ipFilter
     * @return void
     */
    public function setIpFilter($ipFilter)
    {
        $this->ipFilter = (string)$ipFilter;
    }

    /**
     * Returns the refreshFrequency.
     *
     * @return int $refreshFrequency
     */
    public function getRefreshFrequency()
    {
        return $this->refreshFrequency;
    }

    /**
     * Sets the refreshFrequency.
     *
     * @param int $refreshFrequency
     * @return void
     */
    public function setRefreshFrequency($refreshFrequency)
    {
        $this->refreshFrequency = (int)$refreshFrequency;
    }

    /**
     * Returns the entriesPerPage.
     *
     * @return int $entriesPerPage
     */
    public function getEntriesPerPage()
    {
        return $this->entriesPerPage;
    }

    /**
     * Sets the entriesPerPage.
     *
     * @param int $entriesPerPage
     * @return void
     */
    public function setEntriesPerPage($entriesPerPage)
    {
        $this->entriesPerPage = (int)$entriesPerPage;
    }

    /**
     * Returns the maximumRows.
     *
     * @return int $maximumRows
     */
    public function getMaximumRows()
    {
        return $this->maximumRows;
    }

    /**
     * Sets the maximumRows.
     *
     * @param int $maximumRows
     * @return void
     */
    public function setMaximumRows($maximumRows)
    {
        $this->maximumRows = (int)$maximumRows;
    }

    /**
     * Returns the optimizeTable.
     *
     * @return bool $optimizeTable
     */
    public function getOptimizeTable()
    {
        return $this->optimizeTable;
    }

    /**
     * Sets the optimizeTable.
     *
     * @param bool $optimizeTable
     * @return void
     */
    public function setOptimizeTable($optimizeTable)
    {
        $this->optimizeTable = (bool)$optimizeTable;
    }

    /**
     * Returns the maximumExtraDataSize.
     *
     * @return int $maximumExtraDataSize
     */
    public function getMaximumExtraDataSize()
    {
        return $this->maximumExtraDataSize;
    }

    /**
     * Sets the maximumExtraDataSize.
     *
     * @param int $maximumExtraDataSize
     * @return void
     */
    public function setMaximumExtraDataSize($maximumExtraDataSize)
    {
        $this->maximumExtraDataSize = (int)$maximumExtraDataSize;
    }

    /**
     * Returns the logFilePath.
     *
     * @return string $logFilePath
     *
     * @throws \UnexpectedValueException
     */
    public function getLogFilePath()
    {
        return $this->logFilePath;
    }

    /**
     * Sets the logFilePath.
     *
     * @param string $logFilePath
     * @return void
     */
    public function setLogFilePath($logFilePath)
    {
        $this->logFilePath = (string)$logFilePath;
    }
}
