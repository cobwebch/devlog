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

use Devlog\Devlog\Domain\Model\Entry;
use Devlog\Devlog\Domain\Repository\EntryRepository;
use Devlog\Devlog\Utility\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes log entries to a database table.
 */
class DatabaseWriter extends AbstractWriter
{
    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * DatabaseWriter constructor.
     *
     * @param Logger $logger
     * @throws \UnexpectedValueException
     */
    public function __construct($logger)
    {
        parent::__construct($logger);
        try {
            $this->entryRepository = GeneralUtility::makeInstance(EntryRepository::class);
            $this->entryRepository->setExtensionConfiguration(
                    $this->logger->getExtensionConfiguration()
            );
        }
        catch (\Exception $e) {
            throw new \UnexpectedValueException(
                    sprintf(
                            'Database writer is not available (Error: %s, Code: %s)',
                            $e->getMessage(),
                            $e->getCode()
                    ),
                    1518984907
            );
        }
    }

    /**
     * Writes the entry to the DB storage.
     *
     * @param Entry $entry
     * @return void
     */
    public function write($entry)
    {
        $this->entryRepository->add($entry);
        $this->entryRepository->cleanUp();
    }
}