<?php

  class HomeConnectDiscovery extends IPSModule {

      /*
       * Internal function of SDK
       */
      public function Create() {
          // Overwrite ips function
          parent::Create();

          $this->RegisterPropertyString("user", "test");
          $this->RegisterPropertyString("password", "test");
      }

      /*
       * Internal function of SDK
       */
      public function ApplyChanges() {
          // Overwrite ips function
          parent::ApplyChanges();

          if (IPS_GetKernelRunlevel() !== KR_READY) {
              return;
          }

          $this->RequireParent("{6179ED6A-FC31-413C-BB8E-1204150CF376}");
      }

      /*
       * Update Function
       */
      protected function ModulUpdate() {
          // Getting

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
                  "caption" => "HomeConnect - User",
              ],
              [
                  "type" => "PasswordTextBox",
                  "name" => "password",
                  "caption" => "HomeConnect - Password",
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
                          "Device" => $this->ReadAttributeString("user"),
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