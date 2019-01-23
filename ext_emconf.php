<?php

########################################################################
# Extension Manager/Repository config file for ext: "devlog"
#
# Auto generated 21-12-2009 22:35
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = [
        'title' => 'Developer\'s Log',
        'description' => 'The Developer\'s Log extension provides development logging/debugging functionality for usage of GeneralUtility::devlog() and a BE module to browse the logs.',
        'category' => 'misc',
        'priority' => '',
        'loadOrder' => '',
        'module' => 'mod1',
        'state' => 'stable',
        'uploadfolder' => 0,
        'createDirs' => '',
        'modify_tables' => '',
        'clearCacheOnLoad' => 1,
        'lockType' => '',
        'author' => 'Francois Suter',
        'author_email' => 'typo3@cobweb.ch',
        'author_company' => '',
        'version' => '3.0.4',
        'constraints' => [
                'depends' => [
                        'typo3' => '7.6.0-8.9.99',
                ],
                'conflicts' => [
                ],
                'suggests' => [
                ],
        ],
];