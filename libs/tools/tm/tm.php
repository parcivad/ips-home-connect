<?php
// define data.json
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/tm/data.json" ), true );
require_once( dirname(dirname(__FILE__) ) . "/HomeConnectApi.php");

/** Function to open urls in browser
 * @param string $url Url to open in the browser
 */
function open( $url ) {
    $os = strtoupper(substr(PHP_OS, 0, 3));

    // location running OS
    switch ($os) {
        case "DAR":
            shell_exec('open "' . $url . '"');
            break;
        case "LIN":
            shell_exec('xdg-open  "' . $url . '"');
            break;
        case "WIN":
            shell_exec('start  "' . $url . '"');
            break;
    }
}

/*================================= GETTER PART =================================*/
function getAuthorizeCode() {
    global $data;
    return $data["authorize"]["code"];
}

function getAccessToken() {
    global $data;
    return $data["token"]["access_token"];
}

function getRefreshToken() {
    global $data;
    return $data["token"]["refresh_token"];
}

function getIdToken() {
    global $data;
    return $data["token"]["id_token"];
}

function getLastTokenCall() {
    global $data;
    return $data["token"]["last_token_call"];
}

function getTokenType() {
    global $data;
    return $data["token"]["token_type"];
}

function getExpiresIn() {
    global $data;
    return $data["token"]["expires_in"];
}

function getScopes() {
    global $data;
    return $data["token"]["scope"];
}
/*============================================================================*/

/** Function to write the data.json
 * @param string $rewrite var to rewrite
 */
function write( $rewrite ) {
    // rewrite file
    file_put_contents(dirname(dirname(__FILE__) ) . "/tm/data.json", json_encode( $rewrite ));
}

/**
 * Function to reset all Data
 */
function resetData() {

    $json["token"]["access_token"] = null;
    $json["token"]["refresh_token"] = null;
    $json["token"]["expires_in"] = null;
    $json["token"]["id_token"] = null;
    $json["token"]["token_type"] = null;
    $json["token"]["scope"] = null;
    $json["token"]["last_token_call"] = null;

    $json["authorize"]["code"] = null;

    write($json);
}

/**
 * @param string $url Url of the used api
 * @param string $client_id client_id of your client
 * @param string $scopes scopes to ask
 * @return false|string|string[] Code or nothing after Server start
 */
function authorize( $url, $client_id, $scopes ) {

    //================= Url build ===================
    $params_array = [
        "response_type" => "code",
        "client_id" => $client_id,
        "scope" => $scopes,
        "redirect_uri" => "http://localhost:8080",
    ];

    $params = http_build_query($params_array);

    $fullUrl = $url . "?" . $params;
    //=============================================

    open($fullUrl);

    // start php server for authorization
    $cmd = 'cd "' . dirname(dirname(__FILE__) ) . '/tm' . '" && php -S 127.0.0.1:8080 incoming.php';
    shell_exec("$cmd");

    return false;
}

/** Function to get token from your api
 * @param string $url Url of your api
 * @param string $client_id your client_id
 * @param string $client_secret client_secret of the client
 * @return mixed Return the access_token
 */
function getToken( $url, $client_id, $client_secret ) {
    global $data;

    if ( is_string($data["token"]["refresh_token"]) ) {

        $distance = time() - $data["token"]["last_token_call"];
        $limit = $data["token"]["expires_in"] - 3600;

        if ( $distance >= $limit ) {
            return refreshToken( $url, $client_id, $client_secret, "");
        }
        return $data["token"]["access_token"];
    }

    // Check if there is a Authorization code
    if ( is_string( $data["authorize"]["code"] ) )  {

        //================= Url build ===================
        $params_array = [
            "Content-Type" => "application/x-www-form-urlencoded",
            "grant_type" => "authorization_code",
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri" => "http://localhost:8080",
            "code" => $data["authorize"]["code"]
        ];

        $params = http_build_query($params_array);

        $fullUrl = $url . "?" . $params;
        //============================================

        // configure curl curl options in array
        $curlopt = array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
        );

        //================= Curl =======================================
        $ch = curl_init();
        curl_setopt_array($ch, $curlopt);
        // ask
        $response = curl_exec($ch);
        // response url with token etc.
        curl_close($ch);
        //==============================================================

        $query = json_decode($response, true);

        // If the token is present, else send error
        // You have to ask two options (each api is different in error return)
        if ( !isset($query["error"]) && !isset($query["status"]) ) {
            $json = $data;

            $json["token"]["access_token"] = $query["access_token"];
            $json["token"]["refresh_token"] = str_replace( "=", "", urldecode( $query["refresh_token"] ));
            $json["token"]["id_token"] = $query["id_token"];
            $json["token"]["expires_in"] = $query["expires_in"];

            $json["token"]["token_type"] = $query["token_type"];
            $json["token"]["scope"] = $query["scope"];
            $json["token"]["last_token_call"] = time();

            write( $json );

            return $query["access_token"];
        } else {
            // Throw error
            if ( isset($query["error"])) {
                throw new UnexpectedValueException("Error from your api: " . $query["error_description"]);
            } else {
                throw new UnexpectedValueException("Error from your api: " . $query["message"]);
            }
        }
    } else {
        // Throw simple error
        throw new UnexpectedValueException("No Authorization code present [First authorize then ask token]");
    }
}

/** Function to get a new token (refresh) from the api
 * @param string $url Url of your api
 * @param string $client_id your client_id
 * @param string $client_secret client_secret of your client_id
 * @param string $scope Can be used to ask new permissions
 * @return mixed return token
 */
function refreshToken( $url, $client_id, $client_secret, $scope ) {
    global $data;

    // Check if there is a Authorization code
    if ( is_string( $data["authorize"]["code"] ) ) {

        //================= Url build ===================
        $params_array = [
            "Content-Type" => "application/x-www-form-urlencoded",
            "grant_type" => "refresh_token",
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri" => "http://localhost:8080",
            "refresh_token" => getRefreshToken(),
            "scope" => $scope
        ];

        $params = http_build_query($params_array);

        $fullUrl = $url . "?" . $params;
        //============================================

        // configure curl curl options in array
        $curlopt = array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
        );

        //================= Curl =======================================
        $ch = curl_init();
        curl_setopt_array($ch, $curlopt);
        // ask
        $response = curl_exec($ch);
        // response url with token etc.
        curl_close($ch);
        //==============================================================

        $query = json_decode($response, true);

        // If the token is present, else send error
        // You have to ask two options (each api is different in error return)
        if ( !isset($query["error"]) && !isset($query["status"]) ) {
            $json = $data;

            $json["token"]["access_token"] = $query["access_token"];
            $json["token"]["refresh_token"] = str_replace( "=", "", urldecode( $query["refresh_token"] ));
            $json["token"]["id_token"] = $query["id_token"];
            $json["token"]["expires_in"] = $query["expires_in"];

            $json["token"]["token_type"] = $query["token_type"];
            $json["token"]["scope"] = $query["scope"];
            $json["token"]["last_token_call"] = time();

            write( $json );

            return $query["access_token"];
        } else {
            // Throw error
            if ( isset($query["error"])) {
                throw new UnexpectedValueException("Error from your api: " . $query["error_description"]);
            } else {
                throw new UnexpectedValueException("Error from your api: " . $query["message"]);
            }
        }
    } else {
        // Throw simple error
        throw new UnexpectedValueException("No Authorization code present [First authorize then ask token]");
    }
}