<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Rene Fritz (r.fritz@colorcube.de)
*  (c) 2009 Francois Suter (typo3@cobweb.ch)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
*  $Id$
***************************************************************/

/**
 * BE module for the 'devlog' extension.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @author	Francois Suter <typo3@cobweb.ch>
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 */

	// this is a hack to prevent logging while initialization inside of this module
$EXTCONF['devlog']['nolog'] = TRUE;
$TYPO3_CONF_VARS['EXTCONF']['devlog']['nolog'] = TRUE;

$BE_USER->modAccess($MCONF, 1);	// This checks permissions and exits if the users has no permission for entry.

class tx_devlog_module1 extends t3lib_SCbase {
	protected $pageinfo;
	protected $logRuns = array(); // List of all log runs
	protected $recentRuns = array(); // List of recent log runs
	protected $setVars = array(); // All variables passed when calling the script (GET and POST)
	protected $selectedLog; // Flag for the number of logs to display
	protected $totalLogEntries; // Total number of log entries in the database
	protected $filters = array(); // List of possible values for the log filters
	protected $records = array(); // List of records that are gotten from the database and that may be used several times
	protected $selectedFilters = array(); // Selected filters and their values
	protected $extConf = array(); // Extension configuration
	protected $defaultEntriesPerPage = 25; // Default value for number of entries per page configuration parameter
	protected $cshKey; // Key of the CSH file
	protected $cleanupPeriods = array('1hour' => '-1 hour', '1week' => '-1 week', '1month' => '-1 month', '3months' => '-3 months', '6months' => '-6 months', '1year' => '-1 year'); // List of possible periods for cleaning up log entries
	protected $extensionName = 'devlog';

	/**
	 * API of $this->pageRendererObject can be found at
	 * http://ecodev.ch/api/typo3/html/classt3lib___page_renderer.html
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRendererObject;

	/**
	 * API of $this->doc can be found at
	 * http://ecodev.ch/api/typo3/html/classtemplate.html
	 *
	 * @var template
	 */
	public $doc;

	/**
	 * the relative javascript path
	 *
	 * @var string
	 */
	public $javascriptPath;


	/**
	 * Initialise the plugin
	 *
	 * @return	void
	 */
	function initialize()	{
		global $MCONF;
		global $BACK_PATH;

			// Get extension configuration
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['devlog']);
		if (empty($this->extConf['entriesPerPage'])) $this->extConf['entriesPerPage'] = $this->defaultEntriesPerPage;

			// Load language
		$GLOBALS['LANG']->includeLLFile('EXT:devlog/Resources/Private/Language/locallang.xml');
		
			// Get log run list
//		$this->getLogRuns();

			// Clean up excess logs (if activated)
		if ($this->extConf['autoCleanup']) $this->logGC();

			// Get and store the GET and POST variables
		$this->setVars = t3lib_div::_GP('SET');

		parent::init();

//		$this->selectLog();

			// Set key for CSH
		$this->cshKey = '_MOD_'.$MCONF['name'];

		
			// Initilize properties
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->pageRendererObject = $this->doc->getPageRenderer();

			// Defines javascript resource file
		$this->javascriptPath = t3lib_extMgm::extRelPath('devlog') . 'Resources/Public/javascripts/';
		
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return	void
	 */
	function main()	{
		global $BACK_PATH;

		// Access check! Allow only admin user to view this content
		if ($GLOBALS['BE_USER']->user['admin'])	{

				// Processes the parameters passed to tx_devlog
			$message = $this->processParameters();

				// Load Inline CSS
			$this->loadStylesheets();

				// Load javascript header
			$this->loadJavascript();

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));

			$markers['###HEADER###'] = $this->doc->header($GLOBALS['LANG']->getLL('title'));
//			$markers['###MENUBAR###'] = $this->renderMenuBar();
			$markers['###CLEARMENU###'] = $this->renderClearMenu();
			$markers['###OPEN_NEW_VIEW###'] = $this->openNewView();
			$markers['###MESSAGE###'] = $message;
			#$markers['###CONTENT###'] = $this->moduleContent();
			$markers['###SHORTCUT###'] = '';
			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
				$markers['###SHORTCUT###'] = $this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
			}

				// Merges label coming from the template (e.g EXT:devlog/Resources/Private/Template/index.html)
			#$markers = array_merge($markers, $this->getLabelMarkers());

			$backendTemplateFile = t3lib_div::getFileAbsFileName('EXT:devlog/Resources/Private/Templates/index.html');
			$this->content .= t3lib_parsehtml::substituteMarkerArray(file_get_contents($backendTemplateFile), $markers);
		}
		else {
				// If no access
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		}
	}

	/**
	 * Load CSS styles onto the BE Module
	 *
	 * @return void
	 */
	protected function loadStylesheets() {
		$path = t3lib_extMgm::extRelPath('devlog');
		
//		$inlineCSS[] .= <<< EOF
//
//EOF;
//		$this->pageRendererObject->addCssInlineBlock('Devlog', PHP_EOL . implode("\n", $inlineCSS) . PHP_EOL);
		$this->pageRendererObject->addCssFile($path . 'Resources/Public/stylesheets/devlog.css');
	}

	/**
	 * Load Javascript files onto the BE Module
	 *
	 * @return void
	 */
	protected function loadJavascript() {

			// *********************************** //
			// Load ExtCore library
		$this->pageRendererObject->loadExtJS();
		$this->pageRendererObject->enableExtJsDebug();

			// *********************************** //
			// Defines what files should be loaded and loads them
		$files = array();
		$files[] = 'Override/GridPanel.js';
		$files[] = 'Utils.js';
		$files[] = 'Application.js';
		$files[] = 'Application/AbstractBootstrap.js';

		// Stores
		$files[] = 'Store/Bootstrap.js';
//		$files[] = 'Store/LogDirectStore.js';
		$files[] = 'Store/LogJsonStore.js';
		$files[] = 'Store/TimeListStore.js';
		$files[] = 'Store/SeverityListStore.js';
		$files[] = 'Store/ExtensionListStore.js';
		$files[] = 'Store/PageListStore.js';

		// UserInterface
		$files[] = 'UserInterface/Bootstrap.js';
		$files[] = 'UserInterface/Layout.js';
		$files[] = 'UserInterface/RowExpander.js';
		$files[] = 'UserInterface/AjaxRowExpander.js';
		$files[] = 'UserInterface/Iconcombo.js';

		// Listing
		$files[] = 'Listing/Bootstrap.js';
		$files[] = 'Listing/LogGrid.js';
		$files[] = 'Listing/TimeList.js';
		$files[] = 'Listing/SeverityList.js';
		$files[] = 'Listing/ExtensionList.js';
		$files[] = 'Listing/PageList.js';

		foreach ($files as $file) {
			$this->pageRendererObject->addJsFile($this->javascriptPath . $file, 'text/javascript', FALSE);
		}

		// @todo: no need of that now. Though, this line may be still used in the future for Ext Direct calls.
//		$this->pageRendererObject->addJsFile('ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.Devlog', 'text/javascript', FALSE);

		// label / preference datasoure
		$labels = json_encode($this->getLabels());
		$preferences = json_encode($this->getPreferences());

		// Other datasource
		$timeList = json_encode($this->getTimeList());
		$severityList = json_encode($this->getSeverityList());
		$extensionList = json_encode($this->getExtensionList());
		$pageList = json_encode($this->getPageList());
		$logPeriod = json_encode($this->getLogPeriod());
		$lastLogTime = json_encode($this->getLastLogTime());

			// *********************************** //
			// Defines onready Javascript
		$readyJavascript = array();
		$readyJavascript[] .= <<< EOF
			Ext.ns("TYPO3.Devlog");
			TYPO3.Devlog.Language = $labels;
			TYPO3.Devlog.Preferences = $preferences;

			Ext.ns("TYPO3.Devlog.Data");
			TYPO3.Devlog.Data.TimeList = $timeList;
			TYPO3.Devlog.Data.ExtensionList = $extensionList;
			TYPO3.Devlog.Data.SeverityList = $severityList;
			TYPO3.Devlog.Data.PageList = $pageList;
			TYPO3.Devlog.Data.LogPeriod = $logPeriod;
			TYPO3.Devlog.Data.LastLogTime = $lastLogTime;


//		for (var api in Ext.app.ExtDirectAPI) {
//			Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
//		}
//
//		TYPO3.Devlog.Remote.testMe("Hellooo", "World!", function(result) {
//			if (typeof console == "object") {
//				console.log(result);
//			} else {
//				alert(result);
//			}
//		});

EOF;

		$this->pageRendererObject->addExtOnReadyCode(PHP_EOL . implode("\n", $readyJavascript) . PHP_EOL);

			// *********************************** //
			// Defines contextual variables
			// Define function for switching visibility of extra data field on or off
		$imageExpand = t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/plusbullet_list.gif','width="18" height="12"');
		$imageCollapse = t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/minusbullet_list.gif','width="18" height="12"');


		if (!isset($this->extConf['refreshFrequency'])) {
			throw new tx_devlog_exception('Missing setting "refreshFrequency". Try to re-set settings in the Extension Manager.', 1275573201);
		}
		
		$autoRefresh = $this->MOD_SETTINGS['autorefresh'] ? $this->extConf['refreshFrequency'] : '0';
		$this->inlineJavascript[] .= <<< EOF
Ext.ns("{$this->extensionName}");
devlog = {
	imageExpand: '<img $imageExpand alt="+" />',
	imageCollapse: '<img $imageCollapse alt="-" />',
	show_extra_data: '{$GLOBALS['LANG']->getLL('show_extra_data')}',
	hide_extra_data: '{$GLOBALS['LANG']->getLL('hide_extra_data')}',
	autorefresh: $autoRefresh,
}
EOF;
		$this->pageRendererObject->addJsInlineCode('devlog', implode("\n", $this->inlineJavascript));
	}

	/**
	 * Get log period
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return string
	 */
	public function getLogPeriod() {
		global $TYPO3_DB;
		global $LANG;

		$content = '';
		$startDate = $endDate = 0;

		// Fetches interval of time
		$records = $TYPO3_DB->exec_SELECTgetRows('MAX(crdate) AS maximum, MIN(crdate) AS minimum', 'tx_devlog', '');
		if (!empty($records[0])) {
			$endDate = $records[0]['maximum'];
			$startDate = $records[0]['minimum'];
		}
		
		if ($startDate > 0 && $endDate > 0) {
			// return rendered table and pagination
			if ($startDate != $endDate) {
				$content = $LANG->getLL('log_period').': '.t3lib_befunc::dateTimeAge($startDate).' - '.t3lib_befunc::dateTimeAge($endDate);
			}
			else {
				$content = $LANG->getLL('log_period') . ': '.t3lib_befunc::dateTimeAge($startDate);
			}
		}
		
		return $content;
	}

	/**
	 * Get log period
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @return string
	 */
	public function getLastLogTime() {
		global $TYPO3_DB;
		$result = 0;
		// Fetches interval of time
		$records = $TYPO3_DB->exec_SELECTgetRows('MAX(crmsec) AS crmsec', 'tx_devlog', '');
		if (isset($records[0]['crmsec'])) {
			$result = $records[0]['crmsec'];
		}
		return $result;
	}
	/**
	 * Fetches filter by time
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return array
	 */
	public function getTimeList() {
		global $TYPO3_DB;
		global $LANG;
		
			// Initialize $records with default value
		$records[] = array('1000', $LANG->getLL('latest_run'));
		$records[] = array('25', $LANG->getLL('latest_25'));
		$records[] = array('50', $LANG->getLL('latest_50'));
		$records[] = array('100', $LANG->getLL('latest_100'));
		$records[] = array('-1', $LANG->getLL('all_entries'));

		$runLimit = empty($this->extConf['maxLogRuns']) ? 0 : $this->extConf['maxLogRuns'];
		$dbres = $TYPO3_DB->exec_SELECTquery('DISTINCT crmsec, crdate', 'tx_devlog', '', '', 'crmsec DESC');

			// Assemble those runs in an associative array with run timestamp as a key
		$counter = 0;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$formattedDate = t3lib_befunc::dateTimeAge($row['crdate']);
			$logRuns[$row['crmsec']] = $formattedDate;
			if ($runLimit != 0 && $counter < $runLimit) {
				$records[] = array($row['crmsec'], $formattedDate);
			}
			$counter++;
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

		return $records;
	}

	/**
	 * Fetches list of severities
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return array
	 */
	public function getSeverityList() {
		global $TYPO3_DB;
		global $LANG;

		$records[] = array('', $LANG->getLL('selectseverity'), '');
		$dbres = $TYPO3_DB->exec_SELECTquery('DISTINCT severity', 'tx_devlog', '', '', 'crmsec DESC');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$severityLabel = '';
			switch ($row['severity']) {
				case '-1':
					$severity = 'ok';
					break;
				case '0':
					$severity = 'info';
					break;
				case '1':
					$severity = 'notice';
					break;
				case '2':
					$severity = 'warning';
					break;
				case '3':
					$severity = 'error';
					break;
			}
			$severityLabel = $LANG->getLL('severity_' . $severity);
			$icon = t3lib_iconWorks::getSpriteIcon('extensions-devlog-' . $severity);
			$records[] = array($row['severity'], $severityLabel, $severity);
		}
		return $records;
	}

	/**
	 * Fetches list of extensions
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return array
	 */
	public function getExtensionList() {
		global $TYPO3_DB;
		global $LANG;
		global $TYPO3_LOADED_EXT;

		$records[] = array('', $LANG->getLL('selectextentionkey'), '');
		$dbres = $TYPO3_DB->exec_SELECTquery('DISTINCT extkey', 'tx_devlog', '', '', 'crmsec DESC');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$extKey = $row['extkey'];
			if (isset($TYPO3_LOADED_EXT[$extKey]['typo3RelPath'])) {
				$className = $extKey;
				$iconPath = $TYPO3_LOADED_EXT[$extKey]['typo3RelPath'] . 'ext_icon.gif';
				$inlineCSS[] .= <<< EOF
					.$extKey {
						background-image:url(../../../../../../$iconPath) !important;
					}
EOF;
			}
			else {
				$className = 'missing';
			}
			$records[] = array($row['extkey'], $row['extkey'], $className);
		}
		$this->pageRendererObject->addCssInlineBlock('devlog-class-extension', PHP_EOL . implode("\n", $inlineCSS) . PHP_EOL);
		return $records;
	}
	
	/**
	 * Fetches list of pages
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return array
	 */
	public function getPageList() {
		global $TYPO3_DB;
		global $LANG;

		$records[] = array('', $LANG->getLL('selectpage'), '');
		$dbres = $TYPO3_DB->exec_SELECTquery('DISTINCT pid', 'tx_devlog', '', 'pid ASC');

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {

				// Retrieve the stored page information
			$page = t3lib_BEfunc::getRecord('pages', $row['pid']);
			$elementTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);
			$records[] = array($row['pid'], $row['pid'] . ' - ' . $elementTitle, 'page');
		}
		return $records;
	}

	/**
	 * Return labels in the form of an array
	 *
	 * @global Language $LANG
	 * @global array $LANG_LANG
	 * @return array
	 */
	protected function getLabels() {
		global $LANG;
		global $LOCAL_LANG;

		if (isset($LOCAL_LANG[$LANG->lang]) && !empty($LOCAL_LANG[$LANG->lang])) {
			$markers = $LOCAL_LANG[$LANG->lang];
			//$markers = $LANG->includeLLFile('EXT:devlog/Resources/Private/Language/locallang.xml', 0);
		}
		else {
			throw new tx_devlog_exception('No language file has been found', 1276451853);
		}
		return $markers;
	}

	/**
	 * Returns some preferences for the Application
	 *
	 * @global array $TYPO3_CONF_VARS
	 * @return array
	 */
	protected function getPreferences() {
		global $TYPO3_CONF_VARS;

		$preferences['dateFormat'] = $TYPO3_CONF_VARS['SYS']['ddmmyy'];
		$preferences['timeFormat'] = $TYPO3_CONF_VARS['SYS']['hhmm'];
		$preferences['pageSize'] = 25;
		return $preferences;
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Render the CSH icon/box of a given key and return the HTML code
	 *
	 * @param       string	  $str: Locallang key
	 * @return      string	  HTML output
	 */
	function renderCsh($str) {
		global $BACK_PATH;
		return t3lib_BEfunc::cshItem($this->cshKey, $str, $BACK_PATH, '|', false, 'margin-bottom:0px;');
	}

	/**
	 * Processes GP parameters
	 *
	 * @return      string	  HTML message for the BE
	 */
	private function processParameters() {
		$message = '';
		$parameters = t3lib_div::_GP('tx_devlog');
		if (isset($parameters['clear'])) {
			if ($parameters['clear'] == 'all') {
				$where = '';
			}
			else if ((int) $parameters['clear'] > 0) {
				$where = "crdate <= '" . $parameters['clear'] . "'";
			}
			else {
				$where = "extkey = '" . $parameters['clear'] . "'";
			}

			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_devlog', $where);
			$affectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			$message = $this->wrapMessage(sprintf($GLOBALS['LANG']->getLL('cleared_log'), $affectedRows), 'success');
		}
		return $message;
	}

	/**
	 * Render the "clear all" menu option and return the HTML code
	 *
	 * @return      string	  HTML output
	 */
	private function renderClearMenu() {
		global $LANG;
		$labelClearLog = $LANG->getLL('clearlog');
		$labelClearAllLog = $LANG->getLL('clearalllog');
		$labelCleanUpForPeriod = $LANG->getLL('cleanup_for_period');
		$labelCleanUpForExtension = $LANG->getLL('cleanup_for_extension');
		$numberOfEntries = $this->renderClearAllMenu();
		$clearByTimeMenu = $this->renderClearByTimeMenu();
		$clearByExtensionMenu = $this->renderClearByExtensionMenu();
		$content .= <<< EOF
<select onchange="this.parentNode.submit()" name="tx_devlog[clear]">
	<option selected="selected" value="">$labelClearLog</option>
	<optgroup class="c-divider" label="$labelClearAllLog">
		<option value="all">$numberOfEntries</option>
	</optgroup>
	<optgroup class="c-divider" label="$labelCleanUpForPeriod">
		$clearByTimeMenu
	</optgroup>
	<optgroup class="c-divider" label="$labelCleanUpForExtension">
		$clearByExtensionMenu
	</optgroup>
</select>
EOF;
		return $content;
	}

	/**
	 * Render the "clear all" menu option and return the HTML code
	 *
	 * @return      string	  HTML output
	 */
	private function renderClearAllMenu() {
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid) AS total', 'tx_devlog', '');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		return sprintf($GLOBALS['LANG']->getLL('xx_entries'), $row['total']);
	}

	/**
	 * Render the "clear by time" menu and return the HTML code
	 *
	 * @param       string	  $str: Locallang key
	 * @return      string	  HTML output
	 */
	private function renderClearByTimeMenu() {
		$content = '';
		foreach ($this->cleanupPeriods as $key => $period) {
			$date = strtotime($period);
			$content .= '<option value="'.$date.'">'.$GLOBALS['LANG']->getLL($key).'</option>';
		}
		return $content;
	}

	/**
	 * Render the "clear by extension" menu and return the HTML code
	 *
	 * @param       string	  $str: Locallang key
	 * @return      string	  HTML output
	 */
	private function renderClearByExtensionMenu() {
		$content = '';
			// Get list of existing extension keys in the log table
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT extkey', 'tx_devlog', '', '', 'extkey ASC');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$content .= '<option value="'.$row['extkey'].'">'.$row['extkey'].'</option>';
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

		return $content;
	}

	/**
	 * This method wraps a given string with some styling depending on the type of message
	 * (success, wraning or error)
	 * This wrapper makes it easier to change the kind of styling (e.g. when it will be easier to load custom CSS in a BE module)
	 *
	 * @param	string	$string: the message to wrap
	 * @param	string	$type: the type of message (success, wraning or error)
	 * @return	string	The wrapped string
	 */
	private function wrapMessage($string, $type = 'error') {
		switch ($type) {
			case 'success':
				$type = t3lib_FlashMessage::OK;
				break;
			case 'warning':
				$type = t3lib_FlashMessage::ERROR;
				break;
			default:
				$type = t3lib_FlashMessage::INFO;
				break;
		}

		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$string,
			'',
			$type
		);
		return $flashMessage->render();
	}

	/*******************************************
	 *
	 * DB stuff
	 *
	 *******************************************/

	/**
	 * This method cleans up any log runs in excess of maxLogRuns
	 *
	 * @return	void
	 */
	function logGC() {
		if (!empty($this->extConf['maxLogRuns']) && count($this->logRuns) >= $this->extConf['maxLogRuns']) {
			$keys = array_keys($this->logRuns);
			$logRun = $keys[$this->extConf['maxLogRuns'] - 1];
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_devlog', 'crmsec < ' . $logRun);
		}
	}

	/**
	 * This method prepares the link for opening the devlog in a new window
	 *
	 * @return	string	Hyperlink with icon and appropriate JavaScript
	 */
	function openNewView() {
		global $BACK_PATH;

		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT');
		$onClick = "devlogWin=window.open('" . $GLOBALS['MCONF']['_'] . "','devlog','width=790,status=0,menubar=1,resizable=1,location=0,scrollbars=1,toolbar=0');devlogWin.focus();return false;";
		$content = '<a id="openview" href="#" onclick="' . htmlspecialchars($onClick).'">' .
					'<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/open_in_new_window.gif', 'width="19" height="14"') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.openInNewWindow', 1) . '" class="absmiddle" alt="" />' .
					'</a>';
		return $content;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/mod1/index.php']);
}

try {
	// Make instance:
	$SOBE = t3lib_div::makeInstance('tx_devlog_module1');
	$SOBE->initialize();
	$SOBE->main();
	$SOBE->printContent();
}
catch (Exception $e) {
	print $e->getMessage();
}
?>
