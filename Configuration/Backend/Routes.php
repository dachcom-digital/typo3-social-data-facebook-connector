<?php

use DachcomDigital\SocialDataFacebook\Controller\FacebookConnectController;

return [
    'socialdata_facebook_connect_callback' => [
        'path' => '/social-data/connector/facebook/connect-callback',
        'access' => 'public',
        'target' => FacebookConnectController::class . '::connectCallback',
    ]
];
