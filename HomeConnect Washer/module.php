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

          // Set by User
          $this->RegisterPropertyInteger("first_refresh", 1 );
          $this->RegisterPropertyInteger("second_refresh", 1 );

          // Register Information Panel
          $this->RegisterAttributeString("remoteControlAllowed", false );
          $this->RegisterAttributeString("remoteStartAllowed", false );

          // Register Variable and Profiles
          $this->registerProfiles();

          $this->RegisterVariableInteger('LastRefresh', "Last Refresh", "UnixTimestamp", -1 );
          $this->RegisterVariableInteger("state", "Device State", "HC_WasherState", 0 );
          $this->RegisterVariableBoolean("door", "Door State", "HC_WasherDoorState", 1 );
      }

      /** This function will be called by IP Symcon when the User change vars in the Module Interface
       * @return bool|void
       */
      public function ApplyChanges()
      {
          // Overwrite ips function
          parent::ApplyChanges();
      }


      public function refresh() {
          echo getAuthorizeCode();
          echo "TOKEN:";
          echo getAccessToken();

          $recall = Api("homeappliances/011010386629000762/status");


          // catch null exception
          if ( $recall == null ) { return "error"; }

          // Getting each data into variables
          $RemoteControlAllowed = $recall['data']['status'][0]['value'];
          $RemoteControlStartAllowed = $recall['data']['status'][1]['value'];
          $DoorState =  $this->HC( $recall['data']['status'][2]['value'] );
          $OperationState = $this->HC( $recall['data']['status'][3]['value'] );

          $this->SetValue("door", $DoorState );
          $this->SetValue("state", $OperationState );

          $this->SetValue( "LastRefresh", time() );
      }


      /**
       * @param string $var that should be analyse
       * @return bool returns true or false for HomeConnect Api result
       */
      public function HC($var ) {

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
          if (!IPS_VariableProfileExists('HC_WasherState')) {
              IPS_CreateVariableProfile('HC_WasherState', 1);
              IPS_SetVariableProfileIcon('HC_WasherState', 'Power');
              IPS_SetVariableProfileAssociation("HC_WasherState", 0, "Standby", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherState", 1, "Ready", "", 0x22ff00 );
              IPS_SetVariableProfileAssociation("HC_WasherState", 2, "Program running", "", 0xfc0303 );
          }
          if (!IPS_VariableProfileExists('HC_WasherDoorState')) {
              IPS_CreateVariableProfile('HC_WasherDoorState', 0);
              IPS_SetVariableProfileIcon('HC_WasherDoorState', 'Lock');
              IPS_SetVariableProfileAssociation("HC_WasherDoorState", false, "Closed", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherDoorState", true, "Open", "", 0xcf0000 );
          }
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
                  "image" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAACWCAYAAAAonXpvAAAF52lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgZXhpZjpDb2xvclNwYWNlPSIxIgogICBleGlmOlBpeGVsWERpbWVuc2lvbj0iNTAwIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMTUwIgogICBwaG90b3Nob3A6Q29sb3JNb2RlPSIzIgogICBwaG90b3Nob3A6SUNDUHJvZmlsZT0ic1JHQiBJRUM2MTk2Ni0yLjEiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjE1MCIKICAgdGlmZjpJbWFnZVdpZHRoPSI1MDAiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjQwMC4wIgog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"
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
                  "caption" => "Permissions from your Device",
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
                  "type" => "ExpansionPanel",
                  "caption" => "Refreshing Data",
                  "items" => [
                      [
                          "type" => "NumberSpinner",
                          "name" => "first_refresh",
                          "caption" => "Start refreshing from",
                          "suffix" => "h",
                          "min" => "0",
                          "max" => "24",
                          "enabled" => true
                      ],
                      [
                          "type" => "NumberSpinner",
                          "name" => "second_refresh",
                          "caption" => "To",
                          "suffix" => "h",
                          "min" => "0",
                          "max" => "24",
                          "enabled" => true
                      ]
                  ],
              ],
              [
                  "type" => "ExpansionPanel",
                  "caption" => "Settings from your Device",
                  "items" => [
                      [
                          "type" => "CheckBox",
                          "name" => "status",
                          "caption" => "///",
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
              ]
          ];

          return $form;
      }


  }

?>