<?php
/*
 * UWAGA: To jest skrypt CLI!
 * Uruchom go pod konsolą za pomocą polecenia: php device_flow.php
 */

define('CLIENT_ID', ''); // pamietaj aby zarejestrowac klienta typu DEVICE
define('CLIENT_SECRET', '');
define('API_HOST', 'https://api.allegro.pl/');
define('AUTH_URL', 'https://allegro.pl/auth/oauth/');

$headers = [
    'Authorization: Basic ' . base64_encode(CLIENT_ID.':'.CLIENT_SECRET),
    'Content-Type: application/x-www-form-urlencoded'
];
// pobierz user_code, device_code, verification_uri oraz verification_uri_complete
$result = json_decode(doRequest(AUTH_URL . 'device', $headers, true, ['client_id' => CLIENT_ID]));

if (isset($result->error)) {
    throw new \Exception('Nie udało się pobrać device_code. Błąd: ' . $result->error .', '.$result->error_description);
} else {
    echo "Szanowny użytkowniku, proszę ja Cię, otwórz ten oto adres w przeglądarce na jakimś urządzeniu: \n" . $result->verification_uri_complete;

    // zacznij odpytywac auth server o access token co "interval" sekund
    $accessToken = false;
    $interval = (int)$result->interval;
    do {
        sleep($interval);
        
        // pobierz access token
        $url = 'token?grant_type=urn:ietf:params:oauth:grant-type:device_code&device_code=' . $result->device_code;
        $resultAccessToken = json_decode(doRequest(AUTH_URL . $url, $headers, true));

        if (isset($resultAccessToken->error)) {
            if ($resultAccessToken->error == 'access_denied') {
                break; // brak dostepu, koniec odpytywania
            } elseif ($resultAccessToken->error == 'slow_down') {
                $interval++; // dodaj jedna sekunde extra, bo za czesto pytamy
            }
        } else {
            $accessToken = $resultAccessToken->access_token;
        }
    } while ($accessToken === false);
}

// pozyskalismy access token wiec mozna odpytac API o zasoby
if ($accessToken) {
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/vnd.allegro.public.v1+json'
    ];
    // pobierz liste ofert dla kategorii id=7
    $result = json_decode(doRequest(API_HOST.'offers/listing?category.id=7', $headers));
    // pobrane wyniki sa w zmiennej $result
    var_dump($result);
    // ...
}

function doRequest(string $url, array $headers = [], bool $postMethod = false, array $postData = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($postMethod) {
        curl_setopt($ch, CURLOPT_POST, $postMethod);

        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData, '', '&'));
        }
    }

    $output = curl_exec($ch);

    if ($output == false) {
        print_r('Curl error: ' . curl_error($ch));
        curl_close($ch);
    } else {
        curl_close($ch);
        return $output;
    }
}
?>
