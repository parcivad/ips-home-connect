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
          $this->RegisterPropertyString("user", "");
          $this->RegisterPropertyString("password", "");
          // HomeConnect Api
          $this->RegisterPropertyString('refresh_token', "");
          $this->RegisterPropertyString('token', $this->GetToken("test@test.de", "password", true));
          // Use Home Conenct Simulator
          $this->RegisterPropertyBoolean("simulator", false);
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

          $this->CreateToken("test@test.de", "password", true);

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
                  "values" => [
                      [
                          "Device" => "Oven",
                          "Company" => "BOSCH",
                          "haid" => "BOSCH-ASDO2034N-3OI2D7H2QD-ASDHIB2349A",
                          "Status" => "Not Configured",
                          "create" => [
                              "moduleID" => "{5899C50B-7033-9DA4-BD0A-D8ED2BF227B9}",
                              "configuration" => [],
                          ]
                      ],
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