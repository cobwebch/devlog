<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_devlog_domain_model_entry');

// Add context sensitive help (csh) to the backend module and to the tx_devlog table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_devlog_domain_model_entry',
    'EXT:devlog/Resources/Private/Language/locallang_csh_txdevlog.xlf'
);

$pathToExtension = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('devlog');
// Register the extension's icons
$icons = array(
    'entry' => $pathToExtension . 'Resources/Public/Icons/Entry.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, 'devlog');
