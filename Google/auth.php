<?php

error_reporting(E_ALL);

ini_set('display_errors', 1);

// If you've used composer to include the library
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();

    $client->setApplicationName('Calendar API Test');

    //$client->setScopes( ['https://www.googleapis.com/auth/calendar'] );
    $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);

    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // and refresh tokens, and is created automatically when the authorization flow completes for the first time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';

            // Check Param on redirected URL, for ?code=#############  
            // you have to copy only ?code= $_GET parms data and paste in console
            $authCode = trim(fgets(STDIN)); // Get code after Authentication

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }

        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }

        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

$client = getClient();

$accessToken = $client->getAccessToken();

$service = new Google_Service_Calendar($client);

$event = new Google_Service_Calendar_Event(array(
  'summary' => 'Test Event',
  'location' => 'Online',
  'description' => 'Test Event: More Info',
  'start' => array(
    'dateTime' => '2020-06-04T18:00:00',
    'timeZone' => 'Europe/Dublin',
  ),
  'end' => array(
    'dateTime' => '2020-06-04T19:00:00',
    'timeZone' => 'Europe/Dublin',
  ),
));

$calendarId = 'primary';

$opts = array('sendNotifications' => true, 'conferenceDataVersion' => true); // send Notification immediately by Mail or Stop Hangout Call Link

$event = $service->events->insert($calendarId, $event, $opts ); 

printf('Event created: %s\n', $event->htmlLink);

$conference = new \Google_Service_Calendar_ConferenceData();
$conferenceRequest = new \Google_Service_Calendar_CreateConferenceRequest();
$conferenceRequest->setRequestId('randomString123');
$conference->setCreateRequest($conferenceRequest);
$event->setConferenceData($conference);

$event = $service->events->patch($calendarId, $event->id, $event, ['conferenceDataVersion' => 1]);

printf('<br>Conference created: %s', $event->hangoutLink);

?>