<?php
/**
 * Author: Janzen Zarzoso
 * Date: 8/30/16
 * Time: 6:07 PM
 */

require_once __DIR__ . '/vendor/autoload.php';

const CLIENT_ID = '';  // Client ID Provided by Asana
const CLIENT_SECRET = '';  // Client Secret provided by Asana
const REDIRECT_URL = ''; // URL to be used for redirection

$client = Asana\Client::oauth(array(
    'client_id'     => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'redirect_uri'  => REDIRECT_URL,
));

if ($_GET['state'] == 'PH') {
    $token = $client->dispatcher->fetchToken($_GET['code']);
    echo $token;
    // ...
} else {
    // error! possible CSRF attack
}
