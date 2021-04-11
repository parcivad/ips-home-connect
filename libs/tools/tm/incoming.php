<?php

/*
 * This file exist to get the authorization code and save it into json.
 */

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . "/tm/tm.php");

$redirect_params = explode("?", $_SERVER["REQUEST_URI"])[1];

if ( isset( $redirect_params ) ) {

    // check if the code is in the return to the localhost
    if ( explode( "=", $redirect_params )[0] == "code") {
        $code = str_replace( "=", "", urldecode( explode("=", explode("&", $redirect_params)[0])[1] ));
        // user feedback
        echo("<p>authorized!</p>");
        // save code
        write( "code", $code, true);
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