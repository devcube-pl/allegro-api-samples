<?php
session_start();

define('CLIENT_ID', '');
define('CLIENT_SECRET', '');
define('API_HOST', 'https://api.allegro.pl/');
define('AUTH_URL', 'https://allegro.pl/auth/oauth/');

if (!isset($_SESSION['api_access_token_cc'])) {
    $accessTokenURL = AUTH_URL . 'token?grant_type=client_credentials';
    $headers = [
        'Authorization: Basic ' . base64_encode(CLIENT_ID.':'.CLIENT_SECRET)
    ];
    $result = json_decode(doRequest($accessTokenURL, $headers));

    if (isset($result->access_token)) {
        $_SESSION['api_access_token_cc'] = $result->access_token;
    } else {
        throw new \Exception('Brak access tokena');
    }
}

if (isset($_SESSION['api_access_token_cc'])) {
    // pobierz liste ofert dla kategorii id=7
    $headers = [
        'Authorization: Bearer ' . $_SESSION['api_access_token_cc'],
        'Accept: application/vnd.allegro.public.v1+json'
    ];
    $result = json_decode(doRequest(API_HOST.'offers/listing?category.id=7', $headers));
    // pobrane wyniki sa w zmiennej $result
    var_dump($result);
    // ...
}

function doRequest(string $url, array $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
