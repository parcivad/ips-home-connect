<?php
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/tools/tm/data.json" ), true );

/**
 * @param $endpoint String Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api($endpoint="") {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex);
    }

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'content-type: application/vnd.bsh.sdk.v1+json',
        'Authorization: Bearer ' . $token
    );
    // build url
    $url = "https://api.home-connect.com/api/" . $endpoint;
    //-------------------------------------------------------------------------------------

    // configure curl curl options in array
    $curlopt = array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => $header_array,
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

    $response_json = json_decode( $response, true );

    // Catch error
    if ( isset( $response_json['error']) ) throw new Exception($response_json['error']['key']);

    // In case of token error [api can cancel the token every time]
    return $response_json;
}

/**
 * @param string $endpoint Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api_delete(string $endpoint ) {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex);
    }

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'Authorization: Bearer ' . $token
    );
    // build url
    $url = "https://api.home-connect.com/api/" . $endpoint;
    //-------------------------------------------------------------------------------------

    // configure curl curl options in array
    $curlopt = array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $header_array,
        CURLOPT_CUSTOMREQUEST => "DELETE",
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

    return null;
}

/**
 * @param string $endpoint Sending this command to the Api of HomeConnect
 * @param string $json Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api_put(string $endpoint, string $json ) {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex);
    }

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'content-type: application/vnd.bsh.sdk.v1+json',
        'Authorization: Bearer ' . $token
    );
    // build url
    $url = "https://api.home-connect.com/api/" . $endpoint;
    //-------------------------------------------------------------------------------------

    // configure curl curl options in array
    $curlopt = array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $header_array,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $json,
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

    $response_json = json_decode( $response, true );

    // Catch error
    if ( isset( $response_json['error']) ) throw new Exception($response_json['error']['key']);

    // return api call
    return $response_json;
}

/** Function to translate the mode into a readable string
 * @param string $ModeName The abstract or readable string
 * @param bool $direction TRUE =  HC name => Readable string  //   FALSE = Readable string => HC name
 * @return string
 */
function DishwasherTranslateMode( string $ModeName, bool $direction) {
    $dictionary = array(
        "PreRinse" => "Vorspülen",
        "Auto1" => "Auto sanft",
        "Auto2" => "Auto",
        "Auto3" => "Auto hart",
        "Eco50" => "Eco 50°C",
        "Quick45" => "Schnell 45°C",
        "Quick65" => "Schnell 65°C",
        "Intensiv45" => "Intensiv 45°C",
        "Intensiv70" => "Intensiv 70°C",
        "Normal45" => "Normal 45°C",
        "Normal65" => "Normal 65°C",
        "Glas40" => "Glässer 40°C",
        "NightWash" => "Ruhemodus",
        "AutoHalfLoad" => "Auto halb voll",
        "IntensivPower" => "Intensiv stark",
        "MagicDaily" => "Tägliches waschen",
        "Kurz60" => "Kurz 60°C",
        "Super60" => "Super 60°C",
        "ExpressSparkle65" => "Extra sauber 65°C",
        "MachineCare" => "Maschinen Säuberung",
        "SteamFresh" => "Extra trocken",
        "MaximumCleaning" => "Extra sauber"
    );

    // Translate HC NAME => READABLE STRING
    if ( $direction ) { return $dictionary[$ModeName]; }

    // Translate READABLE STRING => HC NAME
    // rewrite dictionary
    return array_flip($dictionary)[$ModeName];
}

/** Function to translate the mode into a readable string
 * @param string $ModeName The abstract or readable string
 * @param bool $direction TRUE =  HC name => Readable string  //   FALSE = Readable string => HC name
 * @return string
 */
function OvenTranslateMode( string $ModeName, bool $direction) {
    $dictionary = array(
        "PreHeating" => "Vorheizen",
        "HotAir" => "Umluft",
        "HotAirEco" => "Umluft Eco",
        "HotAirGrilling" => "Umluft grillen",
        "TopBottomHeating" => "Ober/-Unterhitze",
        "TopBottomHeatingEco" => "Ober/-Unterhitze Eco",
        "BottomHeating" => "Unterhitze",
        "PizzaSetting" => "Pizza",
        "SlowCook" => "Langsames kochen",
        "IntensiveHeat" => "Intensives heizen",
        "KeepWarm" => "Warm halten",
        "PreheatOvenware" => "Vorheizen Geschirr",
        "FrozenHeatupSpecial" => "Gefrorenes Aufheizen",
        "Desiccation" => "Extrem Trocknen",
        "Defrost" => "Auftauen",
        "Proof" => "Brot geringe Temperatur",
        "Dish" => "Gericht"
    );

    // Translate HC NAME => READABLE STRING
    if ( $direction ) { return $dictionary[$ModeName]; }

    // Translate READABLE STRING => HC NAME
    // rewrite dictionary
    return array_flip($dictionary)[$ModeName];
}