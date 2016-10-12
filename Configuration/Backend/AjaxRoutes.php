<?php

return [
        'tx_devlog_list' => [
                'path' => '/devlog/list/get',
                'target' => \Devlog\Devlog\Controller\ListModuleController::class . '::getAllAction'
        ],
        'tx_devlog_reload' => [
                'path' => '/devlog/list/reload',
                'target' => \Devlog\Devlog\Controller\ListModuleController::class . '::getNewAction'
        ],
        'tx_devlog_count' => [
                'path' => '/devlog/list/count',
                'target' => \Devlog\Devlog\Controller\ListModuleController::class . '::getCountAction'
        ]
];
