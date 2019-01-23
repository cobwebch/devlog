<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_devlog_domain_model_entry');

// Add context sensitive help (csh) to the devlog table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_devlog_domain_model_entry',
        'EXT:devlog/Resources/Private/Language/locallang_csh_txdevlog.xlf'
);

// Load the module only in the BE context
if (TYPO3_MODE === 'BE') {
    // Register the "Data Import" backend module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Devlog.Devlog',
            // Make it a submodule of 'ExternalImport'
            'system',
            // Submodule key
            'devlog',
            // Position
            'after:BelogLog',
            array(
                // An array holding the controller-action-combinations that are accessible
                'ListModule' => 'index,delete'
            ),
            array(
                    'access' => 'admin',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/ModuleIcon.svg',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/Module.xlf'
            )
    );
}

// Register test plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        $_EXTKEY,
        'TestPlugin',
        'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:test_plugin',
        'EXT:' . $_EXTKEY . '/Resources/Public/Images/ModuleIcon.svg'
);

// Register sprite icons for loading spinner
/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
        'tx_devlog-loader',
        \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
        [
                'name' => 'spinner',
                'spinning' => true
        ]
);
