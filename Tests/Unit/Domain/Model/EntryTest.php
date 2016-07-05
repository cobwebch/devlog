<?php

namespace Devlog\Devlog\Tests\Unit\Domain\Model;

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

/**
 * Test case for class \Devlog\Devlog\Domain\Model\Entry.
 *
 * @author François Suter <typo3@cobweb.ch>
 */
class EntryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * @var Entry
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new Entry();
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getRunId
     */
    public function getRunIdReturnsInitialValueForString()
    {
        self::assertSame(
                '',
                $this->subject->getRunId()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setRunId
     */
    public function setRunIdForStringSetsExtkey()
    {
        $this->subject->setRunId('12345678987.65432100');

        self::assertAttributeEquals(
                '12345678987.65432100',
                'runId',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getSorting
     */
    public function getSortingReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getSorting()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setSorting
     */
    public function setSortingForIntegerSetsSorting()
    {
        $this->subject->setSorting(12);

        self::assertAttributeEquals(
                12,
                'sorting',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getCrdate
     */
    public function getCrdateReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getCrdate()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setCrdate
     */
    public function setCrdateForIntegerSetsSeverity()
    {
        $this->subject->setCrdate(1417705263);

        self::assertAttributeEquals(
                1417705263,
                'crdate',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getSeverity
     */
    public function getSeverityReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getSeverity()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setSeverity
     */
    public function setSeverityForIntegerSetsSeverity()
    {
        $this->subject->setSeverity(2);

        self::assertAttributeEquals(
                2,
                'severity',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getExtkey
     */
    public function getExtkeyReturnsInitialValueForString()
    {
        self::assertSame(
                '',
                $this->subject->getExtkey()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setExtkey
     */
    public function setExtkeyForStringSetsExtkey()
    {
        $this->subject->setExtkey('devlog');

        self::assertAttributeEquals(
                'devlog',
                'extkey',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getMessage
     */
    public function getMessageReturnsInitialValueForString()
    {
        self::assertSame(
                '',
                $this->subject->getMessage()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setMessage
     */
    public function setMessageForStringSetsMessage()
    {
        $this->subject->setMessage('This is a message');

        self::assertAttributeEquals(
                'This is a message',
                'message',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getLocation
     */
    public function getLocationReturnsInitialValueForString()
    {
        self::assertSame(
                '',
                $this->subject->getLocation()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setLocation
     */
    public function setLocationForStringSetsLocation()
    {
        $this->subject->setLocation('Entry.php');

        self::assertAttributeEquals(
                'Entry.php',
                'location',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getLine
     */
    public function getLineReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getLine()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setLine
     */
    public function setLineForIntegerSetsLine()
    {
        $this->subject->setLine(42);

        self::assertAttributeEquals(
                42,
                'line',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getIp
     */
    public function getIpReturnsInitialValueForString()
    {
        self::assertSame(
                '',
                $this->subject->getIp()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setIp
     */
    public function setIpForStringSetsIp()
    {
        $this->subject->setIp('127.0.0.1');

        self::assertAttributeEquals(
                '127.0.0.1',
                'ip',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getCruserId
     */
    public function getCruserIdReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getCruserId()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setCruserId
     */
    public function setCruserIdForIntegerSetsSeverity()
    {
        $this->subject->setCruserId(5);

        self::assertAttributeEquals(
                5,
                'cruserId',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getPid
     */
    public function getPidReturnsInitialValueForInteger()
    {
        self::assertSame(
                0,
                $this->subject->getPid()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setPid
     */
    public function setPidForIntegerSetsSeverity()
    {
        $this->subject->setPid(17);

        self::assertAttributeEquals(
                17,
                'pid',
                $this->subject
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::getExtraData
     */
    public function getExtraDataReturnsInitialValueForString()
    {
        self::assertNull(
                $this->subject->getExtraData()
        );
    }

    /**
     * @test
     * @covers \Devlog\Devlog\Domain\Model\Entry::setExtraData
     */
    public function setExtraDataForStringSetsExtraData()
    {
        $this->subject->setExtraData('SomeBlob');

        self::assertAttributeEquals(
                'SomeBlob',
                'extraData',
                $this->subject
        );
    }
}
