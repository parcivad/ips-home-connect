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
        'Authorization: Bearer ' . getToken("https://simulator.home-connect.com/security/oauth/token", "8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7")
    );
    // build url
    $url = "https://simulator.home-connect.com/api/" . $endpoint;
    //-------------------------------------------------------------------------------------

    // configure curl curl options in array
    $curlopt = array(
        CURLOPT_URL => $url,
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