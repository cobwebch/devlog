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
		$this->getLogRuns();

			// Clean up excess logs (if activated)
		if ($this->extConf['autoCleanup']) $this->logGC();

			// Get and store the GET and POST variables
		$this->setVars = t3lib_div::_GP('SET');

		parent::init();

		$this->selectLog();

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
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{

			// Load the list of values that can be used as filters (filters are used only when all entries are being displayed)
		$this->getLogFilters();

		$this->MOD_MENU = array(
			'logrun' => array(
				'1000' => $GLOBALS['LANG']->getLL('latest_run'),
				'25' => $GLOBALS['LANG']->getLL('latest_25'),
				'50' => $GLOBALS['LANG']->getLL('latest_50'),
				'100' => $GLOBALS['LANG']->getLL('latest_100'),
				'-1' => $GLOBALS['LANG']->getLL('all_entries'),
			),
			'autorefresh' => 0,
			'page' => 0,
			'expandAllExtraData' => 0,
			'sword' => '',
		);
		$this->MOD_MENU['logrun'] = t3lib_div::array_merge($this->recentRuns, $this->MOD_MENU['logrun']);

			// If the clear button has been clicked, empty all filters
		if (!empty($this->setVars['clear'])) {
			$this->selectedFilters = array();
			$GLOBALS['BE_USER']->pushModuleData('selectedFilters', $this->selectedFilters);
		}
			// Otherwise if new filters have been selected, merge them with stored selection
		else {
			if (isset($this->setVars['filters'])) {
				$storedData = $GLOBALS['BE_USER']->getModuleData('selectedFilters');
				if (isset($storedData)) {
					$this->selectedFilters = array_merge($storedData, $this->setVars['filters']);
				}
				else {
					$this->selectedFilters = $this->setVars['filters'];
				}
				$GLOBALS['BE_USER']->pushModuleData('selectedFilters', $this->selectedFilters);
			}
				// If nothing was defined, retrieve stored selection
			else {
				$this->selectedFilters = $GLOBALS['BE_USER']->getModuleData('selectedFilters');
			}
		}

		parent::menuConfig();
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
			$this->loadCSS();

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
	protected function loadCSS() {

		$inlineCSS[] .= <<< EOF
.x-selectable, .x-selectable * {
		-moz-user-select: text!important;
		-khtml-user-select: text!important;
}

/* Makes column msg beeing wrapped */
td.x-grid3-td-msg {
    overflow: hidden;
}
td.x-grid3-td-msg div.x-grid3-cell-inner {
    white-space: normal;
}

td.x-grid3-td-location {
    overflow: hidden;
}
td.x-grid3-td-location div.x-grid3-cell-inner {
    white-space: normal;
}

 .x-grid3-cell-inner, .x-grid3-hd-inner { white-space:normal !important; }

EOF;
		$this->pageRendererObject->addCssInlineBlock('Devlog', PHP_EOL . implode("\n", $inlineCSS) . PHP_EOL);
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
		$files[] = 'common.js';
		$files[] = 'Override/GridPanel.js';
		$files[] = 'Utils.js';
		$files[] = 'Application.js';
		$files[] = 'Application/AbstractBootstrap.js';
		
		$files[] = 'Store/Bootstrap.js';
//		$files[] = 'Store/LogDirectStore.js';
		$files[] = 'Store/LogJsonStore.js';
		$files[] = 'Store/FilterByTimeArrayStore.js';

		$files[] = 'UserInterface/Bootstrap.js';
		$files[] = 'UserInterface/Layout.js';
		$files[] = 'UserInterface/LogGridPanel.js';
		$files[] = 'UserInterface/RowExpander.js';
		$files[] = 'UserInterface/FilterByTimeComboBox.js';
		foreach ($files as $file) {
			$this->pageRendererObject->addJsFile($this->javascriptPath . $file, 'text/javascript', FALSE);
		}

		// @todo: no need of that now. Though, this line may be still used in the future for Ext Direct calls.
//		$this->pageRendererObject->addJsFile('ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.Devlog', 'text/javascript', FALSE);

		// label / preference datasoure
		$labels = json_encode($this->getLabels());
		$preferences = json_encode($this->getPreferences());

		// Other datasource
		$filterByTime = json_encode($this->getFilterByTimeAction());
		$logPeriod = json_encode($this->getLogPeriod());

			// *********************************** //
			// Defines onready Javascript
		$readyJavascript = array();
		$readyJavascript[] .= <<< EOF
			Ext.ns("TYPO3.Devlog");

			TYPO3.Devlog.Language = $labels;
			TYPO3.Devlog.Preferences = $preferences;

			Ext.ns("TYPO3.Devlog.Data");

			TYPO3.Devlog.Data.FilterByTime = $filterByTime;
			TYPO3.Devlog.Data.LogPeriod = $logPeriod;


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
	 * Fetches filter by time
	 *
	 * @global t3lib_DB $TYPO3_DB
	 * @global Language $LANG;
	 * @return array
	 */
	public function getFilterByTimeAction() {
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
	 * Render the "Select log" menu and return the HTML code
	 *
	 * @return      string	  HTML output
	 */
	private function renderMenuBar() {
		$headerSection ='';
		$optMenu = array ();
		$optMenu['sellogrun'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[logrun]', $this->MOD_SETTINGS['logrun'], $this->MOD_MENU['logrun']);
		if ($this->MOD_SETTINGS['logrun'] <= 1000) {
			$optMenu['autorefresh'] = '<input type="hidden" name="SET[autorefresh]" value="0">';
			$onClick = 'document.options.submit();';
			$optMenu['autorefresh'] .= '<input type="checkbox" class="checkbox" name="SET[autorefresh]" id="autorefresh" value="1"'.($this->MOD_SETTINGS['autorefresh']?' checked':'').' onclick="'.htmlspecialchars($onClick).'"> <label for="autorefresh">'.$GLOBALS['LANG']->getLL('auto_refresh').'</label>';
		}
		$optMenu['expandAllExtraData'] = '<input type="hidden" name="SET[expandAllExtraData]" value="0">';
		$onClick = 'document.options.submit();';
		$optMenu['expandAllExtraData'] .= '<input type="checkbox" class="checkbox" name="SET[expandAllExtraData]" id="expandAllExtraData" value="1"'.($this->MOD_SETTINGS['expandAllExtraData']?' checked="checked"':'').' onclick="'.htmlspecialchars($onClick).'"> <label for="expandAllExtraData">'.$GLOBALS['LANG']->getLL('expand_all_extra_data').'</label>';

		return implode('',$optMenu);
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
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {

		if(count($this->logRuns)) {
			$content = $this->getLogTable();
			return $this->doc->section($GLOBALS['LANG']->getLL('log_entries').':', $content, 0, 1);
		}
	}

	/**
	 * Creates the log entry table
	 *
	 * @return	string 	rendered HTML table
	 */
	function getLogTable()	{
		global $BACK_PATH;
		$content = '';

			// Initialise table layout
		$tableLayout = array (
			'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
			'0' => array (
				'tr' => array('<tr class="bgColor2" valign="top">', '</tr>'),
			),
			'defRow' => array (
				'tr' => array('<tr class="bgColor3-20">', '</tr>'),
				'1' => array('<td align="center">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			)
		);

		$table = array();
		$tr = 0;

			// Header row
		$table[$tr][] = $this->renderHeader('crdate');
		$table[$tr][] = $this->renderHeader('severity', true);
		$table[$tr][] = $this->renderHeader('extkey', true);
		$table[$tr][] = $this->renderHeader('msg');
		$table[$tr][] = $this->renderHeader('location');
		$table[$tr][] = $this->renderHeader('pid', true);
		$table[$tr][] = $this->renderHeader('cruser_id', true);
		$table[$tr][] = $this->renderHeader('data_var');

			// Get all the relevant log entries
		$dbres = $this->getLogEntries();

			// If the selection is empty, display a message
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($dbres) == 0) {
			$content .= $this->wrapMessage($GLOBALS['LANG']->getLL('no_entries_found'));
		}
			// Otherwise loop on the results and build table for display
		else {
			$endDate = 0;
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))) {

					// Memorise start and end date of selected entries
				if (empty($endDate)) {
					$endDate = $row['crdate'];
				}
				$startDate = $row['crdate'];

					// Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
				switch ($row['severity']) {
					case 0:
						$severity = '<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/info.gif', 'width="18" height="16"') . ' alt="" />';
						break;
					case -1:
					case 1:
					case 2:
					case 3:
						$severity = $this->doc->icons($row['severity']);
						break;
					default:
						$severity = $row['severity'];
						break;
				}

					// Add a row to the table
				$tr++;

				$table[$tr][] = $this->linkLogRun(strftime('%d-%m-%y&nbsp;%H:%M:%S', $row['crdate']), $row['crmsec']);
				$table[$tr][] = $severity;
				$table[$tr][] = htmlspecialchars($row['extkey']);
				$table[$tr][] = htmlspecialchars($row['msg']);
				$table[$tr][] = (empty($row['location']) || empty($row['line'])) ? '' : sprintf($GLOBALS['LANG']->getLL('line_call'), $row['location'], $row['line']);
				$table[$tr][] = $this->getPageLink($row['pid']);
				$table[$tr][] = $this->getRecordDetails('be_users', $row['cruser_id']);
				$dataVar = '';
				if (!empty($row['data_var'])) {
					if (strpos($row['data_var'], '"') === 0) {
						$fullData = @unserialize(stripslashes(substr($row['data_var'], 1, strlen($row['data_var']) - 1)));
					}
					else {
						$fullData = @unserialize($row['data_var']);
					}
					if ($fullData === false) {
						$dataVar = $GLOBALS['LANG']->getLL('extra_data_error');
					}
					elseif (is_array($fullData) && isset($fullData['tx_devlog_error'])) {
						$dataVar = $GLOBALS['LANG']->getLL('tx_devlog_error.' . $fullData['tx_devlog_error']);
					}
					else {
						if ($this->MOD_SETTINGS['expandAllExtraData']) {
							$style = '';
							$label = $GLOBALS['LANG']->getLL('hide_extra_data');
							$icon = '<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/minusbullet_list.gif','width="18" height="12"') . ' alt="-" />';
						} else {
							$style = ' style="display: none;"';
							$label = $GLOBALS['LANG']->getLL('show_extra_data');
							$icon = '<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet_list.gif','width="18" height="12"') . ' alt="+" />';
						}
						$dataVar = '<a href="javascript:toggleExtraData(\'' . $row['uid'] . '\')" id="debug-link-' . $row['uid'] . '" title="' . $label . '">';
						$dataVar .= $icon;
						$dataVar .= '</a>';
						$dataVar .= '<div id="debug-row-' . $row['uid'] . '"' . $style . '>' . t3lib_div::view_array($fullData) . '</div>';
					}
				}
				$table[$tr][] = $dataVar;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

			// Render the table
		$logTable = $this->doc->table($table, $tableLayout);
			// If we are viewing all entries and there was a search, parse the log table's HTML and highlight the search words
		if ($this->selectedLog == -1 && !empty($this->MOD_SETTINGS['sword'])) {
			$logTable = $this->highlightString($logTable, $this->MOD_SETTINGS['sword']);
		}

			// Assemble pagination links, if required
		$pagination = '';
		if ($this->selectedLog == -1) $pagination = $this->renderPaginationLinks();
			// Assemble log run browser, if required
		if ($this->selectedLog == 0 || $this->selectedLog > 1000) $pagination = $this->renderBrowseLinks();

			// return rendered table and pagination
		if ($startDate == $endDate) {
			$content .= '<p>'.$GLOBALS['LANG']->getLL('log_period').': '.t3lib_befunc::dateTimeAge($startDate).'</p>';
		}
		else {
			$content .= '<p>'.$GLOBALS['LANG']->getLL('log_period').': '.t3lib_befunc::dateTimeAge($startDate).' - '.t3lib_befunc::dateTimeAge($endDate).'</p>';
		}
		$content .= $this->doc->divider(5);
			// Add warning about filtered entries if any
		if (!empty($this->extConf['excludeKeys'])) {
			$content .= $this->wrapMessage($GLOBALS['LANG']->getLL('info_excluded_key').': '.$this->extConf['excludeKeys'], 'warning');
			$content .= $this->doc->spacer(5);
		}
			// Display search form, if required
		if ($this->selectedLog == -1) {
			$content .= $this->renderSearchForm();
			$content .= $this->doc->spacer(3);
		}
		$content .= $pagination;
		$content .= $logTable;
		$content .= $pagination;
		return $content;
	}

	/**
	 * This method displays a simple search form
	 * and buttons to clear the search or all filters
	 *
	 * @return	string	HTML of the search form
	 */
	function renderSearchForm() {
		$content = '<p>'.$GLOBALS['LANG']->getLL('search_data').': ';
		$content .= '<input type="text" id="sword" name="SET[sword]" value="'.$this->MOD_SETTINGS['sword'].'" /> ';
		$content .= '<input type="submit" name="search" value="'.$GLOBALS['LANG']->getLL('search').'" /> ';
		$content .= '<input type="button" name="clear_search" value="'.$GLOBALS['LANG']->getLL('clear_search').'" onclick="this.form.sword.value=\'\';this.form.submit();" />';
		$content .= '<input type="submit" name="SET[clear]" value="'.$GLOBALS['LANG']->getLL('clear_filters').'" onclick="this.form.sword.value=\'\';" style="margin-left: 20px;" />';
		$content .= '</p>';
		return $content;
	}

	/**
	 * This method takes some string and highlights some other string within it
	 *
	 * @param	string	$content: the string to parse
	 * @param	string	$word: the string to highlight
	 *
	 * @return	string	The original string with the highlighted word
	 */
	function highlightString($content, $word) {
		$highlightedContent = '';
		$replace = '<span style="' . $this->extConf['highlightStyle'] . '">' . $word . '</span>';
		if (function_exists('str_ireplace')) { // If case insensitive replace exists (PHP 5+), use it
			$highlightedContent = str_ireplace($word, $replace, $content);
		} else {
			$highlightedContent = str_replace($word, $replace, $content);
		}
		return $highlightedContent;
	}

	/**
	 * This method renders the header of the log table for a given field
	 * NOTE: in order to work the name of the field must match the name of label for this field,
	 * as well as name of the filter
	 *
	 * @param	string	$field: name of the field for which the header is being rendered
	 * @param	string	$addFilter: set to true to display the filter for the given column
	 * @return	string	HTML to display
	 */
	function renderHeader($field, $addFilter = false) {
		$header = $GLOBALS['LANG']->getLL($field);
			// Add context-sensitive help for header
		$header .= $this->renderCsh($field);
			// If turned on and in "all" log view, add filter
		if ($this->selectedLog == -1 && $addFilter) {
			$header .= '<br />' . $this->renderFilterMenu($field);
		}
		return $header;
	}

	/**
	 * This method assembles links to navigate between pages of log entries
	 *
	 * @return	string	list of pages with links
	 */
	function renderPaginationLinks() {
		$navigation = '';
		$numPages = ceil($this->totalLogEntries / $this->extConf['entriesPerPage']);
		for ($i = 0; $i < $numPages; $i++) {
			$text = ($i * $this->extConf['entriesPerPage']) . '-' . (($i + 1) * $this->extConf['entriesPerPage']);
			$item = '';
			if ($i == $this->MOD_SETTINGS['page']) {
				$item = '<strong>' . $text . '</strong>';
			} else {
				$item = '<a href="?SET[page]=' . $i . '">' . $text . '</a>';
			}
			$navigation .= $item.' ';
		}
		return '<p>' . $GLOBALS['LANG']->getLL('entries') . ': ' . $navigation . '</p>';
	}

	/**
	 * This method assemble links to navigate between previous and next log runs
	 *
	 * @return	string	list of pages with links
	 */
	function renderBrowseLinks() {
//t3lib_div::debug($this->selectedLog);
		$logTimestamps = array_keys($this->logRuns);

			// Extract first and last run
		$latestRun = $logTimestamps[0];
		$oldestRun = $logTimestamps[count($logTimestamps) - 1];

			// Look for current run and keep previous and next
		$previousRun = 0;
		$nextRun = 0;
		foreach ($logTimestamps as $index => $timestamp) {
			if ($timestamp == $this->selectedLog) {
				if (isset($logTimestamps[$index - 1])) $nextRun = $logTimestamps[$index - 1];
				if (isset($logTimestamps[$index + 1])) $previousRun = $logTimestamps[$index + 1];
				break;
			}
		}

			// Unset some unnecessary links
		if ($this->selectedLog == $oldestRun) {
			$oldestRun = 0;
			$previousRun = 0;
		} elseif ($this->selectedLog == $latestRun) {
			$latestRun = 0;
			$nextRun = 0;
		}

			// Assemble browse links: oldest, previous, next, latest (if relevant)
		$browser = '';
		if ($oldestRun > 0) {
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('oldest'), $oldestRun);
		}
		if ($previousRun > 0) {
			if (!empty($browser)) {
				$browser .= '&nbsp;&nbsp;';
			}
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('previous'), $previousRun);
		}
		if (!empty($browser)) {
			$browser .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		if ($nextRun > 0) {
			if (!empty($browser)) {
				$browser .= '&nbsp;&nbsp;';
			}
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('next'), $nextRun);
		}
		if ($latestRun > 0) {
			if (!empty($browser)) {
				$browser .= '&nbsp;&nbsp;';
			}
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('latest'), $latestRun);
		}
		return $browser;
	}

	/**
	 * This method renders a filter drop-down menu for a given filter key
	 *
	 * @param	string	name of a filter key
	 *
	 * @return	string	HTML code for the dropdown menu
	 *
	 * @see	getLogFilters()
	 */
	function renderFilterMenu($filterKey) {
		if (isset($this->filters[$filterKey])) {
			$filter = '<form name="filter' . $filterKey . '" action="" method="GET">';
			$filter .= '<select name="SET[filters][' . $filterKey . ']" onchange="this.form.submit()">';
			foreach ($this->filters[$filterKey] as $key => $value) {
				$selected = '';
				if ((string)$key == (string)$this->selectedFilters[$filterKey]) {
					$selected = ' selected="selected"';
				}
				$filter .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}
			$filter .= '</select>';
			$filter .= '</form>';
			return $filter;
		}
		else {
			return '';
		}
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
	 * This method gets the list of all the log runs
	 * It also assembles a limited list of the most recent log runs up to a limit defined by maxLogRuns
	 *
	 * @return	void
	 */
	function getLogRuns() {
		$this->logRuns = array();
		$this->recentRuns = array();
		$runLimit = empty($this->extConf['maxLogRuns']) ? 0 : $this->extConf['maxLogRuns'];
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT crmsec,crdate', 'tx_devlog', '', '', 'crmsec DESC');
			// Assemble those runs in an associative array with run timestamp as a key
		$counter = 0;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$formattedDate = t3lib_befunc::dateTimeAge($row['crdate']);
			$this->logRuns[$row['crmsec']] = $formattedDate;
			if ($runLimit != 0 && $counter < $runLimit) {
				$this->recentRuns[$row['crmsec']] = $formattedDate;
			}
			$counter++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
	}

	/**
	 * This method gets all the relevant log entries given the current settings
	 *
	 * @return	pointer		Database resource
	 */
	function getLogEntries() {

			// Select only the logs from a single run
		if ($this->selectedLog > 1000) {
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_devlog', 'crmsec = '.$this->selectedLog, $groupBy='', $orderBy='uid', $limit='');
		}
			// Select all log entries, but taking pagination into account
		elseif ($this->selectedLog == -1) {

				// Assemble the SQL condition from filters and an eventual search criteria
			$whereClause = '';
//t3lib_div::debug($this->MOD_SETTINGS);
			if (is_array($this->selectedFilters)) {
				foreach ($this->selectedFilters as $key => $value) {
					if ($value  != '*') {
						if (!empty($whereClause)) $whereClause .= ' AND ';
						$whereClause .= $key." = '".$value."'";
					}
				}
			}
			if (!empty($this->MOD_SETTINGS['sword'])) {
				if (!empty($whereClause)) $whereClause .= ' AND ';
				$fullyQuotedString = $GLOBALS['TYPO3_DB']->fullQuoteStr('%'.$this->MOD_SETTINGS['sword'].'%', 'tx_devlog');
				$whereClause .= '(msg LIKE '.$fullyQuotedString.' OR data_var LIKE '.$fullyQuotedString.')';
			}

				// Load the total entries count
			$this->getLogEntriesCount($whereClause);

				// Make sure the start page number is not an empty string
			$page = 0;
			if (!empty($this->MOD_SETTINGS['page'])) {
				$page = $this->MOD_SETTINGS['page'];
			}
				// Calculate start page
				// If start is larger than entries count, revert to first page (0)
			$start = $page * $this->extConf['entriesPerPage'];
			if ($start > $this->totalLogEntries) $start = 0;
			$limit = $start.','.$this->extConf['entriesPerPage'];
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_devlog', $whereClause, $groupBy='', $orderBy='uid DESC', $limit);
		}
			// Select the latest log entries up to the selected limit
		else {
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_devlog', '', $groupBy='', $orderBy='uid DESC', $limit=$this->selectedLog);
		}
		return $dbres;
	}

	/**
	 * This method gets the total number of log entries in the database
	 *
	 * @param	string	a SQL WHERE clause to apply to the total, without the "WHERE" keyword
	 *
	 * @return	void
	 */
	function getLogEntriesCount($whereClause = '') {
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid) AS total', 'tx_devlog', $whereClause);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
		$this->totalLogEntries = $row['total'];
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
	}

	/**
	 * This method retrieves lists of (unique) values that can be used to filter log entries
	 * Possible filters are: extension keys and pages
	 * (severities are hard-code anyway, so no need to get them from the database)
	 *
	 * @return	void
	 */
	function getLogFilters() {
			// Get list of existing extension keys in the log table
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT extkey', 'tx_devlog', '', '', 'extkey ASC');
		$this->filters['extkey'] = array('*' => '');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))) {
			$this->filters['extkey'][$row['extkey']] = $row['extkey'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

			// Get list of pages referenced in the log table
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT pid', 'tx_devlog', '');
		$this->filters['pid'] = array('*' => '');
		$this->records['pages'] = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			if (!empty($row['pid'])) {
				$page = t3lib_BEfunc::getRecord('pages', $row['pid']);
				$elementTitle = t3lib_BEfunc::getRecordTitle('pages', $page, 1);
				$page['t3lib_BEfunc::title'] = $elementTitle;
				$this->records['pages'][$row['pid']] = $page;
				$this->filters['pid'][$row['pid']] = $elementTitle;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

			// Get list of users referenced in the log table
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT cruser_id', 'tx_devlog', '');
		$this->filters['cruser_id'] = array('*' => '');
		$this->records['be_users'] = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			if (!empty($row['cruser_id'])) {
				$record = t3lib_BEfunc::getRecord('be_users', $row['cruser_id']);
				$elementTitle = t3lib_BEfunc::getRecordTitle('be_users', $record, 1);
				$record['t3lib_BEfunc::title'] = $elementTitle;
				$this->records['be_users'][$row['cruser_id']] = $record;
				$this->filters['cruser_id'][$row['cruser_id']] = $elementTitle;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);

			// Get list of severities
		$this->filters['severity']['*'] = '';
		$this->filters['severity']['-1'] = $GLOBALS['LANG']->getLL('severity_ok');
		$this->filters['severity']['0'] = $GLOBALS['LANG']->getLL('severity_info');
		$this->filters['severity']['1'] = $GLOBALS['LANG']->getLL('severity_notice');
		$this->filters['severity']['2'] = $GLOBALS['LANG']->getLL('severity_warning');
		$this->filters['severity']['3'] = $GLOBALS['LANG']->getLL('severity_error');
	}

	/**
	 * This method is used to set the selectedLog flag
	 *
	 * @return	void
	 */
	function selectLog() {
			// If no logrun was explicitly selected, get the one stored in session
		if (empty($this->setVars['logrun'])) $this->setVars['logrun'] = $this->MOD_SETTINGS['logrun'];

			// If logrun is 1000, we want to display only the latest log run
			// In this case, we select the timestamp key from the latest run
		if ($this->setVars['logrun'] == 1000) {
			reset($this->logRuns);
			$this->selectedLog = key($this->logRuns);
		}
			// Otherwise just take the logrun value as is
		else {
			$this->selectedLog = $this->setVars['logrun'];
		}
	}

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

	/**
	 * Assemble the link to select a single log run
	 *
	 * @return	string
	 */
	function linkLogRun($str, $logRun) {
		$content = '<a href="' . $GLOBALS['MCONF']['_'] . '&SET[logrun]=' . $logRun . '">' . $str . '</a>';
		return $content;
	}

    /**
     * Returns a linked icon with title from a record
     * NOTE: currently this is only called for the pages table, as table names are not stored in the devlog (but a pid may be)
     *
     * @param	integer		ID of the record to link to
     * @return  string		HTML for icon, title and link
     */
    function getPageLink($uid) {
        global $BACK_PATH;
		if (empty($uid)) {
			return '';
		} else {
				// Retrieve the stored page information
				// (pages were already fetched in getLogFilters)
			$row = $this->records['pages'][$uid];
			$iconAltText = t3lib_BEfunc::getRecordIconAltText($row, 'pages');

				// Create icon for record
			$elementIcon = t3lib_iconworks::getIconImage('pages', $row, $BACK_PATH, 'class="c-recicon" title="' . $iconAltText . '"');

				// Return item with edit link
			$editOnClick = 'top.loadEditId(' . $uid . ')';
			$string = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $elementIcon . $row['t3lib_BEfunc::title'] . '</a>';
			return $string;
		}
    }

	/**
	 * This method gets the title and the icon for a given record of a given table
	 * It returns these as a HTML string
	 *
	 * @param	string		$table: name of the table
	 * @param	integer		$uid: primary key of the record
	 * @return	string		HTML to display
	 */
	function getRecordDetails($table, $uid) {
        global $BACK_PATH;
		if (empty($table) || empty($uid)) {
			return '';
		} else {
			$row = array();
			if (isset($this->records[$table][$uid])) {
				$row = $this->records[$table][$uid];
			} else {
				$row = t3lib_BEfunc::getRecord($table, $uid);
			}
	        $iconAltText = t3lib_BEfunc::getRecordIconAltText($row, $table);
            $elementTitle = t3lib_BEfunc::getRecordTitle($table, $row, 1);
	        $elementIcon = t3lib_iconworks::getIconImage($table, $row, $BACK_PATH, 'class="c-recicon" title="' . $iconAltText . '"');
			return $elementIcon.$elementTitle;
		}
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
