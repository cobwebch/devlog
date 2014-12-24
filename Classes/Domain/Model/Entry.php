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
/**
 * Entry model class.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class Entry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Unique ID of the run this entry belongs to.
	 *
	 * @var string
	 */
	protected $runId = '';

	/**
	 * Entry order within the log run.
	 *
	 * @var int
	 */
	protected $sorting = 0;

	/**
	 * Timestamp of the log run.
	 *
	 * @var integer
	 */
	protected $crdate = 0;

	/**
	 * Severity.
	 *
	 * @var integer
	 */
	protected $severity = 0;

	/**
	 * Extension key or some other identification key.
	 *
	 * @var string
	 */
	protected $extkey = '';

	/**
	 * Message.
	 *
	 * @var string
	 */
	protected $message = '';

	/**
	 * Call location (file).
	 *
	 * @var string
	 */
	protected $location = '';

	/**
	 * Call line.
	 *
	 * @var integer
	 */
	protected $line = 0;

	/**
	 * Referring IP address.
	 *
	 * @var string
	 */
	protected $ip = '';

	/**
	 * Logged in BE user at the creation of the log entry (if any).
	 *
	 * @var int
	 */
	protected $cruserId = 0;

	/**
	 * Page where the data was logged (if applicable).
	 *
	 * @var int
	 */
	protected $pid = 0;

	/**
	 * extraData
	 *
	 * @var mixed
	 */
	protected $extraData = NULL;

	/**
	 * Gets the run ID.
	 *
	 * @return string
	 */
	public function getRunId() {
		return $this->runId;
	}

	/**
	 * Sets the run ID.
	 *
	 * @param string $runId
	 */
	public function setRunId($runId) {
		$this->runId = $runId;
	}

	/**
	 * Returns the sorting.
	 *
	 * @return int
	 */
	public function getSorting() {
		return $this->sorting;
	}

	/**
	 * Sets the sorting.
	 *
	 * @param int $sorting
	 */
	public function setSorting($sorting) {
		$this->sorting = $sorting;
	}

	/**
	 * Returns the creation date.
	 *
	 * @return int
	 */
	public function getCrdate() {
		return $this->crdate;
	}

	/**
	 * Sets the creation date.
	 *
	 * @param int $crDate
	 */
	public function setCrdate($crDate) {
		$this->crdate = $crDate;
	}

	/**
	 * Returns the severity
	 *
	 * @return integer $severity
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * Sets the severity
	 *
	 * @param integer $severity
	 * @return void
	 */
	public function setSeverity($severity) {
		$this->severity = $severity;
	}

	/**
	 * Returns the extkey
	 *
	 * @return string $extkey
	 */
	public function getExtkey() {
		return $this->extkey;
	}

	/**
	 * Sets the extkey
	 *
	 * @param string $extkey
	 * @return void
	 */
	public function setExtkey($extkey) {
		$this->extkey = $extkey;
	}

	/**
	 * Returns the message
	 *
	 * @return string $message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the message
	 *
	 * @param string $message
	 * @return void
	 */
	public function setMessage($message) {
		$this->message = $message;
	}

	/**
	 * Returns the location
	 *
	 * @return string $location
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Sets the location
	 *
	 * @param string $location
	 * @return void
	 */
	public function setLocation($location) {
		$this->location = $location;
	}

	/**
	 * Returns the line
	 *
	 * @return integer $line
	 */
	public function getLine() {
		return $this->line;
	}

	/**
	 * Sets the line
	 *
	 * @param integer $line
	 * @return void
	 */
	public function setLine($line) {
		$this->line = $line;
	}

	/**
	 * Returns the ip
	 *
	 * @return string $ip
	 */
	public function getIp() {
		return $this->ip;
	}

	/**
	 * Sets the ip
	 *
	 * @param string $ip
	 * @return void
	 */
	public function setIp($ip) {
		$this->ip = $ip;
	}

	/**
	 * @return int
	 */
	public function getCruserId() {
		return $this->cruserId;
	}

	/**
	 * @param int $cruserId
	 */
	public function setCruserId($cruserId) {
		$this->cruserId = $cruserId;
	}

	/**
	 * @return int
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * @param int $pid
	 */
	public function setPid($pid) {
		$this->pid = $pid;
	}

	/**
	 * Returns the extraData
	 *
	 * @return mixed $extraData
	 */
	public function getExtraData() {
		return $this->extraData;
	}

	/**
	 * Sets the extraData
	 *
	 * @param mixed $extraData
	 * @return void
	 */
	public function setExtraData($extraData) {
		$this->extraData = $extraData;
	}

}