<?php

  define( 'database', json_decode( file_get_contents("database.json", __ROOT__ . "/libs/tools/database.json" ), true ) );

  class HomeConnectApi
  {


      // define vars ---------------------------------------------------------
      private $access_token = database['access_token']; // current access token for home connect
      private $refresh_token = database['refresh_token']; // current refresh token for refresh
      private $expires_in = database['expires_in']; // expire time in seconds ( normal case 1 day )
      private $last_refresh = database['last_refresh']; // When did the Module get the $access_token

      private $user = database['user']; // user
      private $password = database['password']; // password
      private $simulator = database['simulator'];

      private $loginstate = database['loginstate']; // Error analysis in Authorization
      private $tokenstate = database['loginstate']; // Error analysis in GetToken


      /** Set the User of the API
       * @param string $user Email of the account
       */
      public function SetUser( $user="" ) {
          $this->user = $user;
          $this->refreshDatabase();
      }

      /** Set the Password of the User API
       * @param string $password Password of the account
       */
      public function SetPassword( $password="" ) {
          $this->password = $password;
          $this->refreshDatabase();
      }

      /** Set if the Api should work with the simulator
       * @param false $simulator
       */
      public function SetSimulator( $simulator=false ) {
          $this->simulator = $simulator;
          $this->refreshDatabase();
      }

      /**
       * @param $command String Sending this command to the Api of HomeConnect
       * @return array Return the API output
       */
      public function Api($endpoint="") {

          // Checking for access token
          if ( !isset( $this->access_token ) ) {
              $tokens = $this->GetToken();
          }

          // Checking for errors after GetToken/Authorizaition
          if ( !$this->tokenstate ) {
              // A error appeared while trying to get token
              return null;
          } else if ( !$this->loginstate ) {
              // A error appeared while trying to authorize or get token, Check function for more detail
              return null;
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

          $this->refreshDatabase();
          return $result_array;
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

          if ( isset($tokens['error'] ) ) {
              $this->tokenstate = false;
              return null;
          }

          // Setting token (private vars)

          $this->access_token = $tokens['access_token'];
          $this->refresh_token = $tokens['refresh_token'];
          $this->expires_in = $tokens['expires_in'];

          $this->tokenstate = true;
          $this->refreshDatabase();
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
      public function Authorize( $perms="IdentifyAppliance Monitor" ) {

          // Catch if login data is missing
          if ( !isset( $this->user) || !isset( $this->password ) ) {
              return "Login Data is missing (User or password)";
          }

          if ( $this->simulator ) {
              // If the User is using the simulator
              //------------------------------< Configure parameters for Simulator connection >-----
              $params_array = array(
                  'response_type' => 'code',
                  'client_id' => '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D',
                  'scope' => $perms,
                  'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
                  'user' => $this->user,
                  'password' => $this->password
              );
              // using simulator
              $connect = 'simulator';
          } else {
              $state = uniqid();

              // If the User is using real api
              //------------------------------------< Configure parameters for Api connection >-----
              $params_array = array(
                  'response_type' => 'code',
                  'client_id' => '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5',
                  'grant_type' => 'authorization_code',
                  //'state' => $state,
                  'email' => $this->user,
                  'password' => $this->password,
              );
              $connect = 'api';
          }

          //----------------------------------------< Building Url with parameters >-------------
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

          $code = explode('=', $redirected_url);

          // Catch mistake that could appear when login
          if ( $code[0] != 'https://api-docs.home-connect.com/quickstart/?code' ) {
              $this->loginstate = false;
              return "A error appeared!";
          }

          $this->loginstate = true;
          $this->refreshDatabase();
          return strval($code[1]);
      }

      /** Function to refresh the database/json in database.json
       */
      public function refreshDatabase() {
          $json = database;

          $json['access_token'] = $this->access_token;
          $json['refresh_token'] = $this->refresh_token;
          $json['expires_in'] = $this->expires_in;
          $json['last_refresh'] = $this->last_refresh;

          $json['user'] = $this->user;
          $json['password'] = $this->password;
          $json['simulator'] = $this->simulator;

          $json['loginstate'] = $this->loginstate;
          $json['tokenstate'] = $this->tokenstate;

          file_put_contents("database.json", json_encode( $json ));
      }
  }
?>