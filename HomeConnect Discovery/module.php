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
          $this->RegisterAttributeString( 'loginstate', "");
      }
      /*
       * Internal function of SDK
       */
      public function ApplyChanges() {
          // Overwrite ips function
          parent::ApplyChanges();
      }

      public function GetDevices() {

          $api = new HomeConnectApi();
          $api->SetUser( $this->ReadPropertyString('user') );
          $api->SetPassword( $this->ReadPropertyString('password') );
          $api->SetSimulator( $this->ReadPropertyBoolean('simulator') );

          $data = $api->Api("homeappliances")['data']['homeappliances'];
          // catch null exception
          if ( $data == null ) { return [[ 'name' => 'Login failed [Token/Auth]', 'rowColor' => '#ff0000'],]; }

          $len = count($data);

          $devices = [];

          for ($i = 0; $i < $len; $i++) {
              array_push($devices, $data[$i] );
          }

          $config_list = [];

          if (!empty($devices)) {
              foreach ($devices as $device) {
                  $name = $device['name'];
                  $brand = $device['brand'];
                  $connected = $device['connected'];
                  $type = $device['type'];
                  $haId = $device['haId'];

                  if ( $connected == true ) {
                      $color = "#C0FFC0";
                  } else if ( $connected == false ) {
                      $color = '#FFFFC0';
                  }


                  $config_list[] = [
                      'rowColor' => $color,
                      'name' => $name,
                      'device' => $type,
                      'company' => $brand,
                      'haId' => $haId,
                      'connected' => $connected,
                      'create'     => [
                          'moduleID'      => '{5899C50B-7033-9DA4-BD0A-D8ED2BF227B9}',
                          'configuration' => [],
                      ],
                  ];
              }
          }

          return  $config_list;
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
                  "type" => "CheckBox",
                  "name" => "simulator",
                  "caption" => "HomeConnect Simulation verwenden."
              ],
              [
                  "type" => "Configurator",
                  "name" => "Home-Connect Discovery",
                  "caption" => "HomeConnect Discovery",
                  "rowCount" => 14,
                  "add" => false,
                  "delete"=> true,
                  "columns" => [
                      [
                          "caption" => "Name",
                          "name" => "name",
                          "width" => "200px",
                          "add" => false,
                      ],
                      [
                          "caption" => "Device",
                          "name" => "device",
                          "width" => "120px",
                          "add" => false,
                      ],
                      [
                          "caption" => "Company",
                          "name" => "company",
                          "width" => "120px",
                          "add" => false,
                      ],
                      [
                          "caption" => "haId",
                          "name" => "haId",
                          "width" => "auto",
                          "add" => false,
                      ],
                      [
                          "caption" => "Connection",
                          "name" => "connected",
                          "width" => "100px",
                          "add" => false,
                      ],
                  ],
                  "values" => $this->GetDevices(),
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