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
          $this->RegisterAttributeString("remoteControlAllowed", "Your Device doesn't allow remote Control / Dein Gerät erlaubt keine Fernbedienung" );
          $this->RegisterAttributeString("remoteStartAllowed", "Your Device doesn't allow remote Start / Dein Gerät erlaub keinen Fernstart" );

          // Erstellt einen Timer mit dem Namen "Update" und einem Intervall von 5 minutes.
          $this->RegisterTimer("refresh", 300000, "HomeConnectDishwasher_refresh($this->InstanceID);");

          // Register Variable and Profiles
          $this->registerProfiles();

          $this->RegisterVariableInteger('LastRefresh', "Last Refresh", "UnixTimestamp", -2 );
          $this->RegisterVariableBoolean("remoteStart", "Remote start", "HC_WasherRemoteStart", -1 );
          $this->RegisterVariableInteger("state", "Device State", "HC_WasherState", 0 );
          $this->RegisterVariableInteger("mode", "Device Mode", "HC_WasherMode", 1 );
          $this->RegisterVariableInteger("progress", "Progress", "HC_WasherProgress", 7 );
          $this->RegisterVariableInteger("remainTime", "Remaining Time", "UnixTimestampTime", 8 );
          $this->RegisterVariableBoolean("door", "Door State", "HC_WasherDoorState", 9 );
      }

      /** This function will be called by IP Symcon when the User change vars in the Module Interface
       * @return bool|void
       */
      public function ApplyChanges()
      {
          // Overwrite ips function
          parent::ApplyChanges();
      }


    /** Function to refresh the device values
     * @return string could return error
     */
      public function refresh() {
          //============================================================ Check Timer
          $hour = date('G');

          if ( $hour >= $this->ReadPropertyInteger("first_refresh") && $hour <= $this->ReadPropertyInteger("second_refresh") ) {
              // Setting timer
              $this->SetTimerInterval("refresh", 300000 );
          } else {
              // Setting timer slow
              $this->SetTimerInterval("refresh", 900000 );
          }
          //============================================================ Check Timer

          $recall = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/status");

          // catch null exception
          if ( $recall == null ) { return "error"; }

          // Getting each data into variables
          // Check Remote control
          if ( $recall['data']['status'][1]['value'] ) {
              $this->WriteAttributeString("remoteControlAllowed", "Your Device does allow remote Control / Dein Gerät erlaubt eine Fernbedienung");
          } else {
              $this->WriteAttributeString("remoteControlAllowed", "Your Device doesn't allow remote Control / Dein Gerät erlaubt keine Fernbedienung");
          }
          // Check Remote start
          if ( $recall['data']['status'][0]['value'] ) {
              $this->WriteAttributeString("remoteStartAllowed", "Your Device does allow remote Start / Dein Gerät erlaub eine Fernstart" );
          } else {
              $this->WriteAttributeString("remoteStartAllowed", "Your Device doesn't allow remote Start / Dein Gerät erlaub keinen Fernstart" );
          }

          //============================================================ Sorting Data and save
          // Door State and Operation state
          $DoorState =  $this->HC( $recall['data']['status'][2]['value'] );
          $OperationState = $this->HC( $recall['data']['status'][3]['value'] );

          if ( $OperationState == 2 ) {
              // Api call
              $recallProgram = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active")['data'];
              // filter data
              $program = $this->IPS( $recallProgram['key'] );
              $program_remaining_time = $recallProgram['options'][7]['value'];
              $program_progress = $recallProgram['options'][6]['value'];

          } else {
              // Api call
              $recallSelected = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/selected")['data'];
              $program = $this->IPS( $recallSelected['key'] );
              $program_remaining_time = 0;
              $program_progress = 0;
          }

          // Set Variable value
          $this->SetValue("remoteStart", $recall['data']['status'][0]['value'] );
          $this->SetValue("mode", $program );
          $this->SetValue("progress", $program_progress );
          $this->SetValue("remainTime", $program_remaining_time);
          $this->SetValue("door", $DoorState );
          $this->SetValue("state", $OperationState );
          $this->SetValue( "LastRefresh", time() );
          //============================================================ Sorting Data and save
          return true;
      }

    /** Function to start Modes for the Dishwasher
     * @param string $mode Mode
     * @return array Api return
     */
      public function start( string $mode ) {

          $recall = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/status");

          // Get Program
          switch( $mode ) {
              case "Auto1":
                  $mode = "Dishcare.Dishwasher.Program.Auto1";
                  break;
              case "Auto2":
                  $mode = "Dishcare.Dishwasher.Program.Auto2";
                  break;
              case "Auto3":
                  $mode = "Dishcare.Dishwasher.Program.Auto3";
                  break;
              case "Eco50":
                  $mode = "Dishcare.Dishwasher.Program.Eco50";
                  break;
              case "Quick45":
                  $mode = "Dishcare.Dishwasher.Program.Quick45";
                  break;
              default:
                  $mode = "Dishcare.Dishwasher.Program.Auto2";
          }

          // Settings
          $opt = "{ 'data':{ 'key'': $mode, 'options':[ { 'key':'BSH.Common.Option.StartInRelative', 'value':1800, 'unit':'seconds' } ] } }";

          // Send
          if ( $recall['data']['status'][0]['value'] ) {
              if ( $this->HC( $recall['data']['status'][3]['value'] ) == 2 ) {
                  return Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active", $opt);
              } else {
                  $this->turnOn();
                  return Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active", $opt);
              }
          } else {
              throw new LogicException("Remote start must be allowed");
          }
      }

    /** Function to stop a running program
     * @return array|null Api return
     */
      public function stop() {

          $recall = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/status");

          if ( $recall['data']['status'][1]['value'] ) {
              return Api_delete("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active", );
          } else {
              throw new LogicException("Remote control must be allowed");
          }
      }

    /**
     * Function to turn the dishwasher on
     */
      public function turnOn() {
          $power = '{"data": { "key": "BSH.Common.Setting.PowerState", "value": "BSH.Common.EnumType.PowerState.On" }';
          Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/settings/BSH.Common.Setting.PowerState", $power);
      }

      /** This Function will register all Profiles for the Module
       */
      protected function registerProfiles() {
          // Generate Variable Profiles
          if (!IPS_VariableProfileExists('HC_WasherState')) {
              IPS_CreateVariableProfile('HC_WasherState', 1);
              IPS_SetVariableProfileIcon('HC_WasherState', 'Power');
              IPS_SetVariableProfileAssociation("HC_WasherState", 0, "Standby", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherState", 1, "Ready", "", 0x22ff00 );
              IPS_SetVariableProfileAssociation("HC_WasherState", 2, "Program running", "", 0xfc0303 );
          }
          if (!IPS_VariableProfileExists('HC_WasherMode')) {
              IPS_CreateVariableProfile('HC_WasherMode', 1);
              IPS_SetVariableProfileIcon('HC_WasherMode', 'Drops');
              IPS_SetVariableProfileAssociation("HC_WasherMode", 0, "Auto lightly", "",0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 1, "Auto normally", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 2, "Auto highly", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 3, "Auto half Load", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 4, "Eco 50°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 5, "Quick 45°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 6, "Quick 65°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 7, "Intensiv 45°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 8, "Intensiv 70°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 9, "Intensiv Power", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 10, "Normal 45°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 11, "Normal 65°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 12, "Glas 40°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 13, "Glass care", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 14, "Night wash", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 15, "Magic daily", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 16, "Kurz 60°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 17, "Super 60°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 18, "Express Sparkle 65°C", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 19, "Machine care", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 20, "Steam fresh", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherMode", 21, "Maximum cleaning", "", 0x828282 );
          }
          if (!IPS_VariableProfileExists('HC_WasherProgress')) {
              IPS_CreateVariableProfile('HC_WasherProgress', 1);
              IPS_SetVariableProfileIcon('HC_WasherProgress', 'Hourglass');
              IPS_SetVariableProfileText("HC_WasherProgress", "", "%");
          }
          if (!IPS_VariableProfileExists('HC_WasherDoorState')) {
              IPS_CreateVariableProfile('HC_WasherDoorState', 0);
              IPS_SetVariableProfileIcon('HC_WasherDoorState', 'Lock');
              IPS_SetVariableProfileAssociation("HC_WasherDoorState", false, "Closed", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherDoorState", true, "Open", "", 0xcf0000 );
          }
          if (!IPS_VariableProfileExists('HC_WasherRemoteStart')) {
              IPS_CreateVariableProfile('HC_WasherRemoteStart', 0);
              IPS_SetVariableProfileIcon('HC_WasherRemoteStart', 'Lock');
              IPS_SetVariableProfileAssociation("HC_WasherRemoteStart", false, "Not allowed", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_WasherRemoteStart", true, "allowed", "", 0xcf0000 );
          }
      }



      //-----------------------------------------------------< Setting Form.json >------------------------------
      /** This Function will set the IP Symcon Form.json
       * @return false|string Form json
       */
      public function GetConfigurationForm() {
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
                          "type" => "Label",
                          "name" => "refresh Info",
                          "caption" => "The system will still update anyway, only with longer intervals between each refresh."
                      ],
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

      /**
       * @param string $var that should be analyse
       * @return bool returns true or false for HomeConnect Api result
       */
      public function HC($var ) {
        switch ( $var ) {
            //------------------------ DOOR
            case "BSH.Common.EnumType.DoorState.Open":
                return true;
            case "BSH.Common.EnumType.DoorState.Closed":
                return false;
            //------------------------ OPERATION STATE
            case "BSH.Common.EnumType.OperationState.Inactive":
                return 0;
            case "BSH.Common.EnumType.OperationState.Ready":
                return 1;
            case "BSH.Common.EnumType.OperationState.Run":
                return 2;
        }
        return 0;
    }

      /**
       * @param string $var that should be analyse
       * @return bool returns true or false for HomeConnect Api result
       */
       public function IPS($var ) {
        switch ( $var ) {
            //------------------------ Programms
            case "Dishcare.Dishwasher.Program.Auto1":
                return 0;
            case "Dishcare.Dishwasher.Program.Auto2":
                return 1;
            case "Dishcare.Dishwasher.Program.Auto3":
                return 2;
            case "Dishcare.Dishwasher.Program.AutoHalfLoad":
                return 3;
            case "Dishcare.Dishwasher.Program.Eco50":
                return 4;
            case "Dishcare.Dishwasher.Program.Quick45":
                return 5;
            case "Dishcare.Dishwasher.Program.Quick65":
                return 6;
            case "Dishcare.Dishwasher.Program.Intensiv45":
                return 7;
            case "Dishcare.Dishwasher.Program.Intensiv70":
                return 8;
            case "Dishcare.Dishwasher.Program.IntensivPower":
                return 9;
            case "Dishcare.Dishwasher.Program.Normal45":
                return 10;
            case "Dishcare.Dishwasher.Program.Normal65":
                return 11;
            case "Dishcare.Dishwasher.Program.Glas40":
                return 12;
            case "Dishcare.Dishwasher.Program.GlassCare":
                return 13;
            case "Dishcare.Dishwasher.Program.NightWash":
                return 14;
            case "Dishcare.Dishwasher.Program.MagicDaily":
                return 15;
            case "Dishcare.Dishwasher.Program.Kurz60":
                return 16;
            case "Dishcare.Dishwasher.Program.Super60":
                return 17;
            case "Dishcare.Dishwasher.Program.ExpressSparkle65":
                return 18;
            case "Dishcare.Dishwasher.Program.MachineCare":
                return 19;
            case "Dishcare.Dishwasher.Program.SteamFresh":
                return 20;
            case "Dishcare.Dishwasher.Program.MaximumCleaning":
                return 21;
        }
        return 0;
    }
  }
?>