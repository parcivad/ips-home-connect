<?php

  class HomeConnectDiscovery extends IPSModule {

      /*
       * Internal function of SDK
       */
      public function Create() {
          // Overwrite ips function
          parent::Create();
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
              'actions'  => $this->FormActions(),
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
                  "type" => "List",
                  "name" => "Devices",
                  "caption" => "Devices",
                  "rowCount" => 8,
                  "add" => false,
                  "delete" => false,
                  "sort" => [
                      "column" => "Name",
                      "direction" => "ascending",
                  ],
                  "columns" => [
                      [
                          "caption" => "Device",
                          "name" => "Device",
                          "width" => "100px",
                          "add" => "",
                      ],
                      [
                          "caption" => "Company",
                          "name" => "Company",
                          "width" => "120px",
                          "add" => "",
                      ],
                      [
                          "caption" => "haid",
                          "name" => "haid",
                          "width" => "auto",
                          "add" => "",
                      ],
                      [
                          "caption" => "Status",
                          "name" => "Status",
                          "width" => "200px",
                          "add" => "",
                      ],
                  ],
                  "values" => [
                      [
                          "Device" => "Test",
                          "Company" => "Wow GmbH",
                          "haid" => "1098247c1ß9u1ß98udc29ßhß19dcß1ndu12ß0h",
                          "Status" => "Connected",
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