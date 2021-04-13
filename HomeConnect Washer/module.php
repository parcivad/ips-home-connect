<?php

require_once( dirname(dirname(__FILE__) ) . "/libs/tools/HomeConnectApi.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );


class HomeConnectWasher extends IPSModule {

      /** This function will be called on the creation of this Module
       * @return bool|void
       */
      public function Create() {
          // Overwrite ips function
          parent::Create();

          // Device Information, set by Configurator
          $this->RegisterPropertyString('name', '');
          $this->RegisterPropertyString('device', '');
          $this->RegisterPropertyString('company', '');
          $this->RegisterPropertyString('haId', '');

          // Register Information Panel
          $this->RegisterAttributeString("remoteControlAllowed", false );
          $this->RegisterAttributeString("remoteStartAllowed", false );

          // Set by User
          $this->RegisterPropertyInteger("refreshRate", 5 );
      }

      /** This function will be called by IP Symcon when the User change vars in the Module Interface
       * @return bool|void
       */
      public function ApplyChanges()
      {
          // Overwrite ips function
          parent::ApplyChanges();

          // Change Timer
          $rate = ( $this->ReadPropertyInteger("refreshRate") * 1000 ) * 60;
          $this->SetTimerInterval("refresh", $rate );

      }


      /**
       * @param string $var that should be analyse
       * @return bool returns true or false for HomeConnect Api result
       */
      public function HCvar($var ) {

          switch ( $var ) {
              //------------------------ DOOR
              // Return for Open Door
              case "BSH.Common.EnumType.DoorState.Open":
                  return true;
              // Return for Close Door
              case "BSH.Common.EnumType.DoorState.Closed":
                  return false;
                  break;
              //------------------------ OPERATION STATE
              case "BSH.Common.EnumType.OperationState.Inactive":
                  return 0;
              case "BSH.Common.EnumType.OperationState.Ready":
                  return 1;
              case "BSH.Common.EnumType.OperationState.Run":
                  return 2;
                  break;
          }

      }


      /** This Function will register all Profiles for the Module
       */
      protected function registerProfiles()
      {
          // Generate Variable Profiles
      }



      //-----------------------------------------------------< Setting Form.json >-----------
      /** This Function will set the IP Symcon Form.json
       * @return false|string Form json
       */
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
                  "type" => "Image",
                  "image" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAACWCAYAAAAonXpvAAAFRmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgZXhpZjpDb2xvclNwYWNlPSIxIgogICBleGlmOlBpeGVsWERpbWVuc2lvbj0iNTAwIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMTUwIgogICBwaG90b3Nob3A6Q29sb3JNb2RlPSIzIgogICBwaG90b3Nob3A6SUNDUHJvZmlsZT0ic1JHQiBJRUM2MTk2Ni0yLjEiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjE1MCIKICAgdGlmZjpJbWFnZVdpZHRoPSI1MDAiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjQwMC4wIgogICB0aWZmOllSZXNvbHV0aW9uPSI0MDAuMCIKICAgeG1wOk1ldGFkYXRhRGF0ZT0i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"
              ],
              [
                  "type" => "List",
                  "name" => "DeviceInfo",
                  "caption" => "Information to the Home Connect Device [ Washer ]",
                  "rowCount" => 1,
                  "add" => false,
                  "delete" => false,
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
                  ],
                  "values" => [
                      [
                          "name" => $this->ReadPropertyString("name"),
                          "device" => $this->ReadPropertyString( "device"),
                          "company" => $this->ReadPropertyString( "company"),
                          "haId" => $this->ReadPropertyString( "haId"),
                      ],
                  ],
              ],
              [
                  "type" => "ExpansionPanel",
                  "caption" => "System Device Information [IMPORTANT]",
                  "items" => [
                      [
                          "type" => "Label",
                          "name" => "remoteControlAllowed",
                          "caption" => $this->ReadAttributeString('remoteControlAllowed'),
                      ],
                      [
                          "type" => "Label",
                          "name" => "remoteStartAllowed",
                          "caption" => $this->ReadAttributeString('remoteStartAllowed'),
                      ],
                  ],
              ],
              [
                  "type" => "NumberSpinner",
                  "name" => "refreshRate",
                  "caption" => "Refresh Rate",
                  "enable" => true,
                  "maximum" => 86400,
                  "minimum" => 2,
                  "suffix" => "min",
                  "visible" => true,
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
              ]
          ];

          return $form;
      }


  }

?>