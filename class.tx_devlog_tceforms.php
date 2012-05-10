<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Fabien Udriot <fabien.udriot@ecodev.ch>
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
* $Id: class.tx_devlog_tceforms.php 13682 2008-11-03 13:33:42Z omic $
***************************************************************/

/**
 * TCEform custom field for devlog
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package	TYPO3
 * @subpackage	tx_devlog
 */
class tx_devlog_tceforms {

	protected $extKey = 'devlog';

	/**
	 * This method returns the message's content
	 * HTML should be display as is
	 *
	 * @param	array			$PA: information related to the field
	 * @param	t3lib_tceforms	$fobj: reference to calling TCEforms object
	 *
	 * @return	string	The HTML for the form field
	 */
	public function displayMessage($PA, $fobj) {
		return $PA['row']['msg'];
	}

	/**
	 * This method returns the severity of the entry using TYPO3's icon and labels
	 *
	 * @param	array			$PA: information related to the field
	 * @param	t3lib_tceforms	$fobj: reference to calling TCEforms object
	 *
	 * @return	string	The HTML for the form field
	 */
	public function displaySeverity($PA, $fobj) {
		$html = '';
		$this->doc = t3lib_div::makeInstance('template');
		$severity = $PA['row']['severity'];

			// Gets the image
		if ($severity == 0) {
			$html .= '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/info.gif','width="18" height="16" alt="pictogram"').' alt="" />';
		}
		else {
			$html .= $this->doc->icons($severity);
		}

			// Change severity for the label
		if ($severity == -1) {
			$severity = 4;
		}

		$html .= ' <span style="font-weight: bold; margin-left: 5px">';
		$html .= $GLOBALS['LANG']->sL('LLL:EXT:devlog/locallang_db.xml:tx_devlog.severity.I.' . $severity);
		$html .= '</span>';
		return $html;
	}

	/**
	 * This method returns the additional data's content as HTML structure
	 *
	 * @param	array			$PA: information related to the field
	 * @param	t3lib_tceforms	$fobj: reference to calling TCEforms object
	 *
	 * @return	string	The HTML for the form field
	 */
	public function displayAdditionalData($PA, $fobj) {
		if (empty($PA['row']['data_var'])) {
			$html = $GLOBALS['LANG']->sL('LLL:EXT:devlog/locallang_db.xml:tx_devlog.no_extra_data');
		}
		else {
			$data = unserialize($PA['row']['data_var']);
			$html = $this->debugArray($data);
		}
		return $html;
	}


	/**
	 * Prints the debug output of an array
	 *
	 * Compatibility wrapper for obsolete Core methods
	 *
	 * @param array $array Array to output
	 * @return string
	 */
	protected function debugArray($array) {
		if (class_exists('t3lib_utility_Debug')) {
			return t3lib_utility_Debug::viewArray($array);
		} else {
			t3lib_div::view_array($array);
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/class.tx_devlog_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/devlog/class.tx_devlog_tceforms.php']);
}

?>