<?php

use DachcomDigital\SocialDataFacebook\Controller\FacebookConnectController;

return [
    'socialdata_facebook_connect' => [
        'path' => '/social-data/connector/facebook/connect',
        'access' => 'public',
        'target' => FacebookConnectController::class . '::connect',
    ],
    'socialdata_facebook_disconnect' => [
        'path' => '/social-data/connector/facebook/disconnect',
        'access' => 'public',
        'target' => FacebookConnectController::class . '::disconnect',
    ],
    'socialdata_facebook_debugtoken' => [
        'path' => '/social-data/connector/facebook/debug-token',
        'access' => 'public',
        'target' => FacebookConnectController::class . '::debugToken',
    ]
];
