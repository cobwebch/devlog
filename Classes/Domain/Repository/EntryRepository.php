<?php
namespace Devlog\Devlog\Domain\Repository;

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

use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * The repository for DevLog Entries.
 *
 * NOTE: this is not an Extbase repository as we don't need all the
 * sophistication here. We might as well avoid the overhead.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class EntryRepository implements SingletonInterface
{
    /**
     * @var string Name of the database table used for logging
     */
    protected $databaseTable = 'tx_devlog_domain_model_entry';

    /**
     * @var ExtensionConfiguration Extension configuration
     */
    protected $extensionConfiguration = null;

    /**
     * @var integer Number of rows in the database (cached to avoid querying too often)
     */
    protected $numberOfRows = null;

    /**
     * Returns all available records in the log table.
     *
     * By default records are sorted by descending creation date and
     * ascending order.
     *
     * @return array|null
     */
    public function findAll()
    {
        try {
            $entries = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    '*',
                    $this->databaseTable,
                    '',
                    '',
                    'crdate DESC, sorting ASC'
            );
        } catch (\Exception $e) {
            $entries = array();
        }
        $entries = $this->expandEntryData($entries);
        return $entries;
    }

    /**
     * Finds all entries at or after the given timestamp.
     *
     * @param int $timestamp Limit date/time for fetching entries
     * @return array
     */
    public function findAfterDate($timestamp)
    {
        try {
            $entries = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    '*',
                    $this->databaseTable,
                    'crdate >= ' . (int)$timestamp,
                    '',
                    'crdate DESC, sorting ASC'
            );
        } catch (\Exception $e) {
            $entries = array();
        }
        $entries = $this->expandEntryData($entries);
        return $entries;
    }

    /**
     * Adds a log entry to the database table.
     *
     * @param \Devlog\Devlog\Domain\Model\Entry $entry
     * @return boolean
     */
    public function add($entry)
    {
        $fields = array(
                'run_id' => $entry->getRunId(),
                'sorting' => $entry->getSorting(),
                'severity' => $entry->getSeverity(),
                'extkey' => $entry->getExtkey(),
                'message' => $entry->getMessage(),
                'location' => $entry->getLocation(),
                'line' => $entry->getLine(),
                'ip' => $entry->getIp(),
                'cruser_id' => $entry->getCruserId(),
                'crdate' => $entry->getCrdate(),
                'pid' => $entry->getPid(),
        );
        // Handle extra data
        $extraData = $entry->getExtraData();
        // NOTE: GeneralUtility::devLog() sends "false" if extra data is undefined
        if ($extraData) {
            $fields['extra_data'] = gzcompress(serialize($extraData));
            $extraDataSize = strlen($fields['extra_data']);
            $maximumExtraDataSize = $this->extensionConfiguration->getMaximumExtraDataSize();
            // If the entry's extra data is above the limit, replace it with a warning
            if (!empty($maximumExtraDataSize) && $extraDataSize > $maximumExtraDataSize) {
                $fields['extra_data'] = gzcompress(serialize('Extra data too large, not saved.'));
            }
        } else {
            $fields['extra_data'] = '';
        }

        return $this->getDatabaseConnection()->exec_INSERTquery(
                $this->databaseTable,
                $fields
        );
    }

    /**
     * Enforces the limits set in the extension configuration to avoid that the DB tables gets out of hand.
     *
     * @return void
     */
    public function cleanUp()
    {
        // Get the total number of rows, if not already defined
        if ($this->numberOfRows === null) {
            $this->numberOfRows = $this->getDatabaseConnection()->exec_SELECTcountRows(
                    'uid',
                    'tx_devlog_domain_model_entry'
            );
        }
        // Check if number of rows is above the limit and clean up if necessary
        if ($this->numberOfRows > $this->extensionConfiguration->getMaximumRows()) {
            // Select the row from which to start cleaning up
            // To achieve this, order by creation date (so oldest comes first)
            // then offset by 10% of maximumRows and get the next record
            // This will return a timestamp that is used as a cut-off date
            $numberOfRowsToRemove = round(0.1 * $this->extensionConfiguration->getMaximumRows());
            $cutOffRow = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    'crdate',
                    'tx_devlog_domain_model_entry',
                    '',
                    '',
                    'crdate',
                    $numberOfRowsToRemove . ',1'
            );
            $cutOffDate = $cutOffRow[0]['crdate'];
            // Delete all rows older or same age as previously found timestamp
            // This will probably delete a bit more than 10% of maximumRows, but will at least
            // delete complete log runs
            $this->getDatabaseConnection()->exec_DELETEquery(
                    'tx_devlog_domain_model_entry',
                    'crdate <= \'' . $cutOffDate . '\''
            );
            $numberOfRemovedRows = $this->getDatabaseConnection()->sql_affected_rows();
            // Update (cached) number of rows
            $this->numberOfRows -= $numberOfRemovedRows;
            // Optimize the table (if option is active)
            if ($this->extensionConfiguration->getOptimizeTable()) {
                $this->getDatabaseConnection()->sql_query('OPTIMIZE table tx_devlog_domain_model_entry');
            }
        }

    }

    /**
     * Collects additional data or transforms data from entries for simpler handling during display.
     *
     * @param array $entries
     * @return array
     */
    protected function expandEntryData(array $entries)
    {
        $pageInformationCache = array();
        $numEntries = count($entries);
        if ($numEntries > 0) {
            $users = $this->findAllUsers();
            for ($i = 0; $i < $numEntries; $i++) {
                // Grab username instead of id
                $userId = (int)$entries[$i]['cruser_id'];
                if ($userId > 0 && isset($users[$userId])) {
                    $entries[$i]['username'] = $users[$userId]['username'];
                } else {
                    $entries[$i]['username'] = '';
                }
                // Grab page title
                $pid = (int)$entries[$i]['pid'];
                if ($pid > 0 && isset($pageInformationCache[$pid])) {
                    $entries[$i]['page'] = $pageInformationCache[$pid];
                } else {
                    $pageTitle = '';
                    $pageRecord = BackendUtility::getRecord(
                            'pages',
                            $pid
                    );
                    if (is_array($pageRecord)) {
                        $title = BackendUtility::getRecordTitle(
                                'pages',
                                $pageRecord
                        );
                        $pageTitle = $title . ' [' . $pid . ']';
                    }
                    $entries[$i]['page'] = $pageTitle;
                    $pageInformationCache[$pid] = $pageTitle;
                }
                // Process extra data (uncompress and dump)
                if ($entries[$i]['extra_data'] === '') {
                    $extraData = '';
                } else {
                    $extraData = gzuncompress($entries[$i]['extra_data']);
                    $extraData = var_export(unserialize($extraData), true);
                }
                $entries[$i]['extra_data'] = $extraData;
            }
            unset($pageInformationCache);
        }
        return $entries;
    }

    /**
     * Fetches the list of all BE users.
     *
     * @return array
     */
    protected function findAllUsers()
    {
        try {
            $users = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    'uid, username',
                    'be_users',
                    '',
                    '',
                    '',
                    '',
                    'uid'
            );
        } catch (\Exception $e) {
            $users = array();
        }
        return $users;
    }

    /**
     * Sets the extension configuration.
     *
     * Used to pass the "devlog" configuration down to the entry repository.
     *
     * @param ExtensionConfiguration $configuration
     */
    public function setExtensionConfiguration($configuration)
    {
        $this->extensionConfiguration = $configuration;
    }

    /**
     * Wrapper around the global database object.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}