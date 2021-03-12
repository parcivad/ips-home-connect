<?php

  define('__ROOT__', dirname(dirname(__FILE__)));
  require_once(__ROOT__ . "/libs/tools/HomeConnectApi.php");

  class HomeConnectDiscovery extends IPSModule {


      /*
       * Internal function of SDK
       */
      public function Create()
      {
          // Overwrite ips function
          parent::Create();

          // User Data
          $this->RegisterPropertyString("user", "your@mail.de");
          $this->RegisterPropertyString("password", "password");
          $this->RegisterPropertyBoolean("simulator", true);
          $this->RegisterAttributeString( 'loginstate', false);
      }
      /*
       * Internal function of SDK
       */
      public function ApplyChanges() {
          // Overwrite ips function
          parent::ApplyChanges();
      }

      public function GetConfigurationForm()
      {
          // return current form
          $Form = json_encode([
              'elements' => $this->FormElements(),
              //'actions'  => $this->FormActions(),
              'status'   => $this->FormStatus(),
          ]);
          $this->SendDebug('FORM', $Form, 0);
          $this->SendDebug('FORM', json_last_error_msg(), 0);

          return $Form;
      }

      public function GetDevices() {

          $api = new HomeConnectApi();
          $api->SetUser( "your@mail.de" );
          $api->SetPassword( "password" );
          $api->SetSimulator( true );

          $data = $api->Api("homeappliances")['data']['homeappliances'];
          $len = count($data);

          $devices = array();

          for ($i = 0; $i < $len; $i++) {
              $name = $data[$i]['name'];
              $brand = $data[$i]['brand'];
              $connected = $data[$i]['connected'];
              $type = $data[$i]['type'];
              $haId = $data[$i]['haId'];

              $device = [
                  "Device" => $type,
                  "Company" => $brand,
                  "haid" => $haId,
                  "Connected" => $connected,
                  "create" => [
                      "moduleID" => "{5899C50B-7033-9DA4-BD0A-D8ED2BF227B9}",
                      "configuration" => [],
                  ]
              ];

              array_push($devices, $device);
          }

// Return String (json)
          return (  json_encode( $devices ) );
      }







      /**
       * @return array[] Form Actions
       */
      protected function FormActions() {
          $form = [
              [

              ],
          ];

          return $form;
      }

      /**
       * @return array[] Form Elements
       */
      protected function FormElements() {
          $form = [
              [
                  "type" => "ValidationTextBox",
                  "name" => "user",
                  "caption" => "HomeConnect - User-Email",
              ],
              [
                  "type" => "PasswordTextBox",
                  "name" => "password",
                  "caption" => "HomeConnect - Password",
              ],
              [
                  "type" => "ValidationTextBox",
                  "name" => "loginstate",
                  "caption" => "connection",
              ],
              [
                  "type" => "CheckBox",
                  "name" => "simulator",
                  "caption" => "HomeConnect Simulation verwenden."
              ],
              [
                  "type" => "Configurator",
                  "name" => "Home-Connect Discovery",
                  "caption" => "HomeConnect Discovery",
                  "rowCount" => 20,
                  "add" => false,
                  "delete"=> true,
                  "columns" => [
                      [
                          "caption" => "Device",
                          "name" => "Device",
                          "width" => "100px",
                          "add" => false,
                      ],
                      [
                          "caption" => "Company",
                          "name" => "Company",
                          "width" => "120px",
                          "add" => false,
                      ],
                      [
                          "caption" => "haid",
                          "name" => "haid",
                          "width" => "auto",
                          "add" => false,
                      ],
                      [
                          "caption" => "Connected",
                          "name" => "Status",
                          "width" => "100px",
                          "add" => false,
                      ],
                  ],
                  "values" => [
                      substr( $this->GetDevices(), 1),
                  ],
              ],
          ];

          return $form;
      }

      /**
       * @return array[] Form Status
       */
      protected function FormStatus() {
          $form = [
              [
                  'code'    => 101,
                  'icon'    => 'inactive',
                  'caption' => 'Creating instance.',
              ],
              [
                  'code'    => 102,
                  'icon'    => 'active',
                  'caption' => 'HomeConnect Discovery created.',
              ],
              [
                  'code'    => 104,
                  'icon'    => 'inactive',
                  'caption' => 'interface closed.',
              ],
              [
                  'code'    => 201,
                  'icon'    => 'inactive',
                  'caption' => 'Please follow the instructions.',
              ],
          ];

          return $form;
      }

  }

?>