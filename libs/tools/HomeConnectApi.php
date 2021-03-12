<?php

  class HomeConnectApi
  {

      // define vars ---------------------------------------------------------
      private $access_token; // current access token for home connect
      private $refresh_token; // current refresh token for refresh
      private $expires_in; // expire time in seconds ( normal case 1 day )
      private $last_refresh; // When did the Module get the $access_token

      private $user; // user
      private $password; // password
      private $simulator = false;
      /**
       * @param $command String Sending this command to the Api of HomeConnect
       * @return array Return the API output
       */
      public function Api($endpoint="") {

          if ( !isset( $this->access_token ) ) {
              $this->GetToken();
          }

          if ( $this->simulator ) {
              // using simulator
              $connect = 'simulator';
          } else {
              // If the User is using real api
              $connect = 'api';
          }

          //----------------------------------------< Building Url with parameters >-------------
          $header_array = array(
              'content-type: application/vnd.bsh.sdk.v1+json',
              'authorization: Bearer ' . $this->access_token,
          );
          // build url
          $url = "https://" . $connect . ".home-connect.com/api/" . $endpoint . "?";
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


      /** Set the User of the API
       * @param string $user Email of the account
       */
      public function SetUser( $user="" ) {
          $this->user = $user;
      }

      /** Set the Password of the User API
       * @param string $password Password of the account
       */
      public function SetPassword( $password="" ) {
          $this->password = $password;
      }

      /** Set if the Api should work with the simulator
       * @param false $simulator
       */
      public function SetSimulator( $simulator=false ) {
          $this->simulator = $simulator;
      }

      /**
       * @return mixed return token ( but not needed )
       */
      public function GetToken() {

          if ( $this->CheckToken() ) {
              return $this->access_token;
          }

          if ( $this->simulator ) {
              // using simulator
              $connect = 'simulator';
              $client = '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D';
              $client_secret = '';
          } else {
              // If the User is using real api
              $connect = 'api';
              $client = '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5';
              $client_secret = 'EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7';
          }

          //----------------------------------------< Building Url with parameters >-------------
          $params_array = array(
              'Content-Type' => 'application/x-www-form-urlencoded',
              'grant_type' => 'authorization_code',
              'client_id' => $client,
              'client_secret' => $client_secret,
              'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
              'code' => $this->Authorize()
          );
          $params = http_build_query($params_array);
          // define endpoint for authorization
          $endpoint = "/security/oauth/token?";
          // build url
          $url = "https://" . $connect . ".home-connect.com" . $endpoint;
          //-------------------------------------------------------------------------------------

          // configure curl curl options in array
          $curloptions = array(
              CURLOPT_URL => $url,
              CURLOPT_POST => true,
              CURLOPT_POSTFIELDS => $params,
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
          $this->last_refresh = time();
          // close curl
          curl_close($ch);

          $tokens = json_decode($result, true);

          // Setting token (private vars)
          $this->access_token = $tokens['access_token'];
          $this->refresh_token = $tokens['refresh_token'];
          $this->expires_in = $tokens['expires_in'];

          return $tokens;
      }

      /** Function will check if the token is still working
       * @param $token
       * @return boolean True for active and False for expired!
       */
      private function CheckToken() {

          if ( isset( $this->access_token ) ) {

              $lastrefresh = $this->last_refresh;
              $expire = $this->expires_in;
              $time = time();

              $timeSinceLastRefresh = $time - $lastrefresh;
              $shouldupdate = $expire - 3600;

              if ( $timeSinceLastRefresh < $shouldupdate ) {
                  return true;
              }
          }

          return false;
      }

      /** Function to authorize the application the first time or in case of no token!
       * @return string return authorization code
       */
      public function Authorize( $perms="IdentifyAppliance") {

          if ( $this->simulator ) {
              // using simulator
              $connect = 'simulator';
              $client = '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D';
          } else {
              // If the User is using real api
              $connect = 'api';
              $client = '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5';
          }

          $scopes = $perms;

          //----------------------------------------< Building Url with parameters >-------------
          $params_array = array(
              'response_type' => 'code',
              'client_id' => $client,
              'scope' => $scopes,
              'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
              'user' => $this->user,
              'password' => $this->password
          );
          $params = http_build_query($params_array);
          $params = str_replace( "+", "%20", $params );
          // define endpoint for authorization
          $endpoint = "/security/oauth/authorize?";
          // build url
          $url = "https://" . $connect . ".home-connect.com" . $endpoint;
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

          $code = explode('=', $redirected_url)[1];

          $this->loginstate = true;

          return strval($code);
      }
  }
?>