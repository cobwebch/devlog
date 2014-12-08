<?php
namespace Devlog\Devlog\Utility;

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

use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * TCEforms custom field for devlog table.
 */
class TceForms {

	protected $extKey = 'devlog';

	/**
	 * Returns the severity of the entry using TYPO3's icon and labels
	 *
	 * @param array $PA Information related to the field
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formObject Reference to calling TCEforms object
	 *
	 * @return string The HTML for the form field
	 */
	public function displaySeverity($PA, $formObject) {
		// Translate severity to icon "name"
		$severity = $PA['row']['severity'];
		switch ($severity) {
			case -1:
				$severityName = 'ok';
				break;
			case 1:
				$severityName = 'notification';
				break;
			case 2:
				$severityName = 'warning';
				break;
			case 3:
				$severityName = 'error';
				break;
			default:
				$severityName = 'information';
		}

		$html = IconUtility::getSpriteIcon(
			'status-dialog-' . $severityName,
			array(
				'title' => $this->getLanguageObject()->sL('LLL:EXT:devlog/Resources/Private/Language/locallang.xlf:severity_' . $severityName)
			)
		);
		return $html;
	}

	/**
	 * Returns the extra data as a nice HTML dump.
	 *
	 * @param array $PA Information related to the field
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formObject Reference to calling TCEforms object
	 *
	 * @return string The HTML for the form field
	 */
	public function displayExtraData($PA, $formObject) {
		if (empty($PA['row']['extra_data'])) {
			$html = $this->getLanguageObject()->sL('LLL:EXT:devlog/Resources/Private/Language/locallang.xlf:no_extra_data');
		} else {
			$data = unserialize(gzuncompress($PA['row']['extra_data']));
			$html = \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(
				$data,
				NULL,
				10,
				FALSE,
				TRUE,
				TRUE
			);
		}
		return $html;
	}

	/**
	 * Wrapper around the global language object.
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageObject() {
		return $GLOBALS['LANG'];
	}
}
