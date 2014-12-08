<?php

namespace Devlog\Devlog\Tests\Unit\Utility;

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

/**
 * Test case for class \Devlog\Devlog\Logger.
 *
 * @author François Suter <typo3@cobweb.ch>
 */
class LoggerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var Logger
	 */
	protected $subject = NULL;

	/**
	 * @var array Test extension configuration
	 */
	protected $testConfiguration = array(
		'minimumLogLevel' => 1,
		'excludeKeys' => 'foo,bar'
	);

	protected function setUp() {
		$this->subject = new Logger();
		$this->subject->setExtensionConfiguration(
			$this->testConfiguration
		);
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 * @covers \Devlog\Devlog\Utility\Logger::isEntryAccepted
	 */
	public function entryIsAccepted() {
		$this->assertTrue(
			$this->subject->isEntryAccepted(
				array(
					'severity' => 2,
					'extKey' => 'whatever'
				)
			)
		);
	}

	public function wrongEntriesProvider() {
		return array(
			'Severity too low' => array(
				array(
					'severity' => 0,
					'extKey' => 'whatever'
				)
			),
			'Excluded extension key' => array(
				array(
					'severity' => 3,
					'extKey' => 'foo'
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider wrongEntriesProvider
	 * @covers \Devlog\Devlog\Utility\Logger::isEntryAccepted
	 */
	public function entryIsRefused($entry) {
		$this->assertFalse(
			$this->subject->isEntryAccepted(
				$entry
			)
		);
	}

	/**
	 * @test
	 * @covers \Devlog\Devlog\Utility\Logger::getExtensionConfiguration
	 * @covers \Devlog\Devlog\Utility\Logger::setExtensionConfiguration
	 */
	public function getConfigurationReturnsTestValue() {
		$this->assertSame(
			$this->subject->getExtensionConfiguration(),
			$this->testConfiguration
		);
	}


	/**
	 * @test
	 * @covers \Devlog\Devlog\Utility\Logger::isLoggingEnabled
	 */
	public function getIsLoggingEnabledReturnsInitialValueForBoolean() {
		$this->assertTrue(
			$this->subject->isLoggingEnabled()
		);
	}

	/**
	 * @test
	 * @covers \Devlog\Devlog\Utility\Logger::setIsLoggingEnabled
	 */
	public function setIsLoggingEnabledForBooleanSetsIsLoggingEnabled() {
		$this->subject->setIsLoggingEnabled(FALSE);

		$this->assertAttributeEquals(
			FALSE,
			'isLoggingEnabled',
			$this->subject
		);
	}
}
