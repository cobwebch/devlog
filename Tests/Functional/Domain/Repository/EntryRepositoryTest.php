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

    protected function tearDown()
    {
        $this->dropDatabase();
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
                3,
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

    /**
     * @test
     */
    public function findByKeyReturnsRelatedRecords() {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        // Find entries for key "foo"
        $records = $this->subject->findByKey('foo');
        self::assertCount(
                2,
                $records
        );
    }

    /**
     * @test
     */
    public function countByKeyReturnsEmptyArrayForEmptyDatabase()
    {
        $records = $this->subject->countByKey();
        self::assertCount(
                0,
                $records
        );
    }

    /**
     * @test
     */
    public function countByKeyReturnsKeyCount()
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        $records = $this->subject->countByKey();
        self::assertSame(
                array(
                        'bar' => 1,
                        'foo' => 2
                ),
                $records
        );
    }

    /**
     * @test
     */
    public function countByPeriodReturnsEmptyArrayForEmptyDatabase()
    {
        $records = $this->subject->countByPeriod();
        self::assertSame(
                array(
                        '1hour' => 0,
                        '1day' => 0,
                        '1week' => 0,
                        '1month' => 0,
                        '3months' => 0,
                        '6months' => 0,
                        '1year' => 0
                ),
                $records
        );
    }

    /**
     * @test
     */
    public function countByPeriodReturnsPeriodCount()
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');
        $this->adjustEntriesAge();

        $records = $this->subject->countByPeriod();
        self::assertSame(
                array(
                        '1hour' => 3,
                        '1day' => 2,
                        '1week' => 2,
                        '1month' => 1,
                        '3months' => 1,
                        '6months' => 0,
                        '1year' => 0
                ),
                $records
        );
    }

    /**
     * @test
     */
    public function deleteAllDeletesAllEntries() {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        $deleted = $this->subject->deleteAll();
        $records = $this->subject->findAll();
        // Asset that the expected number of records were deleted and that there are none left in the database
        self::assertSame(
                3,
                $deleted
        );
        self::assertCount(
                0,
                $records
        );
    }

    /**
     * @test
     */
    public function deleteByKeyDeletesEntriesForKey() {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');

        $deleted = $this->subject->deleteByKey('foo');
        $records = $this->subject->findByKey('foo');
        // Asset that the expected number of records were deleted and that there are none left with this key in the database
        self::assertSame(
                2,
                $deleted
        );
        self::assertCount(
                0,
                $records
        );
    }

    /**
     * Provides various periods for deleting by period.
     *
     * @return array
     */
    public function periodProvider()
    {
        return array(
                'Nothing deleted for 1 year age' => array(
                        EntryRepository::PERIOD_1YEAR,
                        0,
                        array(
                                '1hour' => 3,
                                '1day' => 2,
                                '1week' => 2,
                                '1month' => 1,
                                '3months' => 1,
                                '6months' => 0,
                                '1year' => 0
                        ),
                ),
                'Two records deleted for 1 week age' => array(
                        EntryRepository::PERIOD_1WEEK,
                        2,
                        array(
                                '1hour' => 1,
                                '1day' => 0,
                                '1week' => 0,
                                '1month' => 0,
                                '3months' => 0,
                                '6months' => 0,
                                '1year' => 0
                        ),
                ),
                'One record deleted for 1 month age' => array(
                        EntryRepository::PERIOD_1MONTH,
                        1,
                        array(
                                '1hour' => 2,
                                '1day' => 1,
                                '1week' => 1,
                                '1month' => 0,
                                '3months' => 0,
                                '6months' => 0,
                                '1year' => 0
                        ),
                ),
                'All records deleted for 1 hour age' => array(
                        EntryRepository::PERIOD_1HOUR,
                        3,
                        array(
                                '1hour' => 0,
                                '1day' => 0,
                                '1week' => 0,
                                '1month' => 0,
                                '3months' => 0,
                                '6months' => 0,
                                '1year' => 0
                        ),
                ),
        );
    }

    /**
     * @param int $period Age for records to delete
     * @param int $deleted Expected number of deleted records
     * @param array $count Expected record count after deletion
     * @test
     * @dataProvider periodProvider
     */
    public function deleteByPeriodDeletesEntriesForPeriod($period, $deleted, $count)
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DevlogEntries.xml');
        $this->adjustEntriesAge();

        $deletedRecords = $this->subject->deleteByPeriod($period);
        $records = $this->subject->countByPeriod();
        // Asset that the expected number of records were deleted and that the count by period is as expected
        self::assertSame(
                $deleted,
                $deletedRecords
        );
        self::assertSame(
                $count,
                $records
        );
    }

    /**
     * Adjusts age of fixture log entries.
     *
     * For the tests acting on periods, we can't work with the fixtures as is
     * because they grow older over time. Hence we dynamically change
     * the "crdate" values in the test database to have records with known
     * age intervals.
     *
     * @return void
     */
    protected function adjustEntriesAge()
    {
        $db = \Tx_Phpunit_Service_Database::getDatabaseConnection();
        // Make sure the first entry is at least 3 months old (but not more than 6)
        $db->exec_UPDATEquery(
                'tx_devlog_domain_model_entry',
                'uid = 1',
                array(
                        'crdate' => time() - EntryRepository::PERIOD_3MONTHS - 3600
                )
        );
        // Make sure the second record is at least 1 day old (but not much older)
        $db->exec_UPDATEquery(
                'tx_devlog_domain_model_entry',
                'uid = 2',
                array(
                        'crdate' => time() - EntryRepository::PERIOD_1WEEK - 3600
                )
        );
        // Make sure the third record is at least 1 hour old (but not much older)
        $db->exec_UPDATEquery(
                'tx_devlog_domain_model_entry',
                'uid = 3',
                array(
                        'crdate' => time() - EntryRepository::PERIOD_1HOUR - 10
                )
        );
    }
}