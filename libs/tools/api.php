<?php

/**
 *  A file for all Api communications
 */

// import
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/tools/tm/data.json" ), true );

/** GET Api request
 * @param $endpoint String Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api($endpoint="") {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex->getMessage());
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

/** DELETE Api request
 * @param string $endpoint Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api_delete( $endpoint ) {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex->getMessage());
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

/** PUT Api request
 * @param string $endpoint Sending this command to the Api of HomeConnect
 * @param string $json Sending this command to the Api of HomeConnect
 * @return array Return the API output
 * @throws Exception
 */
function Api_put( $endpoint,  $json ) {
    global $data;

    try {
        $token = getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
    } catch (Exception $ex) {
        throw new Exception($ex->getMessage());
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

/** Function the check failed Api call/Token call for errors
 * @param $ex
 * @return int
 */
function analyseEX( Exception $ex ) {
    $codes = [
        'No Authorization code present' => 206,
        'invalid_grant' => 206,

        'invalid_token' => 207,
        'missing or invalid request parameters' => 207,

        'SDK.Error.HomeAppliance.Connection.Initialization.Failed' => 401,

        'SDK.Error.UnsupportedProgram' => 402,

        'ActiveProgramNotSet' => 403,

        'invalid_request' => 405,
        '404' => 405,

        '429' => 406,

        '503' => 407,

        '500' => 408,

        '403' => 409,
        'insufficient_scope' => 409,

        'SDK.Error.UnsupportedOperation' => 410,

        'BSH.Common.Error.RemoteControlNotActive' => 411,

        'BSH.Common.Error.RemoteStartNotActive' => 412,

        'BSH.Common.Error.LockedByLocalControl' => 413,

        'SDK.Error.Cooking.Oven.Status.FrontPanelOpen' => 414,

        'SDK.Error.WrongDoorState' => 415,

        'SDK.Error.Cooking.Oven.Status.MeatprobePlugged' => 416,

        'SDK.Error.BatteryLevelTooLow' => 417,

        'SDK.Error.ConsumerProducts.CleaningRobot.Status.Lifted' => 418,

        'SDK.Error.ConsumerProducts.CleaningRobot.Status.DustBoxNotInserted' => 419,

        'SDK.Error.ConsumerProducts.CleaningRobot.Status.AlreadyAtHome' => 420,

        'SDK.Error.ActiveProgramSet' => 421
    ];

    if ( isset( $codes[$ex->getMessage()] ) ) {
        return $codes[$ex->getMessage()];
    } else {
        IPS_LogMessage( 'HomeConnect','Unknown HomeConnect Error: ' . $ex->getMessage() );
        return 201;
    }
}