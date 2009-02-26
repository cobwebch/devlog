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
 */

	// this is a hack to prevent logging while initialization inside of this module
$EXTCONF['devlog']['nolog'] = TRUE;

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require ('conf.php');
require ($BACK_PATH.'init.php');

$TYPO3_CONF_VARS['EXTCONF']['devlog']['nolog'] = TRUE;

require ($BACK_PATH.'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:devlog/mod1/locallang.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_devlog_module1 extends t3lib_SCbase {
	var $pageinfo;

	var $logRuns = array(); // List of all log runs
	var $recentRuns = array(); // List of recent log runs
	var $setVars = array(); // All variables passed when calling the script (GET and POST)
	var $selectedLog; // Flag for the number of logs to display
	var $totalLogEntries; // Total number of log entries in the database
	var $filters = array(); // List of possible values for the log filters
	var $records = array(); // List of records that are gotten from the database and that may be used several times
	var $selectedFilters = array(); // Selected filters and their values
	var $extConf = array(); // Extension configuration
	var $defaultEntriesPerPage = 25; // Default value for number of entries per page configuration parameter

	var $cleanupPeriods = array('1hour' => '-1 hour', '1week' => '-1 week', '1month' => '-1 month', '3months' => '-3 months', '6months' => '-6 months', '1year' => '-1 year'); // List of possible periods for cleaning up log entries

	/**
	 * Initialise the plugin
	 *
	 * @return	void
	 */
	function init()	{
		global $MCONF;

			// Get extension configuration
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$MCONF['extKey']]);
		if (empty($this->extConf['entriesPerPage'])) $this->extConf['entriesPerPage'] = $this->defaultEntriesPerPage;

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
			'function' => array(
				'showlog' => $GLOBALS['LANG']->getLL('showlog'),
				'cleanup' => $GLOBALS['LANG']->getLL('cleanup'),
//				'setup' => $GLOBALS['LANG']->getLL('setup'),
			),
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
	
				// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;

				// JavaScript
				// Load Prototype library (check if it exists in the TYPO3 source, otherwise get it from extension configuration)
			$pathToPrototype = '';
			if (file_exists($BACK_PATH.'contrib/prototype/prototype.js')) {
				$pathToPrototype = $BACK_PATH.'contrib/prototype/prototype.js';
			}
			elseif (isset($this->extConf['prototypePath'])) {
				$testPath = t3lib_div::getFileAbsFileName($this->extConf['prototypePath']);
				if (file_exists($testPath)) $pathToPrototype = $BACK_PATH.'../'.$this->extConf['prototypePath'];
			}
			if (!empty($pathToPrototype)) $this->doc->JScode .= '<script type="text/javascript" src="'.$pathToPrototype.'"></script>'."\n";

				// Define function for switching visibility of extra data field on or off
			$this->doc->JScodeArray[] .= 'var imageExpand = \'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet_list.gif','width="18" height="12"').' alt="+" />\';';
			$this->doc->JScodeArray[] .= 'var imageCollapse = \'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/minusbullet_list.gif','width="18" height="12"').' alt="-" />\';';
			$this->doc->JScodeArray[] .= '
					function toggleExtraData(theID) {
						var theLink = $(\'debug-link-\' + theID);
						var theElement = $(\'debug-row-\' + theID);
						if (theElement.visible()) {
							theElement.hide();
							theLink.update(imageExpand);
							theLink.title = \''.$GLOBALS['LANG']->getLL('show_extra_data').'\';
						}
						else {
							theElement.show();
							theLink.update(imageCollapse);
							theLink.title = \''.$GLOBALS['LANG']->getLL('hide_extra_data').'\';
						}
					}
			';

				// JavaScript for menu switching
			$this->doc->JScodeArray[] = '
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}';

				// JavaScript for automatic reloading of log window
			$this->doc->JScodeArray[] = '
				var reloadTimer = null;
				
				window.onload = function() {
				  if(window.name=="devlog") {
					document.getElementById("openview").style.visibility = "hidden";
				  }
				  setReloadTime('.($this->MOD_SETTINGS['autorefresh'] ? $this->extConf['refreshFrequency'] : '0').');
				}
				
				function setReloadTime(secs) {
				  if (arguments.length == 1) {
				    if (reloadTimer) clearTimeout(reloadTimer);
				    if (secs) reloadTimer = setTimeout("setReloadTime()", Math.ceil(parseFloat(secs) * 1000));
				  }
				  else {
				    //window.location.replace(window.location.href);
				    document.options.submit();
				  }
				}
				
				function toggleReload(autorefresh) {
					if(autorefresh){
						setReloadTime(2);
					}else{
						setReloadTime(0);
					};
				}';
				


			$headerSection ='';
			if ($this->MOD_SETTINGS['function'] == 'showlog') {
				$optMenu = array ();
				$optMenu['sellogrun'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[logrun]', $this->MOD_SETTINGS['logrun'], $this->MOD_MENU['logrun']);
				if ($this->MOD_SETTINGS['logrun'] <= 1000) {
					$optMenu['autorefresh'] = '<input type="hidden" name="SET[autorefresh]" value="0">';
					$onClick = 'toggleReload(this.checked);';
					$optMenu['autorefresh'] .= '<input type="checkbox" name="SET[autorefresh]" id="autorefresh" value="1"'.($this->MOD_SETTINGS['autorefresh']?' checked':'').' onclick="'.htmlspecialchars($onClick).'"> <label for="autorefresh">'.$GLOBALS['LANG']->getLL('auto_refresh').'</label>';
				}
				$optMenu['refresh'] = '<input type="submit" name="refresh" value="'.$GLOBALS['LANG']->getLL('refresh').'">';
				$optMenu['expandAllExtraData'] = '<input type="hidden" name="SET[expandAllExtraData]" value="0">';
				$onClick = 'document.options.submit();';
				$optMenu['expandAllExtraData'] .= '<input type="checkbox" name="SET[expandAllExtraData]" id="expandAllExtraData" value="1"'.($this->MOD_SETTINGS['expandAllExtraData']?' checked="checked"':'').' onclick="'.htmlspecialchars($onClick).'"> <label for="expandAllExtraData">'.$GLOBALS['LANG']->getLL('expand_all_extra_data').'</label>';

				$headerSection = $this->doc->menuTable(
					array(
						array($GLOBALS['LANG']->getLL('selectlog'), $optMenu['sellogrun']),
						array('', $optMenu['refresh'])
					),
					array(
						array('', $optMenu['autorefresh']),
						array('',$optMenu['expandAllExtraData'])
					)
				);			
			}
			
			
			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= '<form name="options" action="" method="POST">'.$this->doc->section('', $this->doc->funcMenu($headerSection, t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'], $this->MOD_MENU['function']).'&nbsp;&nbsp;&nbsp;'.$this->openNewView())).'</form>';
			$this->content .= $this->doc->divider(5);


			// Render content:
			$this->moduleContent();

			
			// ShortCut
			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
				$this->content .= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}
		
			$this->content .= $this->doc->spacer(10);
		}
		else {
				// If no access
		
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
		
			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->spacer(10);
		}
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

		switch((string)$this->MOD_SETTINGS['function'])	{
			case 'showlog':
				if(count($this->logRuns)) {					
					$content = $this->getLogTable();
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('log_entries').':', $content, 0, 1);
				}
			break;
			case 'cleanup':
				$content = $this->cleanupScreen();
				$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('clearlog').':', $content, 0, 1);
			break;
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

			// init table layout
		$tableLayout = array (
			'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
			'0' => array (
				'tr' => array('<tr class="bgColor2" valign="top">', '</tr>'),
			),
			'defRow' => array (
				'tr' => array('<tr class="bgColor-20">', '</tr>'),
				'1' => array('<td align="center">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			)
		);

		$table = array();
		$tr = 0;
		
			// add header row
		$table[$tr][] = $this->renderHeader('uid');
		$table[$tr][] = $this->renderHeader('severity', true, true);
		$table[$tr][] = $this->renderHeader('crdate', false, true);
		$table[$tr][] = $this->renderHeader('extkey', true, true);
		$table[$tr][] = $this->renderHeader('message');
		$table[$tr][] = $this->renderHeader('location');
		$table[$tr][] = $this->renderHeader('pid', true, true);
		$header = $GLOBALS['LANG']->getLL('cruser_id');
		if ($this->selectedLog == -1) {
			$header .= '<br />'.$this->renderFilterMenu('cruser_id');
		}
		$table[$tr][] = $this->renderHeader('cruser_id', true, true);
		$table[$tr][] = $this->renderHeader('data_var', false, true);

			// Get all the relevant log entries
		$dbres = $this->getLogEntries();

			// If the selection is empty, display a message
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($dbres) == 0) {
			$content .= $this->wrapMessage($GLOBALS['LANG']->getLL('no_entries_found'));
		}
			// Otherwise loop on the results and build table for display
		else {
			$endDate = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
	
					// Memorise start and end date of selected entries
				if (empty($endDate)) $endDate = $row['crdate'];
				$startDate = $row['crdate'];
				
					// Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
				switch ($row['severity']) {
					case 0:
						$severity = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/info.gif','width="18" height="16"').' alt="" />';
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
	
					// If the user who created log entry is the same as the current user,
					// use a darker row background
					// TODO: find an appropriate style in t3skin
				if ($row['cruser_id'] == intval($GLOBALS['BE_USER']->user['uid']))	{
					$tableLayout[$tr]['tr'] = array('<tr class="bgColor4">','</tr>');
				}				
			
				$table[$tr][] = $this->linkLogRun($row['uid'], $row['crmsec']);
				$table[$tr][] = $severity;
				$table[$tr][] = date('d-m-y G:i:s',$row['crdate']);
				$table[$tr][] = $row['extkey'];
				$table[$tr][] = $row['msg'];
				$table[$tr][] = (empty($row['location']) || empty($row['line'])) ? '' : sprintf($GLOBALS['LANG']->getLL('line_call'), $row['location'], $row['line']);
				$table[$tr][] = $this->getPageLink($row['pid']);
				$table[$tr][] = $this->getRecordDetails('be_users', $row['cruser_id']);
				$dataVar = '';
				if (!empty($row['data_var'])) {
					if (strpos($row['data_var'], '"') === 0) {
						$fullData = @unserialize(stripslashes(substr($row['data_var'],1,strlen($row['data_var'])-1)));
					}
					else {
						$fullData = @unserialize($row['data_var']);
					}
					if ($fullData === false) {
						$dataVar = $GLOBALS['LANG']->getLL('extra_data_error');
					}
					else {
						if ($this->MOD_SETTINGS['expandAllExtraData']) {
							$style = '';
							$label = $GLOBALS['LANG']->getLL('hide_extra_data');
							$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/minusbullet_list.gif','width="18" height="12"').' alt="-" />';
						} else {
							$style = ' style="display: none;"';
							$label = $GLOBALS['LANG']->getLL('show_extra_data');
							$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet_list.gif','width="18" height="12"').' alt="+" />';
						}
						$dataVar = '<a href="javascript:toggleExtraData(\''.$row['uid'].'\')" id="debug-link-'.$row['uid'].'" title="'.$label.'">';
						$dataVar .= $icon;
						$dataVar .= '</a>';
						$dataVar .= '<div id="debug-row-'.$row['uid'].'"'.$style.'>'.t3lib_div::view_array($fullData).'</div>';
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
		$replace = '<span style="' . $this->extConf['highlightStyle'] . '">' . $word . '</span>';
		if (function_exists('str_ireplace')) { // If case insensitive replace exists (PHP 5+), use it
			$highlightedContent = str_ireplace($word, $replace, $content);
		}
		else {
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
	 * @param	string	$addCSH: set to true to display CSH in the header
	 * @return	string	HTML to display
	 */
	function renderHeader($field, $addFilter = false, $addCsh = false) {
		$header = $GLOBALS['LANG']->getLL($field);
			// If turned on, add context-sensitive help for header
		if ($addCsh) {
			$header .= $this->renderCsh($field);
		}
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
			if ($i == $this->MOD_SETTINGS['page']) {
				$item = '<strong>' . $text . '</strong>';
			}
			else {
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
		}
		elseif ($this->selectedLog == $latestRun) {
			$latestRun = 0;
			$nextRun = 0;
		}

			// Assemble browse links: oldest, previous, next, latest (if relevant)
		$browser = '';
		if ($oldestRun > 0) $browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('oldest'), $oldestRun);
		if ($previousRun > 0) {
			if (!empty($browser)) $browser .= '&nbsp;&nbsp;';
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('previous'), $previousRun);
		}
		if (!empty($browser)) $browser .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		if ($nextRun > 0) {
			if (!empty($browser)) $browser .= '&nbsp;&nbsp;';
			$browser .= $this->linkLogRun($GLOBALS['LANG']->getLL('next'), $nextRun);
		}
		if ($latestRun > 0) {
			if (!empty($browser)) $browser .= '&nbsp;&nbsp;';
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
			$filter = '<form name="filter'.$filterKey.'" action="" method="GET">';
			$filter .= '<select name="SET[filters]['.$filterKey.']" onchange="this.form.submit()">';
			foreach ($this->filters[$filterKey] as $key => $value) {
				if ((string)$key == (string)$this->selectedFilters[$filterKey]) {
					$selected = ' selected="selected"';
				}
				else {
					$selected = '';
				}
				$filter .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
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
	 * This method displays the clean up screen and performs any clean up action requested
	 *
	 * @return	string	the HTML code to display
	 */
	function cleanupScreen() {
		$content = '<p>'.$GLOBALS['LANG']->getLL('clearlog_intro').'</p>';
		$content .= $this->doc->spacer(20);

			// Act on clear commands
		if ($clearParameters = t3lib_div::_GP('clear')) {
			$where = '';
			if (isset($clearParameters['extension'])) {
				$where = "extkey = '".$clearParameters['extension']."'";
			}
			elseif (isset($clearParameters['period'])) {
				$where = "crdate <= '".$clearParameters['period']."'";
			}
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_devlog', $where);
			$affectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			$content .= $this->wrapMessage(sprintf($GLOBALS['LANG']->getLL('cleared_log'), $affectedRows), 'success');
			$content .= $this->doc->spacer(10);
		}
			// Display delete forms

			// Get total number of log entries
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid) AS total', 'tx_devlog', $where_clause='');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		if ($row['total'] == 0) { // No entries, display a simple message
			$content .= '<p>'.$GLOBALS['LANG']->getLL('no_entries').'</p>';
		}
		else { // Display delete forms only if there's at least one log entry
			$content .= '<p>'.sprintf($GLOBALS['LANG']->getLL('xx_entries'), $row['total']).'</p>';
			$content .= $this->doc->spacer(10);

				// Get list of existing extension keys in the log table
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT extkey', 'tx_devlog', $where_clause='', $groupBy='', $orderBy='extkey ASC');
				// Display form for deleting log entries per extension
			$content .= '<p>'.$GLOBALS['LANG']->getLL('cleanup_for_extension').'</p>';
			$content .= '<form name="cleanExt" action="" method="POST">';
			$content .= '<p><select name="clear[extension]">';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
				$content .= '<option value="'.$row['extkey'].'">'.$row['extkey'].'</option>';
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
			$content .= '</select></p>';
			$content .= '<p><input type="submit" name="clear[cmd]" value="'.$GLOBALS['LANG']->getLL('clearlog').'"></p>';
			$content .= '</form>';
			$content .= $this->doc->spacer(10);

				// Display form for deleting log entries per period
			$content .= '<p>'.$GLOBALS['LANG']->getLL('cleanup_for_period').'</p>';
			$content .= '<form name="cleanPeriod" action="" method="POST">';
			$content .= '<p><select name="clear[period]">';
			foreach ($this->cleanupPeriods as $key => $period) {
				$date = strtotime($period);
				$content .= '<option value="'.$date.'">'.$GLOBALS['LANG']->getLL($key).'</option>';
			}
			$content .= '</select></p>';
			$content .= '<p><input type="submit" name="clear[cmd]" value="'.$GLOBALS['LANG']->getLL('clearlog').'"></p>';
			$content .= '</form>';
			$content .= $this->doc->spacer(10);

				// Display form for deleting all log entries
			$content .= '<p><strong>'.$GLOBALS['LANG']->getLL('cleanup_all').'</strong></p>';
			$content .= '<form name="cleanAll" action="" method="POST">';
			$content .= '<p><input type="submit" name="clear[cmd]" value="'.$GLOBALS['LANG']->getLL('clearalllog').'"></p>';
			$content .= '</form>';
		}
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
	function wrapMessage($string, $type = 'error') {
		switch ($type) {
			case 'success':
				$result = '<p style="padding: 4px; background-color: #0f0;">'.$string.'</p>';
				break;
			case 'warning':
				$result = '<p style="padding: 4px; background-color: #f90;">'.$string.'</p>';
				break;
			default:
				$result = '<p style="padding: 4px; background-color: #f00; color: #fff">'.$string.'</p>';
				break;
		}
		return $result;
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
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT crmsec,crdate', 'tx_devlog', $where_clause='', $groupBy='', $orderBy='crmsec DESC');
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
			if (empty($this->MOD_SETTINGS['page'])) {
				$page = 0;
			}
			else {
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
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT extkey', 'tx_devlog', $where_clause='', $groupBy='', $orderBy='extkey ASC');
		$this->filters['extkey'] = array('*' => '');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
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
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_devlog', 'crmsec < '.$logRun);
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
		$onClick = "devlogWin=window.open('".$url."','devlog','width=790,status=0,menubar=1,resizable=1,location=0,scrollbars=1,toolbar=0');devlogWin.focus();return false;";
		$content = '<a id="openview" href="#" onclick="'.htmlspecialchars($onClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/open_in_new_window.gif','width="19" height="14"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.openInNewWindow',1).'" class="absmiddle" '.$addAttrib.' alt="" />'.
					'</a>';
		return $content;						
	}	

	/**
	 * Assemble the link to select a single log run
	 *
	 * @return	string
	 */
	function linkLogRun($str, $logRun) {		
		$content = '<a href="?SET[logrun]='.$logRun.'">'.$str.'</a>';
		return $content;						
	}

    /**
     * Returns a linked icon with title from a record
     * NOTE: currently this is only called for the pages table, as table names are not stored in the devlog (but a pid may be)
     *
     * @param   string      Table name (tt_content,...)
     * @param   array       Record array
     * @return  string      Rendered icon
     */
    function getPageLink($uid) {
        global $BACK_PATH;
		if (empty($uid)) {
			return '';
		}
		else {
				// Retrieve the stored page information
				// (pages were already fetched in getLogFilters)
			$row = $this->records['pages'][$uid];
			$iconAltText = t3lib_BEfunc::getRecordIconAltText($row, 'pages');
	
				// Create icon for record
			$elementIcon = t3lib_iconworks::getIconImage('pages', $row, $BACK_PATH, 'class="c-recicon" title="'.$iconAltText.'"');
	
				// Return item with edit link
			$editOnClick = 'top.loadEditId('.$uid.')';
			$string = '<a href="#" onclick="'.htmlspecialchars($editOnClick).'">'.$elementIcon.$row['t3lib_BEfunc::title'].'</a>';
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
		}
		else {
			if (isset($this->records[$table][$uid])) {
				$row = $this->records[$table][$uid];
			}
			else {
				$row = t3lib_BEfunc::getRecord($table, $uid);
			}
	        $iconAltText = t3lib_BEfunc::getRecordIconAltText($row, $table);
            $elementTitle = t3lib_BEfunc::getRecordTitle($table, $row, 1);
	        $elementIcon = t3lib_iconworks::getIconImage($table, $row, $BACK_PATH, 'class="c-recicon" title="'.$iconAltText.'"');
			return $elementIcon.$elementTitle;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_devlog_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
