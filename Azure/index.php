<?php
        require_once(__DIR__ . '/vendor/autoload.php');
        use Symfony\Component\Dotenv\Dotenv;


        // Use $_ENV to retrieve variables as getenv() doesn't work
        $dotenv = new Dotenv();;
        $dotenv->load(__DIR__ . '/.env');

        // Getting authorization code to then get tokens
        function getCode() {

            $params = "?client_id=" . $_ENV['OAUTH_APP_ID'] .
            "&client_secret=" . $_ENV['OAUTH_APP_PASSWORD'] .
            "&response_type=code" .
            "&scope=openid profile offline_access user.read calendars.read";
            $url = $_ENV['url'] . $_ENV['tenent_id'] . $_ENV['OAUTH_AUTHORIZE_ENDPOINT'] . $params;

            $ch = curl_init();

            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, false);
            //curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //echo $url;

            curl_exec($ch);

            // Code gets returned to callback route
            //curl_close($ch);
        }

        // Getting tokens to save to DB
        function getTokens($code) {
            $params = "?scope=openid profile offline_access user.read calendars.read";
            $data = "grant_type=authorization_code&client_secret=" . $_ENV['OAUTH_APP_PASSWORD'] . "&code=$code&client_id=" . $_ENV['OAUTH_APP_ID'];
            $url = $_ENV['url'] . $_ENV['tenent_id'] . $_ENV['OAUTH_TOKEN_ENDPOINT'];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result =  json_decode(curl_exec($ch), true);

            saveToDB($result["refresh_token"], $result["access_token"]);
            curl_close($ch);
        }

        function saveToDB($refreshToken, $accessToken) {
            //echo "Saving Tokens to DB: <br> RefreshToken: $refreshToken <br> AccessToken: $accessToken";


            // Testing calendar
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://graph.microsoft.com/v1.0/me/events?$select=subject,body,bodyPreview,organizer,attendees,start,end,location');

            // Returns the data/output as a string instead of raw data
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //Set your auth headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
            ));

            $result =  curl_exec($ch);
            //echo "<script>console. log('$result');</script>";
            //$result = json_encode($result, JSON_PRETTY_PRINT);
            print_r($result);
        }

        // Start chain of events
        getCode();
?>