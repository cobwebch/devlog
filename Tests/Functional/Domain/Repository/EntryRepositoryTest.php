<?php
namespace Devlog\Devlog\Tests\Functional\Domain\Repository;

/*
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

use Devlog\Devlog\Domain\Repository\EntryRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Functional tests for Entry repository.
 *
 * @package Devlog\Devlog\Tests\Functional\Domain\Repository
 */
class EntryRepositoryTest extends \Tx_Phpunit_Database_TestCase
{
    /**
     * @var EntryRepository
     */
    protected $subject = null;

    protected function setUp()
    {
        if (!$this->createDatabase()) {
            self::markTestSkipped('Test database could not be created.');
        }
        $this->importExtensions(['devlog']);

        $objectManager = new ObjectManager();
        $this->subject = $objectManager->get(EntryRepository::class);
    }

    /**
     * @test
     */
    public function findAllReturnsNothingForEmptyDatabase()
    {
        $records = $this->subject->findAll();
        self::assertCount(
                0,
                $records
        );
    }

    /**
     * @test
     */
    public function findAllReturnsAllRecords()
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        $records = $this->subject->findAll();
        self::assertCount(
                2,
                $records
        );
    }

    /**
     * @test
     */
    public function findAfterDateReturnsOnlyNewRecords()
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        // Find entries after July 15, 2016
        $records = $this->subject->findAfterDate(1468579836);
        self::assertCount(
                1,
                $records
        );
    }
}