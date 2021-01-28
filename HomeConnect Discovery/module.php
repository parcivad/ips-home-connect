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
                  "type"=> "Configurator",
                  "name"=> "Configuration",
                  "caption"=> "Configuration",
                  "delete"=> true,
                  "values"=> [
                      [
                          "id"=> 1,
                          "name"=> "Kategorie",
                          "address"=> ""
                      ],[
                          "parent"=> 1,
                          "name"=> "Rechenmodul - Minimum",
                          "address"=> "2",
                          "create"=> [
                              "moduleID"=> "[A7B0B43B-BEB0-4452-B55E-CD8A9A56B052]",
                              "configuration"=> [
                                  "Calculation"=> 2,
                                  "Variables"=> "[]"
                              ]
                          ]
                      ],[
                          "parent"=> 1,
                          "name"=> "Rechenmodul im Wohnzimmer",
                          "address"=> "2",
                          "create"=> [
                              "moduleID"=> "[A7B0B43B-BEB0-4452-B55E-CD8A9A56B052]",
                              "configuration"=> [
                                  "Calculation"=> 2,
                                  "Variables"=> "[]"
                              ],
                              "location"=> [
                                  "Erdgeschoss",
                                  "Wohnzimmer"
                              ]
                          ]
                      ],[
                          "parent"=> 1,
                          "instanceID"=> 53398,
                          "name"=> "Fehlerhafte Instanz",
                          "address"=> "4"
                      ],[
                          "parent"=> 1,
                          "name"=> "Rechenmodul - Auswahl",
                          "address"=> "2",
                          "create"=> [
                              "Maximum"=> [
                                  "moduleID"=> "[A7B0B43B-BEB0-4452-B55E-CD8A9A56B052]",
                                  "configuration"=> [
                                      "Calculation"=> 3,
                                      "Variables"=> "[]"
                                  ]
                              ],
                              "Average"=> [
                                  "moduleID"=> "[A7B0B43B-BEB0-4452-B55E-CD8A9A56B052]",
                                  "configuration"=> [
                                      "Calculation"=> 4,
                                      "Variables"=> "[]"
                                  ]
                              ]
                          ]
                      ], [
                          "parent"=> 1,
                          "name"=> "OZW772 IP-Interface",
                          "address"=> "00=>A0=>03=>FD=>14=>BB",
                          "create"=> [
                              [
                                  "moduleID"=> "[33765ABB-CFA5-40AA-89C0-A7CEA89CFE7A]",
                                  "configuration"=> []
                              ],
                              [
                                  "moduleID"=> "[1C902193-B044-43B8-9433-419F09C641B8]",
                                  "configuration"=> [
                                      "GatewayMode"=>1
                                  ]
                              ],
                              [
                                  "moduleID"=> "[82347F20-F541-41E1-AC5B-A636FD3AE2D8]",
                                  "configuration"=> [
                                      "Host"=>"172.17.31.95",
                                      "Port"=>3671,
                                      "Open"=>true
                                  ]
                              ]
                          ]
                      ]
                  ]
              ]
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