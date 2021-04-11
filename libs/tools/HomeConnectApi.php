<?php
require_once( dirname(dirname(__FILE__) ) . "/tools/tm/tm.php");

/**
 * @param $command String Sending this command to the Api of HomeConnect
 * @return array Return the API output
 */
function Api($endpoint="") {

    //----------------------------------------< Building Url with parameters >-------------
    $header_array = array(
        'content-type: application/vnd.bsh.sdk.v1+json',
        'authorization: Bearer ' . getAccessToken()
    );
    // build url
    $url = "https://simulator.home-connect.com/api/" . $endpoint . "?";
    //-------------------------------------------------------------------------------------

    // configure curl curl options in array
    $curloptions = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $header_array,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
    );

    // initialse curl
    $ch = curl_init();
    // setting curl options
    curl_setopt_array($ch, $curloptions);
    // run curl
    $result = curl_exec($ch);
    // setting that the token got refreshed
    // close curl
    curl_close($ch);
    // Format
    $result_formatted = explode('Origin', $result)[1];
    $result_array = json_decode($result_formatted, true);

    return $result_array;

}