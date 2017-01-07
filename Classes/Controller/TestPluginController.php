<?php
namespace Devlog\Devlog\Controller;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Dummy plugin controller, which generates test devlog entries.
 *
 * @package Devlog\Devlog\Controller
 */
class TestPluginController extends ActionController
{
    /**
     * Writes log entries and outputs confirmation sentence.
     *
     * @return void
     */
    public function indexAction()
    {
        GeneralUtility::devLog('Empty data', 'devlog', 1);
        GeneralUtility::devLog('Logging object test', 'devlog', 0, $this);
        GeneralUtility::devLog('Logging test', 'devlog', -1, $this->settings);
        GeneralUtility::devLog('Logging test', 'devlog', 0, $this->settings);
        GeneralUtility::devLog('Logging test', 'devlog', 1, $this->settings);
        GeneralUtility::devLog('Logging test', 'devlog', 2, $this->settings);
        GeneralUtility::devLog('Logging test', 'devlog', 3, $this->settings);
        GeneralUtility::devLog('Escaping>=special "characters"', 'devlog', 1, 'Special characters: < > & " \'');
        $htmlObject = new \stdClass();
        $htmlObject->html = '<p>This is some HTML content, with <strong>wrong</strong> markups.</td></p>';
        GeneralUtility::devLog('Logging <strong>HTML</strong>', '<b>devlog</b>', 3, $htmlObject);
    }
}