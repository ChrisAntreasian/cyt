<?php

// cookie hack used to persist sessions
if(isset($_COOKIE['session_id'])) {
    session_id($_COOKIE['session_id']);
}
session_start();
if(!isset($_COOKIE['session_id'])) {
    setcookie('session_id', session_id(), 0, '/', 'cyt.gigamegaultra.com');
}

// initalize the google client for the google client
$client = new Google_Client();

// $client->setDeveloperKey( API_KEY );
$client->setDeveloperKey( API_KEY );
$client->setClientId( CLIENT_ID );
$client->setClientSecret( CLIENT_SECRET );
$client->setScopes('https://www.googleapis.com/auth/youtube.force-ssl');
$redirect = filter_var( 'http://cyt.gigamegaultra.com/dashboard.php', FILTER_SANITIZE_URL );
$client->setRedirectUri($redirect);
