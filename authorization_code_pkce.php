<?php
session_start();

define('CLIENT_ID', '');
define('CLIENT_SECRET', '');
define('REDIRECT_URI', '');
define('API_HOST', 'https://api.allegro.pl/');
define('AUTH_URL', 'https://allegro.pl/auth/oauth/');
define('CODE_VERIFIER', '');

$codeChallenge = base64url_encode(hash('sha256', CODE_VERIFIER, true));

$authUrlButton = AUTH_URL . 'authorize?response_type=code&client_id=' . CLIENT_ID . '&redirect_uri=' . REDIRECT_URI .
    '&code_challenge_method=S256&code_challenge=' . $codeChallenge . '&prompt=confirm';

if (isset($_SESSION['access_token_pkce'])) {
    // pobierz liste ofert dla kategorii id=7
    $headers = [
        'Authorization: Bearer ' . $_SESSION['access_token_pkce'],
        'Accept: application/vnd.allegro.public.v1+json'
    ];
    $result = json_decode(doRequest(API_HOST.'offers/listing?category.id=7', $headers));
    // pobrane wyniki sa w zmiennej $result
    var_dump($result);
    // ...
    exit;
}

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $accessTokenURL = AUTH_URL . 'token?grant_type=authorization_code&code=' . $code . '&redirect_uri=' . REDIRECT_URI
        . '&code_verifier=' . CODE_VERIFIER;

    $result = json_decode(doRequest($accessTokenURL));

    if (isset($result->access_token)) {
        $_SESSION['access_token_pkce'] = $result->access_token;

        header('Location: /pkce.php');
        exit;
    } else {
        throw new \Exception('Brak access tokena');
    }
}
?>

<html>
<body>
  <a href="<?php echo $authUrlButton; ?>">Zaloguj do Allegro</a>
</body>
</html>

<?php
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
