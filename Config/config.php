<?php

declare(strict_types=1);

return [
    'name'        => 'Bulk Mailer',
    'description' => 'Send a template email to an ad-hoc recipient list (CSV/Excel) repeatedly, with per-contact logging.',
    'version'     => '0.1.0',
    'author'      => 'acolono GmbH',

    'routes' => [
        'main' => [
            'mautic_bulkmailer_index' => [
                'path'       => '/bulk-mailer',
                'controller' => 'MauticPlugin\AcolonoBulkMailerBundle\Controller\BulkMailerController::indexAction',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'items' => [
                'mautic.bulkmailer.menu.index' => [
                    'route'     => 'mautic_bulkmailer_index',
                    // Reuse the existing "view emails" permission so no custom
                    // permission bundle is required for the MVP.
                    'access'    => 'email:emails:view',
                    'iconClass' => 'ri-mail-send-line',
                    'priority'  => 60,
                ],
            ],
        ],
    ],
];
