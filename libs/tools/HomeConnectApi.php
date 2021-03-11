<?php

  trait HomeConnectApi
  {

      /**
       * @param $command String Sending this command to the Api of HomeConnect
       */
      public function Api($command = "", $token = null, $imulator = false) {
          return false;
      }

      /**
       * @param string $user Email of the account
       * @param string $password Password of the account
       * @param false $simulator Real Api or simulator
       * @return mixed|string Device Token
       */
      public function GetToken($user = "", $password = "", $simulator = false) {

          if ($simulator) {
              // using simulator
              $connect = 'simulator';
              $client = '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D';
              $client_secret = '';
              $sim = true;
          } else {
              // If the User is using real api
              $connect = 'api';
              $client = '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5';
              $client_secret = 'EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7';
              $sim = false;
          }

          //----------------------------------------< Building Url with parameters >-------------
          $params_array = array(
              'Content-Type' => 'application/x-www-form-urlencoded',
              'grant_type' => 'authorization_code',
              'client_id' => $client,
              'client_secret' => $client_secret,
              'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
              'code' => $this->Authorize($user, $password, $sim)
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
          // close curl
          curl_close($ch);

          $tokens = json_decode($result, true);

          return $tokens;
      }

      /** Function to authorize the application the first time or in case of no token!
       * @param string $user Email of the account
       * @param string $password Password of the account
       * @param boolean $simulator Real Api or simulator
       * @return mixed|string Authorize code
       */
      public function Authorize($user = "", $password = "", $simulator = false) {

          if ($simulator) {
              // using simulator
              $connect = 'simulator';
              $client = '8CB8468BC84F6E2C6AA1378BAE73BDF9864A32038D8EEF327CBB99936B74848D';
          } else {
              // If the User is using real api
              $connect = 'api';
              $client = '35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5';
          }

          //----------------------------------------< Building Url with parameters >-------------
          $params_array = array(
              'response_type' => 'code',
              'client_id' => $client,
              'scope' => 'IdentifyAppliance',
              'redirect_uri' => 'https://api-docs.home-connect.com/quickstart/',
              'user' => $user,
              'password' => $password
          );
          $params = http_build_query($params_array);
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

          return strval($code);
      }
  }
?>