<?php

  define('__ROOT__', dirname(dirname(__FILE__)));
  require_once(__ROOT__ . "/libs/tools/HomeConnectApi.php");

  class HomeConnectDiscovery extends IPSModule {


      use HomeConnectApi;

      /*
       * Internal function of SDK
       */
      public function Create()
      {
          // Overwrite ips function
          parent::Create();

          // User Data
          $this->RegisterPropertyString("user", "your@mail.com");
          $this->RegisterPropertyString("password", "password");
          $this->RegisterPropertyBoolean("simulator", true);
          // HomeConnect Api
          $tokens = $this->GetToken( "your@mail.com", "password", true );

          $this->RegisterPropertyString('refresh_token', $tokens['refresh_token']);
          $this->RegisterPropertyString('token', $tokens['access_token'] );

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


          // HomeConnect Api
          $tokens = $this->GetToken( $this->ReadPropertyString('user'),
                                     $this->ReadPropertyString('password'),
                                     $this->ReadPropertyBoolean('simulator'));


          $return = [
              "Device" => "Oven",
              "Company" => "BOSCH",
              "haid" => $tokens['access_token'],
              "Status" => "Not Configured",
              "create" => [
                  "moduleID" => "{5899C50B-7033-9DA4-BD0A-D8ED2BF227B9}",
                  "configuration" => [],
              ]
          ];

          return $return;
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
                  "name" => "token",
                  "caption" => "Current Token",
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
                          "caption" => "Status",
                          "name" => "Status",
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