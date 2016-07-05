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

use TYPO3\CMS\Backend\Form\Element\UserElement;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Custom fields for tx_devlog_domain_model_entry table.
 */
class UserFields
{

    /**
     * @var string
     */
    protected $extKey = 'devlog';

    /**
     * Returns the severity of the entry using TYPO3's icon and labels.
     *
     * @param array $parameters Information related to the field
     * @param UserElement $userElement Reference to calling object
     *
     * @return string The HTML for the form field
     */
    public function displaySeverity($parameters, $formObject)
    {
        // Translate severity to icon "name"
        $severity = $parameters['row']['severity'];
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

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $html = $iconFactory->getIcon(
                'status-dialog-' . $severityName,
                Icon::SIZE_DEFAULT
        );
        $html .= $this->getLanguageObject()->sL('LLL:EXT:devlog/Resources/Private/Language/locallang.xlf:status_' . $severityName);
        return $html;
    }

    /**
     * Returns the extra data as a nice HTML dump.
     *
     * @param array $PA Information related to the field
     * @param UserElement $userElement Reference to calling object
     *
     * @return string The HTML for the form field
     */
    public function displayExtraData($PA, $formObject)
    {
        if (empty($PA['row']['extra_data'])) {
            $html = $this->getLanguageObject()->sL('LLL:EXT:devlog/Resources/Private/Language/locallang.xlf:no_extra_data');
        } else {
            $data = unserialize(gzuncompress($PA['row']['extra_data']));
            $html = \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(
                    $data,
                    null,
                    10,
                    false,
                    true,
                    true
            );
        }
        return $html;
    }

    /**
     * Wrapper around the global language object.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageObject()
    {
        return $GLOBALS['LANG'];
    }
}
