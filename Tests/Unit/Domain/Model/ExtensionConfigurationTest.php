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

use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case for class \Devlog\Devlog\Domain\Model\ExtensionConfiguration.
 *
 * @author François Suter <typo3@cobweb.ch>
 */
class ExtensionConfigurationTest extends UnitTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * @var ExtensionConfiguration
     */
    protected $subject = null;

    protected function setUp()
    {
        // Override existing configuration for testing
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['devlog'] = serialize(array());
        $this->subject = new ExtensionConfiguration();
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getMinimumLogLevelInitiallyReturnsDefaultValue()
    {
        self::assertSame(
                -1,
                $this->subject->getMinimumLogLevel()
        );
    }

    /**
     * @test
     * @param mixed $input Input value
     * @param int $expected Value cast to integer
     * @dataProvider integerProvider
     */
    public function setMinimumLogLevelSetsLevel($input, $expected)
    {
        $this->subject->setMinimumLogLevel($input);
        self::assertSame(
                $expected,
                $this->subject->getMinimumLogLevel()
        );
    }

    /**
     * @test
     */
    public function getExcludeKeysInitiallyReturnsEmptryString()
    {
        self::assertSame(
                '',
                $this->subject->getExcludeKeys()
        );
    }

    /**
     * @test
     */
    public function setExcludeKeysSetsKeys()
    {
        $keys = 'core,extbase';
        $this->subject->setExcludeKeys($keys);
        self::assertSame(
                $keys,
                $this->subject->getExcludeKeys()
        );
    }

    /**
     * @test
     */
    public function getIncludeKeysInitiallyReturnsEmptryString()
    {
        self::assertSame(
                '',
                $this->subject->getIncludeKeys()
        );
    }

    /**
     * @test
     */
    public function setIncludeKeysSetsKeys()
    {
        $keys = 'core,extbase';
        $this->subject->setIncludeKeys($keys);
        self::assertSame(
                $keys,
                $this->subject->getIncludeKeys()
        );
    }

    /**
     * @test
     */
    public function getIpFilterInitiallyReturnsEmptyString()
    {
        self::assertSame(
                '',
                $this->subject->getIpFilter()
        );
    }

    /**
     * @test
     */
    public function setIpFilterSetsFilter()
    {
        $filter = '::1,127.0.0.1';
        $this->subject->setIpFilter($filter);
        self::assertSame(
                $filter,
                $this->subject->getIpFilter()
        );
    }

    /**
     * @test
     */
    public function getRefreshFrequencyInitiallyReturnsDefaultValue()
    {
        self::assertSame(
                2,
                $this->subject->getRefreshFrequency()
        );
    }

    /**
     * @test
     * @param mixed $input Input value
     * @param int $expected Value cast to integer
     * @dataProvider integerProvider
     */
    public function setRefreshFrequencySetFrequency($input, $expected)
    {
        $this->subject->setRefreshFrequency($input);
        self::assertSame(
                $expected,
                $this->subject->getRefreshFrequency()
        );
    }

    /**
     * @test
     */
    public function getEntriesPerPageInitiallyReturnsDefaultValue()
    {
        self::assertSame(
                25,
                $this->subject->getEntriesPerPage()
        );
    }

    /**
     * @test
     * @param mixed $input Input value
     * @param int $expected Value cast to integer
     * @dataProvider integerProvider
     */
    public function setEntriesPerPageSetsEntries($input, $expected)
    {
        $this->subject->setEntriesPerPage($input);
        self::assertSame(
                $expected,
                $this->subject->getEntriesPerPage()
        );
    }

    /**
     * @test
     */
    public function getMaximumRowsInitiallyReturnsDefaultValue() {
        self::assertSame(
                1000,
                $this->subject->getMaximumRows()
        );
    }

    /**
     * @test
     * @param mixed $input Input value
     * @param int $expected Value cast to integer
     * @dataProvider integerProvider
     */
    public function setMaximumRowsSetsMaximum($input, $expected)
    {
        $this->subject->setMaximumRows($input);
        self::assertSame(
                $expected,
                $this->subject->getMaximumRows()
        );
    }

    /**
     * @test
     */
    public function getOptimizeTableInitiallyReturnsTrue() {
        self::assertTrue(
                $this->subject->getOptimizeTable()
        );
    }

    /**
     * @test
     * @param $input
     * @param $expected
     * @dataProvider booleanProvider
     */
    public function setOptimizeTableSetsOptimizeFlag($input, $expected)
    {
        $this->subject->setOptimizeTable($input);
        self::assertSame(
                $expected,
                $this->subject->getOptimizeTable()
        );
    }

    /**
     * @test
     */
    public function getMaximumExtraDataSizeInitiallyReturnsDefaultValue() {
        self::assertSame(
                1000000,
                $this->subject->getMaximumExtraDataSize()
        );
    }

    /**
     * @test
     * @param mixed $input Input value
     * @param int $expected Value cast to integer
     * @dataProvider integerProvider
     */
    public function setMaximumExtraDataSizeSetsMaximum($input, $expected)
    {
        $this->subject->setMaximumExtraDataSize($input);
        self::assertSame(
                $expected,
                $this->subject->getMaximumExtraDataSize()
        );
    }

    /**
     * @test
     */
    public function getLogFilePathInitiallyReturnsEmptyString() {
        self::assertSame(
                '',
                $this->subject->getLogFilePath()
        );
    }

    /**
     * @test
     */
    public function setLogFilePathSetsPath() {
        $path = 'typo3temp/foo.txt';
        $this->subject->setLogFilePath($path);
        self::assertSame(
                $path,
                $this->subject->getLogFilePath()
        );
    }

    /**
     * Provides integer or pseudo-integer values for testing
     * integer setters.
     *
     * @return array
     */
    public function integerProvider()
    {
        return array(
            'true integer' => array(
                10,
                10
            ),
            'integer string' => array(
                '12',
                12
            ),
            'integer and more string' => array(
                '9 yards',
                9
            ),
            'arbitrary string' => array(
                'foo bar',
                0
            ),
            'boolean true' => array(
                true,
                1
            ),
            'boolean false' => array(
                false,
                0
            )
        );
    }

    /**
     * Provides boolean or pseudo-boolean values for testing
     * boolean setters.
     *
     * @return array
     */
    public function booleanProvider()
    {
        return array(
            'boolean' => array(
                true,
                true
            ),
            'integer 0' => array(
                0,
                false
            ),
            'any integer' => array(
                23,
                true
            ),
            'true string' => array(
                'true',
                true
            ),
            'false string' => array(
                'false',
                true
            ),
            'empty string' => array(
                '',
                false
            ),
            'null' => array(
                '',
                false
            )
        );
    }
}
