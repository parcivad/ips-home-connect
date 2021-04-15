<?php
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/tools/tm/data.json" ), true );

/**
 * @param $endpoint String Sending this command to the Api of HomeConnect
 * @return array Return the API output
 */
function Api($endpoint="") {
    global $data;

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'content-type: application/vnd.bsh.sdk.v1+json',
        'Authorization: Bearer ' . getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7")
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

    return json_decode( $response, true );
}

/**
 * @param string $endpoint Sending this command to the Api of HomeConnect
 * @return array Return the API output
 */
function Api_delete(string $endpoint ) {
    global $data;

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'Authorization: Bearer ' . getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7")
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
 */
function Api_put(string $endpoint, string $json ) {
    global $data;

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'content-type: application/vnd.bsh.sdk.v1+json',
        'Authorization: Bearer ' . getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7")
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

    return json_decode( $response, true );
}