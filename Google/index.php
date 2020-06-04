<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

function getClient()
{
    $client = new Google_Client();

    $client->setApplicationName('YourSpace Event Creator');

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

             // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }

            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            // Generate new token seperate to this application and then place them in token.json
            echo "Access Token and Refresh Token invalid, refresh them";
        }  
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

$opts = array('conferenceDataVersion' => true);

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