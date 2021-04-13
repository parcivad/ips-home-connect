<?php

  define('__ROOT__', dirname(dirname(__FILE__)));
  require_once(__ROOT__ . "/libs/tools/HomeConnectApi.php");

  class HomeConnectOven extends IPSModule {

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

          // Erstellt einen Timer mit dem Namen "Update" und einem Intervall von 5 Sekunden.
          $this->RegisterTimer("refresh", 300000, "HomeConnectDishwasher_refresh($this->InstanceID);");

          // Register Variable and Profiles
          $this->registerProfiles();

          $this->RegisterVariableInteger('LastRefresh', "Last Refresh", "UnixTimestamp", -1 );
          $this->RegisterVariableInteger("state", "Device State", "HC_OvenState", 0 );
          $this->RegisterVariableBoolean("door", "Doorstate", "HC_DoorState", 1 );
          $this->RegisterVariableInteger("heating", "Heating Mode", "HC_HeatMode", 7 );
          $this->RegisterVariableFloat("temperature", "Temperature", "Temperature", 8 );
          $this->RegisterVariableInteger("timer", "Timer", "UnixTimestampTime", 9);
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

      /** This Function will refresh the IP Symcon Variables with data from the Home Connect Cloud
       * @return string setting
       */
      public function Refresh() {
          $api = new HomeConnectApi();

          $data = $api->Api("homeappliances/BOSCH-HCS01OVN1-319994D4D470/status");

          // catch null exception
          if ( $data == null ) { $error_return = "error"; return $error_return;}

          // Getting each data into variables
          $RemoteControlAllowed = $data['data']['status'][0]['value'];
          $RemoteControlStartAllowed = $data['data']['status'][1]['value'];
          $OperationState = $this->HCvar( $data['data']['status'][2]['value'] );
          $DoorState =  $this->HCvar( $data['data']['status'][3]['value'] );
          $Temperature = $data['data']['status'][4]['value'];

          // Remote Control
          if ( $RemoteControlStartAllowed ) {
              $RemoteControlStartAllowed = "Device allowing RemoteControlStart";
          } else {
              $RemoteControlStartAllowed = "Device cancelled RemoteControlStart";
          }

          // Remote Control
          if ( $RemoteControlAllowed ) {
              $RemoteControlAllowed = "Device allowing RemoteControl";
          } else {
              $RemoteControlAllowed = "Device cancelled RemoteControl";
          }



          // put data into IP Symcon Vars or Attribute
          $this->WriteAttributeString( 'remoteControlAllowed', $RemoteControlAllowed );
          $this->WriteAttributeString( 'remoteStartAllowed', $RemoteControlStartAllowed );
          $this->SetValue("door", $DoorState );
          $this->SetValue("temperature", $Temperature );
          $this->SetValue("state", $OperationState );

          $this->SetValue( "LastRefresh", time() );
      }


      /** Translate the Home Connect api return
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
          if (!IPS_VariableProfileExists('HC_OvenState')) {
              IPS_CreateVariableProfile('HC_OvenState', 1);
              IPS_SetVariableProfileIcon('HC_OvenState', 'Power');
              IPS_SetVariableProfileAssociation("HC_OvenState", 0, "Standby", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_OvenState", 1, "Ready", "", 0x22ff00 );
              IPS_SetVariableProfileAssociation("HC_OvenState", 2, "Program running", "", 0xfc0303 );
          }
          if (!IPS_VariableProfileExists('HC_DoorState')) {
              IPS_CreateVariableProfile('HC_DoorState', 0);
              IPS_SetVariableProfileIcon('HC_DoorState', 'Lock');
              IPS_SetVariableProfileAssociation("HC_DoorState", false, "Closed", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_DoorState", true, "Open", "", 0xcf0000 );
          }
          if (!IPS_VariableProfileExists('HC_HeatMode')) {
              IPS_CreateVariableProfile('HC_HeatMode', 1);
              IPS_SetVariableProfileIcon('HC_HeatMode', 'Temperature');
              IPS_SetVariableProfileAssociation("HC_HeatMode", 0, "Top/Bottom", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_HeatMode", 1, "Circulation air", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_HeatMode", 2, "Pre heat", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_HeatMode", 3, "Pizza Mode", "", 0x828282 );
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
                  "image" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAACWCAYAAAAonXpvAAAEuGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgZXhpZjpQaXhlbFhEaW1lbnNpb249IjUwMCIKICAgZXhpZjpQaXhlbFlEaW1lbnNpb249IjE1MCIKICAgZXhpZjpDb2xvclNwYWNlPSIxIgogICB0aWZmOkltYWdlV2lkdGg9IjUwMCIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMTUwIgogICB0aWZmOlJlc29sdXRpb25Vbml0PSIyIgogICB0aWZmOlhSZXNvbHV0aW9uPSI0MDAuMCIKICAgdGlmZjpZUmVzb2x1dGlvbj0iNDAwLjAiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIw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",
              ],
              [
                  "type" => "List",
                  "name" => "DeviceInfo",
                  "caption" => "Information to the Home Connect Device [ Oven ]",
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