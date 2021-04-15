<?php

require_once( dirname(dirname(__FILE__) ) . "/libs/tools/HomeConnectApi.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );

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

          // Refresh Settings
          $this->RegisterPropertyInteger("first_refresh", 1);
          $this->RegisterPropertyInteger("second_refresh", 1);
          $this->RegisterPropertyBoolean("refresh_on_off", true);
          // Notify Settings
          $this->RegisterPropertyInteger("notify_instance", 0);
          $this->RegisterPropertyString("notify_sound", "");
          $this->RegisterPropertyBoolean("notify_start", false);
          $this->RegisterPropertyBoolean("notify_stop", false);
          $this->RegisterPropertyBoolean("notify_finish", false);
          // Notify Settings
          $this->RegisterPropertyInteger("web_notify_instance", 0);
          $this->RegisterPropertyInteger("web_notify_Timeout", 10);
          $this->RegisterPropertyBoolean("web_notify_start", false);
          $this->RegisterPropertyBoolean("web_notify_stop", false);
          $this->RegisterPropertyBoolean("web_notify_finish", false);


          // Register Information Panel
          $this->RegisterAttributeString("remoteControlAllowed", "Dein Gerät erlaubt keine Fernbedienung");
          $this->RegisterAttributeString("remoteStartAllowed", "Dein Gerät erlaub keinen Fernstart");
          $this->RegisterAttributeBoolean("first_start", true );

          // Erstellt einen Timer mit dem Namen "Update" und einem Intervall von 5 minutes.
          $this->RegisterTimer("refresh", 300000, "HCDishwasher_refresh($this->InstanceID);");

          // Register Variable and Profiles
          $this->registerProfiles();

          $this->RegisterVariableBoolean("remoteControl", "Remote control", "HC_OvenRemoteStart", -2);
          IPS_SetHidden($this->GetIDForIdent("remoteControl"), true);
          $this->RegisterVariableInteger('LastRefresh', "Last Refresh", "UnixTimestamp", -2);
          IPS_SetHidden($this->GetIDForIdent('LastRefresh'), true);
          $this->RegisterVariableInteger("state", "Geräte Zustand", "HC_OvenState", 0);
          $this->EnableAction('state');
          $this->RegisterVariableInteger("mode", "Programm", "HC_OvenMode", 1);
          $this->EnableAction('mode');
          $this->RegisterVariableBoolean("remoteStart", "Remote start", "HC_OvenRemoteStart", 2);
          $this->RegisterVariableBoolean("door", "Tür Zustand", "HC_OvenDoorState", 3);
          $this->RegisterVariableInteger("remainTime", "Verbleibende Zeit", "UnixTimestampTime", 4);
          $this->RegisterVariableInteger("progress", "Fortschritt", "HC_OvenProgress", 5);
          $this->RegisterVariableBoolean("start_stop", "Programm start/stop", "HC_OvenStartStop", 6);
          $this->EnableAction('start_stop');
      }

      /** This function will be called by IP Symcon when the User change vars in the Module Interface
       * @return bool|void
       */
      public function ApplyChanges() {
          // Overwrite ips function
          parent::ApplyChanges();
      }


      //--------------------------------------------------< Reaction >----------------------------------------
      public function RequestAction($Ident, $Value) {
          switch ($Ident) {
              case 'state':
                  if ($this->GetValue("state") != 2) {
                      if ($Value) {
                          $this->SetActive(true);
                          $this->SetValue('state', 1);
                      } else {
                          $this->SetActive(false);
                          $this->SetValue('state', 0);
                      }
                  }
                  break;
              case 'mode':
                  $this->SetValue('mode', $Value);
                  break;
              case 'start_stop':
                  //TODO: start and stop Device
                  if ($Value) {
                      $this->start($this->GetListValue());
                      $this->SetValue('start_stop', true);
                      $this->refresh();
                  } else {
                      $this->stop();
                      $this->SetValue('start_stop', false);
                      $this->refresh();
                  }
          }

          $this->Hide();
      }
    //--------------------------------------------------< User functions >----------------------------------
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

          // Only refresh when set
          if ( $this->ReadPropertyBoolean("refresh_on_off") ) {
              $recall = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/status");

              // catch null exception
              if ( $recall == null ) { return "error"; }

              // Getting each data into variables
              // Check Remote control
              if ( $recall['data']['status'][1]['value'] ) {
                  $this->WriteAttributeString("remoteControlAllowed", "Dein Gerät erlaubt eine Fernbedienung");
              } else {
                  $this->WriteAttributeString("remoteControlAllowed", "Dein Gerät erlaubt keine Fernbedienung");
              }
              // Check Remote start
              if ( $recall['data']['status'][0]['value'] ) {
                  $this->WriteAttributeString("remoteStartAllowed", "Dein Gerät erlaub ein Fernstart" );
              } else {
                  $this->WriteAttributeString("remoteStartAllowed", "Dein Gerät erlaub keinen Fernstart" );
              }

              //============================================================ Sorting Data and save
              // Door State and Operation state
              $DoorState =  $this->HC( $recall['data']['status'][2]['value'] );
              $OperationState = $this->HC( $recall['data']['status'][3]['value'] );

              if ( $OperationState == 2 ) {
                  // Api call
                  $recallProgram = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active")['data'];
                  // filter data
                  $this->SetListValue( explode( ".", $recallProgram['key'] )[3] );
                  $program_remaining_time = $recallProgram['options'][7]['value'] - 3600;
                  $program_progress = $recallProgram['options'][6]['value'];
                  $this->SetValue('start_stop', true );

              } else {
                  // Api call
                  $recallSelected = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/selected")['data'];
                  $this->SetListValue( explode( ".", $recallSelected['key'] )[3] );
                  $program_remaining_time = 0;
                  $program_progress = 0;
                  $this->SetValue('start_stop', false );
              }

              // Set Variable value
              $this->SetValue("remoteStart", $recall['data']['status'][0]['value'] );
              $this->SetValue("remoteControl", $recall['data']['status'][1]['value'] );
              $this->SetValue("progress", $program_progress );
              $this->SetValue("remainTime", $program_remaining_time);
              $this->SetValue("door", $DoorState );
              $this->SetValue("state", $OperationState );
              $this->SetValue( "LastRefresh", time() );
              //============================================================ Sorting Data and save
          }

          //============================================================ Check Notifications
          if ( $this->ReadPropertyBoolean("notify_finish") ) {
              if ( $this->GetValue("state") == 2 && $this->GetValue("remainTime") <= -3300 && $this->GetValue("remainTime") != 0 ) {
                  $this->SendNotify($this->ReadPropertyString("name") . " ist in unter 5min fertig");
              }
          }
          //============================================================ Check Notifications


          if ( $this->ReadAttributeBoolean("first_start") ) {
              $this->BuildList("HC_OvenMode");
              $this->WriteAttributeBoolean("first_start", false );
          }

          $this->Hide();
          return true;
      }

    /** Function to start Modes for the Dishwasher
     * @param string $mode Mode
     */
      public function start( string $mode ) {

          $this->SetActive( true );

          sleep(1);

          $this->refresh();

          $mode = "Cooking.Oven.Program.HeatingMode." . $mode;

          // Settings
          $opt = '{"data":{"key":"' . $mode . '","options":[{"key":"BSH.Common.Option.StartInRelative","value":3,"unit":"seconds"}]}}';

          // Send
          if ( $this->GetValue("remoteStart") ) {
              // Check Door state
              if ( !$this->GetValue("door") ) {
                  // Check if the device is on
                  if ( $this->GetValue("state") == 1 ) {
                      Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active", $opt);

                      //============================================================ Check Notifications
                      if ( $this->ReadPropertyBoolean("notify_start") ) {
                          $this->SendNotify($this->ReadPropertyString("name") . " hat das Programm " . $mode . " gestarted!");
                      }
                      //============================================================ Check Notifications
                  } else {
                      throw new UnexpectedValueException("Something went wrong (try again)");
                  }
              } else {
                  throw new LogicException("Door state must be closed");
              }
          } else {
              throw new LogicException("Remote start must be allowed");
          }
      }

    /**
     * Function to stop a running program
     */
      public function stop() {

          $this->refresh();

          if ( $this->GetValue("remoteControl") ) {
              if ( $this->GetValue("state") == 2 ) {
                  Api_delete("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active" );

                  //============================================================ Check Notifications
                  if ( $this->ReadPropertyBoolean("notify_stop") ) {
                      $this->SendNotify($this->ReadPropertyString("name") . " hat das Programm gestoppt!");
                  }
                  //============================================================ Check Notifications
              }
          } else {
              throw new LogicException("Remote control must be allowed");
          }
      }

    /**
     * Function to turn the dishwasher on
     * @param bool $state switch
     */
      public function SetActive( bool $state ) {
          if ( $state ) {
              $power = '{"data": {"key": "BSH.Common.Setting.PowerState","value": "BSH.Common.EnumType.PowerState.On","type": "BSH.Common.EnumType.PowerState"}}';
          } else {$power = '{"data": {"key": "BSH.Common.Setting.PowerState","value": "BSH.Common.EnumType.PowerState.Off","type": "BSH.Common.EnumType.PowerState"}}';

          }

          Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/settings/BSH.Common.Setting.PowerState", $power);
      }

      public function test( $type ) {
          switch ($type) {
              case "handy_message":
                  WFC_PushNotification( $this->ReadPropertyInteger("notify_instance"), "HomeConnect", "Test Message", $this->ReadPropertyString("notify_sound"), $this->InstanceID );
                  break;
              case "web_message":
                  WFC_SendNotification( $this->ReadPropertyInteger("web_notify_instance"), "HomeConnect", "Test Message", "Power", $this->ReadPropertyInteger("web_notify_Timeout") );
                  break;
          }
      }

    //-----------------------------------------------------< Profiles >------------------------------
      /** This Function will register all Profiles for the Module
       */
      protected function registerProfiles() {
          // Generate Variable Profiles
          if (!IPS_VariableProfileExists('HC_OvenState')) {
              IPS_CreateVariableProfile('HC_OvenState', 1);
              IPS_SetVariableProfileIcon('HC_OvenState', 'Power');
              IPS_SetVariableProfileValues("HC_OvenState", 0, 2, 0 );
              IPS_SetVariableProfileAssociation("HC_OvenState", 0, "Aus", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_OvenState", 1, "An", "", 0x22ff00 );
              IPS_SetVariableProfileAssociation("HC_OvenState", 2, "Program läuft", "", 0xfa3200 );
          }
          if (!IPS_VariableProfileExists('HC_OvenMode')) {
              IPS_CreateVariableProfile('HC_OvenMode', 1);
              IPS_SetVariableProfileIcon('HC_OvenMode', 'Drops');
          }
          if (!IPS_VariableProfileExists('HC_OvenProgress')) {
              IPS_CreateVariableProfile('HC_OvenProgress', 1);
              IPS_SetVariableProfileIcon('HC_OvenProgress', 'Hourglass');
              IPS_SetVariableProfileText("HC_OvenProgress", "", "%");
          }
          if (!IPS_VariableProfileExists('HC_OvenDoorState')) {
              IPS_CreateVariableProfile('HC_OvenDoorState', 0);
              IPS_SetVariableProfileIcon('HC_OvenDoorState', 'Lock');
              IPS_SetVariableProfileAssociation("HC_OvenDoorState", false, "Geschlossen", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_OvenDoorState", true, "Offen", "", 0xcf0000 );
          }
          if (!IPS_VariableProfileExists('HC_OvenStartStop')) {
              IPS_CreateVariableProfile('HC_OvenStartStop', 0);
              IPS_SetVariableProfileIcon('HC_OvenStartStop', 'Power');
              IPS_SetVariableProfileAssociation("HC_OvenStartStop", false, "Stop", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_OvenStartStop", true, "Start", "", 0x11ff00 );
          }
          if (!IPS_VariableProfileExists('HC_OvenRemoteStart')) {
              IPS_CreateVariableProfile('HC_OvenRemoteStart', 0);
              IPS_SetVariableProfileIcon('HC_OvenRemoteStart', 'Lock');
              IPS_SetVariableProfileAssociation("HC_OvenRemoteStart", false, "Nicht erlaubt", "", 0xfa3200 );
              IPS_SetVariableProfileAssociation("HC_OvenRemoteStart", true, "Erlaubt", "", 0x11ff00 );
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
          return[
              [
                  "type" => "Button",
                  "caption" => "Test Handy notify",
                  "onClick" => 'HCOven_test( $id, "handy_message" );',
              ],
              [
                  "type" => "Button",
                  "caption" => "Test Webfront notify",
                  "onClick" => 'HCOven_test( $id, "web_message" );',
              ],
              [
                  "type" => "Button",
                  "caption" => "Refresh",
                  "onClick" => 'HCOven_refresh( $id, );',
              ]
          ];
      }

      /**
       * @return array[] Form Elements
       */
      protected function FormElements() {
          return[
              [
                  "type" => "Image",
                  "image" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAACWCAYAAAAonXpvAAAF52lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgZXhpZjpDb2xvclNwYWNlPSIxIgogICBleGlmOlBpeGVsWERpbWVuc2lvbj0iNTAwIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMTUwIgogICBwaG90b3Nob3A6Q29sb3JNb2RlPSIzIgogICBwaG90b3Nob3A6SUNDUHJvZmlsZT0ic1JHQiBJRUM2MTk2Ni0yLjEiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjE1MCIKICAgdGlmZjpJbWFnZVdpZHRoPSI1MDAiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjQwMC4wIgog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"
              ],
              [
                  "type" => "List",
                  "name" => "Gerät Information",
                  "caption" => "Informationen zu diesem Gerät [ Ofen ]",
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
                  "caption" => "Berechtigungen die von deinem Gerät gesetzt werden",
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
                          "caption" => "Das System updated in dem Zeitraum alle 5min. Sonst nur 15min."
                      ],
                      [
                          "type" => "NumberSpinner",
                          "name" => "first_refresh",
                          "caption" => "Refreshen von " . $this->ReadPropertyInteger("first_refresh") . " Uhr",
                          "suffix" => "h",
                          "minimum" => "0",
                          "maximum" => "24",
                          "enabled" => true
                      ],
                      [
                          "type" => "NumberSpinner",
                          "name" => "second_refresh",
                          "caption" => "Bis " . $this->ReadPropertyInteger("second_refresh") . " Uhr",
                          "suffix" => "h",
                          "minimum" => "0",
                          "maximum" => "24",
                          "enabled" => true
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "refresh_on_off",
                          "caption" => "Refresh An/Aus",
                      ],
                  ],
              ],
              [
                  "type" => "ExpansionPanel",
                  "caption" => "Handy Notification Settings",
                  "items" => [
                      [
                          "type" => "SelectInstance",
                          "name" => "notify_instance",
                          "caption" => "Benachrichtigungs Instanz [Mobile / Handy]",
                      ],
                      [
                          "type" => "Select",
                          "name" => "notify_sound",
                          "caption" => "Benachrichtigungs Sound [nichts für den normalen]",
                          "options" => [
                              [ "caption" => "Normal", "value" => "" ],
                              [ "caption" => "alarm", "value" => "alarm" ],
                              [ "caption" => "bell", "value" => "bell" ],
                              [ "caption" => "boom", "value" => "boom" ],
                              [ "caption" => "buzzer", "value" => "buzzer" ],
                              [ "caption" => "connected", "value" => "connected" ],
                              [ "caption" => "dark", "value" => "dark" ],
                              [ "caption" => "digital", "value" => "digital" ],
                              [ "caption" => "drums", "value" => "drums" ],
                              [ "caption" => "duck", "value" => "duck" ],
                              [ "caption" => "full", "value" => "full" ],
                              [ "caption" => "happy", "value" => "happy" ],
                              [ "caption" => "horn", "value" => "horn" ],
                              [ "caption" => "inception", "value" => "inception" ],
                              [ "caption" => "kazoo", "value" => "kazoo" ],
                              [ "caption" => "roll", "value" => "roll" ],
                              [ "caption" => "siren", "value" => "siren" ],
                              [ "caption" => "space", "value" => "space" ],
                              [ "caption" => "trickling", "value" => "trickling" ],
                              [ "caption" => "turn", "value" => "turn" ],
                          ],
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "notify_start",
                          "caption" => "Startbenachrichtigung",
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "notify_stop",
                          "caption" => "Stopbenachrichtigung",
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "notify_finish",
                          "caption" => "Benachrichtigung wenn fertig",
                      ],
                  ],
              ],
              [
                  "type" => "ExpansionPanel",
                  "caption" => "Webfront Notification Settings",
                  "items" => [
                      [
                          "type" => "SelectInstance",
                          "name" => "web_notify_instance",
                          "caption" => "Benachrichtigungs Instanz [Mobile / Handy]",
                      ],
                      [
                          "type" => "NumberSpinner",
                          "name" => "web_notify_Timeout",
                          "caption" => "Nachrichten Timeout",
                          "suffix" => "sec",
                          "minimum" => "0",
                          "maximum" => "300",
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "web_notify_start",
                          "caption" => "Startbenachrichtigung",
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "web_notify_stop",
                          "caption" => "Stopbenachrichtigung",
                      ],
                      [
                          "type" => "CheckBox",
                          "name" => "web_notify_finish",
                          "caption" => "Benachrichtigung wenn fertig",
                      ],
                  ],
              ],
          ];
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

      protected function Hide() {
          switch ($this->GetValue('state')) {
              case 0:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), true );
                  IPS_SetHidden( $this->GetIDForIdent('door'), true );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), true );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), true );
                  break;
              case 1:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), true );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), true );
                  break;
              case 2:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), false );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), false );
                  break;
              default:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), false );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), false );
          }
      }

      /** Send Text
       * @param string $text Text in the Notification
       */
      protected function SendNotify( string $text ) {
          if ( $this->ReadPropertyInteger("notify_instance") != 0 ) {
              WFC_PushNotification( $this->ReadPropertyInteger("notify_instance"), "HomeConnect", $text, $this->ReadPropertyString("notify_sound"), $this->InstanceID );
          }
          if ( $this->ReadPropertyInteger("web_notify_instance") != 0 ) {
              WFC_SendNotification( $this->ReadPropertyInteger("web_notify_instance"), "HomeConnect", $text, "Power", $this->ReadPropertyInteger("web_notify_Timeout") );
          }
      }

      /** Function to set Profile of a Integer Var
       * @param string $profile Name of the profile
       */
      protected function BuildList( string $profile ) {
          $programs = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/available")['data']['programs'];
          $programs_count = count( $programs );

          for ($i = 0; $i < $programs_count ; $i++) {
              IPS_SetVariableProfileAssociation($profile, $i, explode( ".", $programs[$i]["key"])[3], "", 0x828282 );
          }
      }

      /** Function to set integer by name association
       * @param string $name
       */
      protected function SetListValue( string $name ) {
          $profile = IPS_GetVariableProfile( 'HC_OvenMode' )['Associations'];
          $profile_count = count( $profile );

          $profile_list = array();

          for ( $i = 0; $i < $profile_count; $i++ ) {
              $profile_list[$profile[$i]["Name"]] = $profile[$i]["Value"];
          }

          $this->SetValue('mode', $profile_list[$name] );
      }

      /**Function to get name to association
       * @return mixed return name
       */
      protected function GetListValue() {
          $profile = IPS_GetVariableProfile( 'HC_OvenMode' )['Associations'];
          $profile_count = count( $profile );

          $profile_list = array();

          for ( $i = 0; $i < $profile_count; $i++ ) {
              $profile_list[$profile[$i]["Value"]] = $profile[$i]["Name"];
          }

          return $profile_list[$this->GetValue('mode' )];
      }

      /**
       * @param string $var that should be analyse
       * @return bool returns true or false for HomeConnect Api result
       */
      private function HC($var ) {
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
  }
?>