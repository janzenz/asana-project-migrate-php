<?php
/**
 * Author: Janzen Zarzoso
 * Date: 8/30/16
 * Time: 6:09 PM
 */

require_once __DIR__ . '/vendor/autoload.php';

const CLIENT_ID = '';  // Client ID Provided by Asana
const CLIENT_SECRET = '';  // Client Secret provided by Asana
const REDIRECT_URL = ''; // URL to be used for redirection
const PROJECT_ID_FROM = ''; // Project to be copied
const PROJECT_ID_TO  = ''; // Project to be copied to

$client = Asana\Client::oauth(array(
    'client_id'     => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'redirect_uri'  => REDIRECT_URL,
));

$state = 'PH';
$url = $client->dispatcher->authorizationUrl($state);

if (isset($_GET['state']) && $_GET['state'] == 'PH') {
    $token = $client->dispatcher->fetchToken($_GET['code']);

    $asanaApi = new AsanaAPI($token, $projectIdFrom);
    $asanaApi->copyProjectTo($projectIdTo);

} else {
    header('Location:' . $url);
}