<?php

require_once './config.php';

require_once 'Google/autoload.php';
require_once 'Google/Client.php';

require_once './initalize.php';

function requestAuthorization($client) {
    // If the user hasn't authorized the app, initiate the OAuth flow
    $state = $_SESSION['state'] ? $_SESSION['state'] : mt_rand();
    $_SESSION['state'] = $state;

    $client->setState($state);
    $authUrl = $client->createAuthUrl();
    
    print_r( $_SESSION );
    
    echo '<a href="' . $authUrl . '">authorise access</a><br />';

}

if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
}

if ( !$client->getAccessToken() ) {
    requestAuthorization($client);
}