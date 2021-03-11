<?php


  trait HomeConnectApi{



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

      /** Function to authorize the application the first time or in case of no token!
       * @param string $user Email of the account
       * @param string $password Password of the account
       * @param boolean $simulator Real Api or simulator
       * @return mixed|string Authorize token
       */
      public function Authorize( $user="", $password="", $simulator = false ) {

          if ( $simulator ) {
              $connect = 'simulator';
          } else {
              $connect = 'api';
          }

          //----------------------------------------< Building Url with parameters >-------------
          $params_array = array(
              'response_type' => 'code',
              'client_id' => '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5',
              'scope' => 'IdentifyAppliance',
              'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
              'user' => $user,
              'password' => $password
          );
          $params = http_build_query($params_array);
          // define endpoint for authorization
          $endpoint = "/security/oauth/authorize?";
          // build url
          $url = "https://" . $connect . "simulator.home-connect.com" . $endpoint;
          //-------------------------------------------------------------------------------------

          // configure curl curl options in array
          $curloptions = array(
              CURLOPT_URL => $url . $params,
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
          $redirected_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
          // close curl
          curl_close($ch);

          $token = explode( '=', $redirected_url)[1];

          return $token;
      }

  }

?>