<?php

/*
 * This file exist to get the authorization code and save it into json.
 */

$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/tm/data.json" ), true );
require_once("./tm.php");

$redirect_params = explode("?", $_SERVER["REQUEST_URI"])[1];

if ( isset( $redirect_params ) ) {

    // check if the code is in the return to the localhost
    if ( explode( "=", $redirect_params )[0] == "code") {
        $code = str_replace( "=", "", urldecode( explode("=", explode("&", $redirect_params)[0])[1] ));
        // user feedback
        echo("<p>authorized!</p>");
        // save code
        $json = $data;
        $json["authorize"]["code"] = $code;
        write( $json );
        // stop php server
        shell_exec("killall -9 php");
        // return code for developer
        return $code;
    } else {
        throw new UnexpectedValueException("Error from your api: " . $redirect_params);
    }
} else {
    throw new UnexpectedValueException("No Parameters after authorization check your url and client id!");
}