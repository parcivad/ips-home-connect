<?php

$data_array = array(
    'response_type' => 'code',
    'client_id' => '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D',
    'scope' => 'IdentifyAppliance',
    'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
    'user' => 'test@test.de'
);
$data = http_build_query($data_array);

$endpoint = "https://simulator.home-connect.com/security/oauth/authorize?";

// setting curl options in array
$curloptions = array(
    CURLOPT_URL => $endpoint . $data,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RETURNTRANSFER => true,
);

// initialse curl
$ch = curl_init();
// setting curl options
curl_setopt_array($ch, $curloptions);

// run curl
$result = curl_exec($ch);
$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
// close curl
curl_close($ch);
// printout curl
print_r($url);


  trait HomeConnectApi {



      /**
       * @param $command String Sending this command to the Api of HomeConnect
       */
      public function Api($command = "", $token = null , $imulator = false ) {
          return false;
      }

      /**
       * @param $user String Email of the User
       * @param $password String password from the User
       * @param $simulator Boolean Token for Simulator or Real API
       */
      public function CreateToken( $user = null, $password = null, $simulator = false ) {

          // Creating a token in the real API
          if ( $simulator == false ) {


          } else if ( $simulator == true) {
              // creating token and return


          } else {
              // Unexpected Value
              throw new UnexpectedValueException("Value simulator must be boolean");
          }

      }

      public function RefreshToken( $user, $password, $simulator ) {

      }

  }

?>