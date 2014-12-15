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
		'excludeKeys' => 'foo,bar',
		'ipFilter' => '127.0.0.1,::1'
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
					'extKey' => 'whatever',
					'ip' => '127.0.0.1'
				)
			)
		);
	}

	public function validEntriesProvider() {
		return array(
			'Severity too low' => array(
				array(
					'severity' => 0,
					'extKey' => 'whatever',
					'ip' => '127.0.0.1'
				)
			),
			'Excluded extension key' => array(
				array(
					'severity' => 3,
					'extKey' => 'foo',
					'ip' => '127.0.0.1'
				)
			),
			'IP does not match' => array(
				array(
					'severity' => 3,
					'extKey' => 'whatever',
					'ip' => '192.168.1.1'
				)
			)
		);
	}

	/**
	 * @param array $entry Log entry data
	 * @test
	 * @dataProvider validEntriesProvider
	 * @covers \Devlog\Devlog\Utility\Logger::isEntryAccepted
	 */
	public function entryIsRefused($entry) {
		$this->assertFalse(
			$this->subject->isEntryAccepted(
				$entry
			)
		);
	}

	public function ipAddressesProvider() {
		return array(
			'Valid IP v4' => array(
				'127.0.0.1',
				'',
				'',
				TRUE
			),
			'Valid IP v6' => array(
				'::1',
				'',
				'',
				TRUE
			),
			'Valid with devIPMask' => array(
				'192.168.1.67',
				'devIPMask',
				'192.168.1.*',
				TRUE
			),
			'IP v4' => array(
				'192.168.1.1',
				'',
				'',
				FALSE
			),
			'IP v6' => array(
				'2001:db8::ff00:42:8329',
				'',
				'',
				FALSE
			),
			'devIPMask' => array(
				'80.58.212.14',
				'devIPMask',
				'192.168.1.*',
				FALSE
			)
		);
	}

	/**
	 * @param string $testValue IP address to test
	 * @param string $configurationOverride Override IP filter in extension configuration
	 * @param string $devIpMask Value for overriding $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
	 * @param boolean $result TRUE or FALSE, depending on IP address validity
	 * @test
	 * @dataProvider ipAddressesProvider
	 * @covers \Devlog\Devlog\Utility\Logger::isIpAddressAccepted
	 */
	public function isIpAddressValid($testValue, $configurationOverride, $devIpMask, $result) {
		$savedIpMask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
		// Override devIPmask
		if (!empty($devIpMask)) {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIpMask;
		}
		// Override extension configuration
		if (!empty($configurationOverride)) {
			$specialConfiguration = $this->testConfiguration;
			$specialConfiguration['ipFilter'] = $configurationOverride;
			$this->subject->setExtensionConfiguration(
				$specialConfiguration
			);
		}
		// Perform the actual test
		$this->assertSame(
			$result,
			$this->subject->isIpAddressAccepted(
				$testValue
			)
		);
		// Restore extension configuration
		$this->subject->setExtensionConfiguration(
			$this->testConfiguration
		);
		// Restore devIPmask
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $savedIpMask;
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
