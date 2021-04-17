<?php

require_once( dirname(dirname(__FILE__) ) . "/libs/tools/HomeConnectApi.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );

class HomeConnectDishwasher extends IPSModule {

      /** This function will be called on the creation of this Module
       * @return bool|void
       */
      public function Create()
      {
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
          $this->RegisterTimer("DownCountStart", 0, "HCDishwasher_DownCount($this->InstanceID, 'remainStartTime'");
          $this->RegisterTimer("DownCountProgram", 0, "HCDishwasher_DownCount($this->InstanceID, 'remainTime');");

          // Register Variable and Profiles
          $this->registerProfiles();

          $this->RegisterVariableBoolean("remoteControl", "Remote control", "HC_DishwasherRemoteStart", -2);
          IPS_SetHidden($this->GetIDForIdent("remoteControl"), true);
          $this->RegisterVariableInteger('LastRefresh', "Last Refresh", "UnixTimestamp", -2);
          IPS_SetHidden($this->GetIDForIdent('LastRefresh'), true);
          $this->RegisterVariableInteger("state", "Geräte Zustand", "HC_DishwasherState", 0);
          $this->EnableAction('state');
          $this->RegisterVariableString("remainStartTime", "Start in", "", 1);
          $this->RegisterVariableInteger("mode", "Programm", "HC_DishwasherMode", 2);
          $this->EnableAction('mode');
          $this->RegisterVariableBoolean("remoteStart", "Remote start", "HC_DishwasherRemoteStart", 3);
          $this->RegisterVariableBoolean("door", "Tür Zustand", "HC_DishwasherDoorState", 4);
          $this->RegisterVariableString("remainTime", "Verbleibende Programm Zeit", "", 5);
          $this->RegisterVariableInteger("progress", "Fortschritt", "HC_DishwasherProgress", 6);
          $this->RegisterVariableBoolean("start_stop", "Programm start/stop", "HC_DishwasherStartStop", 7);
          $this->EnableAction('start_stop');
      }

      /** This function will be called by IP Symcon when the User change vars in the Module Interface
       * @return bool|void
       */
      public function ApplyChanges()
      {
          // Overwrite ips function
          parent::ApplyChanges();
      }


      //--------------------------------------------------< Reaction >----------------------------------------
      public function RequestAction($Ident, $Value)
      {
          switch ($Ident) {
              case 'state':
                  if ($this->GetValue("state") < 3) {
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
                  if ($Value) {
                      $program = $this->GetListValue();
                      $this->start($program, 3);
                      $this->SetValue('start_stop', true);
                  } else {
                      $this->stop();
                      $this->SetValue('start_stop', false);
                  }
          }

          $this->Hide();
      }
    //--------------------------------------------------< User functions >----------------------------------
    /** Function to refresh the device values
     * @return string could return error
     */
      public function refresh() {
          //====================================================================================================================== Check Timer
          $hour = date('G');

          if ( $hour >= $this->ReadPropertyInteger("first_refresh") && $hour <= $this->ReadPropertyInteger("second_refresh") ) {
              // Setting timer
              $this->SetTimerInterval("refresh", 300000 );
          } else {
              // Setting timer slow
              $this->SetTimerInterval("refresh", 900000 );
          }
          //====================================================================================================================== Check Timer

          //====================================================================================================================== Refreshing
          if ( $this->ReadPropertyBoolean("refresh_on_off") ) {
              //make api call
              $recall_api = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/status");
              // catch null exception
              if ( $recall_api == null ) { return "error"; }
              // Build Options
              $options_recall = $this->getKeys($recall_api, 'status');

              //================================================================================================================== Refreshing
              if ( $options_recall['BSH.Common.Status.RemoteControlActive'] ) {
                  $this->WriteAttributeString("remoteControlAllowed", "Dein Gerät erlaubt eine Fernbedienung");
              } else {
                  $this->WriteAttributeString("remoteControlAllowed", "Dein Gerät erlaubt keine Fernbedienung");
              }
              // Check Remote start
              if ( $options_recall['BSH.Common.Status.RemoteControlStartAllowed'] ) {
                  $this->WriteAttributeString("remoteStartAllowed", "Dein Gerät erlaub ein Fernstart" );
              } else {
                  $this->WriteAttributeString("remoteStartAllowed", "Dein Gerät erlaub keinen Fernstart" );
              }

              //============================================================ Sorting Data and save
              // Door State and Operation state
              $DoorState =  $this->HC( $options_recall['BSH.Common.Status.DoorState'] );
              $OperationState = $this->HC( $options_recall['BSH.Common.Status.OperationState'] );

              $program_remaining_time = "==:==:==";
              $program_remaining_start_time = "==:==:==";

              if ( $OperationState == 3 || $OperationState == 2 ) {
                  // Api call
                  $recallProgram = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active");
                  // filter data
                  $options = $this->getKeys($recallProgram, 'options');

                  $this->SetListValue( explode( ".", $recallProgram['data']['key'] )[3] );

                  if ( $OperationState == 2 ) {
                      // Register DownCount and set time to start
                      $program_remaining_start_time = gmdate("H:i:s", $options['BSH.Common.Option.StartInRelative']);
                      $this->SetTimerInterval('DownCountStart', 1001);
                      $this->SetTimerInterval('DownCountProgram', 0);
                  } else if ( $OperationState == 3 ){
                      $this->SetTimerInterval('DownCountProgram', 1001);
                      $this->SetTimerInterval('DownCountStart', 0);
                  }
                  $program_remaining_time = gmdate("H:i:s", $options['BSH.Common.Option.RemainingProgramTime']);
                  $program_progress = $options['BSH.Common.Option.ProgramProgress'];
                  $this->SetValue('start_stop', true );

              } else {
                  // Api call
                  $this->SetTimerInterval('DownCountStart', 0);
                  $this->SetTimerInterval('DownCountProgram', 0);
                  $recallSelected = Api("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/selected")['data'];
                  $this->SetListValue( explode( ".", $recallSelected['key'] )[3] );
                  $program_remaining_time = "00:00:00";
                  $program_progress = 0;
                  $this->SetValue('start_stop', false );
              }

              // Set Variable value
              $this->SetValue("remoteStart", $options_recall['BSH.Common.Status.RemoteControlStartAllowed'] );
              $this->SetValue("remoteControl", $options_recall['BSH.Common.Status.RemoteControlActive'] );
              $this->SetValue("progress", $program_progress );
              $this->SetValue("remainTime", $program_remaining_time);
              $this->SetValue("remainStartTime", $program_remaining_start_time );
              $this->SetValue("door", $DoorState );
              $this->SetValue("state", $OperationState );
              $this->SetValue( "LastRefresh", time() );
              //============================================================ Sorting Data and save
          } else {
              $this->SetTimerInterval('DownCountStart', 0);
              $this->SetTimerInterval('DownCountProgram', 0);
          }

          //============================================================ Check Notifications
          if ( $this->ReadPropertyBoolean("notify_finish") ) {
              $now = "1970-01-01 " . $this->GetValue("remainTime");
              $set = date("H:i:s", strtotime($now));

              if ( $this->GetValue("state") == 3 && $set <= 300 && $this->GetValue("remainTime") != 0 ) {
                  $this->SendNotify($this->ReadPropertyString("name") . " ist in unter 5min fertig");
              }
          }
          //============================================================ Check Notifications


          if ( $this->ReadAttributeBoolean("first_start") ) {
              $this->BuildList("HC_DishwasherMode");
              $this->WriteAttributeBoolean("first_start", false );
          }

          $this->Hide();
          return true;
      }

    /** Function to start Modes for the Dishwasher
     * @param string $mode Mode
     * @param int $delay Delay in seconds until the device starts
     */
      public function start( string $mode, int $delay )
      {

          $this->SetActive(true);

          sleep(1);

          $this->refresh();

          $run_program = "Dishcare.Dishwasher.Program." . $mode;

          // Settings
          $opt = '{"data":{"key":"' . $run_program . '","options":[{"key":"BSH.Common.Option.StartInRelative","value":' . $delay . ',"unit":"seconds"}]}}';

          // Send
          if ($this->GetValue("remoteStart")) {
              // Check Door state
              if (!$this->GetValue("door")) {
                  // Check if the device is on
                  if ($this->GetValue("state") == 1) {
                      Api_put("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active", $opt);

                      //============================================================ Check Notifications
                      if ($this->ReadPropertyBoolean("notify_start")) {
                          $this->SendNotify($this->ReadPropertyString("name") . " hat das Programm " . explode(".", $mode)[3] . " gestarted!");
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
              if ( $this->GetValue("state") == 3 ) {
                  Api_delete("homeappliances/" . $this->ReadPropertyString("haId") . "/programs/active" );

                  //============================================================ Check Notifications
                  if ( $this->ReadPropertyBoolean("notify_stop") ) {
                      $this->SendNotify($this->ReadPropertyString("name") . " hat das Programm gestoppt!");
                  }
                  //============================================================ Check Notifications
              } else if ( $this->GetValue("state") == 2 ) {
                  $this->SetActive(false);

                  //============================================================ Check Notifications
                  if ( $this->ReadPropertyBoolean("notify_stop") ) {
                      $this->SendNotify($this->ReadPropertyString("name") . " hat den Verzögerten start und das Programm gestoppt!");
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
          if (!IPS_VariableProfileExists('HC_DishwasherState')) {
              IPS_CreateVariableProfile('HC_DishwasherState', 1);
              IPS_SetVariableProfileIcon('HC_DishwasherState', 'Power');
              IPS_SetVariableProfileValues("HC_DishwasherState", 0, 2, 0 );
              IPS_SetVariableProfileAssociation("HC_DishwasherState", 0, "Aus", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_DishwasherState", 1, "An", "", 0x22ff00 );
              IPS_SetVariableProfileAssociation("HC_DishwasherState", 2, "Verzögerter Start", "", 0xfa8e00 );
              IPS_SetVariableProfileAssociation("HC_DishwasherState", 3, "Program läuft", "", 0xfa3200 );
          }
          if (!IPS_VariableProfileExists("HC_DishwasherMode") ) {
              IPS_CreateVariableProfile("HC_DishwasherMode", 1);
              IPS_SetVariableProfileIcon("HC_DishwasherMode", 'Drops');
          }
          if (!IPS_VariableProfileExists('HC_DishwasherProgress')) {
              IPS_CreateVariableProfile('HC_DishwasherProgress', 1);
              IPS_SetVariableProfileIcon('HC_DishwasherProgress', 'Hourglass');
              IPS_SetVariableProfileText("HC_DishwasherProgress", "", "%");
          }
          if (!IPS_VariableProfileExists('HC_DishwasherDoorState')) {
              IPS_CreateVariableProfile('HC_DishwasherDoorState', 0);
              IPS_SetVariableProfileIcon('HC_DishwasherDoorState', 'Lock');
              IPS_SetVariableProfileAssociation("HC_DishwasherDoorState", false, "Geschlossen", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_DishwasherDoorState", true, "Offen", "", 0xcf0000 );
          }
          if (!IPS_VariableProfileExists('HC_DishwasherStartStop')) {
              IPS_CreateVariableProfile('HC_DishwasherStartStop', 0);
              IPS_SetVariableProfileIcon('HC_DishwasherStartStop', 'Power');
              IPS_SetVariableProfileAssociation("HC_DishwasherStartStop", false, "Stop", "", 0x828282 );
              IPS_SetVariableProfileAssociation("HC_DishwasherStartStop", true, "Start", "", 0x11ff00 );
          }
          if (!IPS_VariableProfileExists('HC_DishwasherRemoteStart')) {
              IPS_CreateVariableProfile('HC_DishwasherRemoteStart', 0);
              IPS_SetVariableProfileIcon('HC_DishwasherRemoteStart', 'Lock');
              IPS_SetVariableProfileAssociation("HC_DishwasherRemoteStart", false, "Nicht erlaubt", "", 0xfa3200 );
              IPS_SetVariableProfileAssociation("HC_DishwasherRemoteStart", true, "Erlaubt", "", 0x11ff00 );
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
                  "onClick" => 'HCDishwasher_test( $id, "handy_message" );',
              ],
              [
                  "type" => "Button",
                  "caption" => "Test Webfront notify",
                  "onClick" => 'HCDishwasher_test( $id, "web_message" );',
              ],
              [
                  "type" => "Button",
                  "caption" => "Refresh",
                  "onClick" => 'HCDishwasher_refresh( $id, );',
              ],
              [
                  "type" => "Button",
                  "caption" => "Profile refresh [nur bei falschen oder zu  wenig Daten]",
                  "onClick" => 'HCDishwasher_BuildList( $id, "HC_DishwasherMode");',
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
                  "image" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAACWCAYAAAAonXpvAAAF52lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgZXhpZjpDb2xvclNwYWNlPSIxIgogICBleGlmOlBpeGVsWERpbWVuc2lvbj0iNTAwIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMTUwIgogICBwaG90b3Nob3A6Q29sb3JNb2RlPSIzIgogICBwaG90b3Nob3A6SUNDUHJvZmlsZT0ic1JHQiBJRUM2MTk2Ni0yLjEiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjE1MCIKICAgdGlmZjpJbWFnZVdpZHRoPSI1MDAiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjQwMC4wIgogICB0aWZmOllSZXNvbHV0aW9uPSI0MDAuMCIKICAgeG1wOk1ldGFkYXRhRGF0ZT0iMjAyMS0wNC0xM1QxMToyMjowMiswMjowMCIKICAgeG1wOk1vZGlmeURhdGU9IjIwMjEtMDQtMTNUMTE6MjI6MDIrMDI6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgeG1wTU06YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgeG1wTU06c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgRGVzaWduZXIgMS45LjEiCiAgICAgIHhtcE1NOndoZW49IjIwMjEtMDMtMThUMjA6NDU6MTIrMDE6MDAiLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0icHJvZHVjZWQiCiAgICAgIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFmZmluaXR5IERlc2lnbmVyIDEuOS4yIgogICAgICBzdEV2dDp3aGVuPSIyMDIxLTA0LTEzVDExOjIyOjAyKzAyOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICAgPGRjOnRpdGxlPgogICAgPHJkZjpBbHQ+CiAgICAgPHJkZjpsaSB4bWw6bGFuZz0ieC1kZWZhdWx0Ij5JUFN5bWNvbkltZzwvcmRmOmxpPgogICAgPC9yZGY6QWx0PgogICA8L2RjOnRpdGxlPgogIDwvcmRmOkRlc2NyaXB0aW9uPgogPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KPD94cGFja2V0IGVuZD0iciI/PrUN+NwAAAGBaUNDUHNSR0IgSUVDNjE5NjYtMi4xAAAokXWRzytEURTHP/ODESOKhQU1aVihMWpio8ykoSZNY5Rfm5lnfqj58XpvJNkq2ylKbPxa8BewVdZKESnZKWtig57zzNRMMud27vnc773ndO+5YI1mlKxu90A2V9AiQb9rdm7e5XjGTjP1dEFM0dWxcDhETfu4w2LGm36zVu1z/1rTUkJXwNIgPKqoWkF4Qji0WlBN3hZuV9KxJeFT4T5NLih8a+rxEr+YnCrxl8laNBIAa6uwK1XF8SpW0lpWWF6OO5tZUcr3MV/iTORmpiV2i3eiEyGIHxeTjBPAxyAjMvvox8uArKiR7/nNnyIvuYrMKmtoLJMiTYE+UVekekJiUvSEjAxrZv//9lVPDnlL1Z1+qHsyjLcecGzBd9EwPg8N4/sIbI9wkavk5w9g+F30YkVz70PLBpxdVrT4DpxvQseDGtNiv5JN3JpMwusJNM9B2zU0LpR6Vt7n+B6i6/JVV7C7B71yvmXxB+gQZ6wnmSeCAAAACXBIWXMAAD2EAAA9hAHVrK90AAAYkElEQVR4nO3debxd4/XH8U9uEmIKialmV0XNY4mh5rFCTZEZEY0pNDGXLNGwDFEVhJoT80xRSYuihKL9GWueLmpoihBDRSa/P9aOnHKHc/bZ557p+3697uvKOXuv8+irsc7z7OdZC0RERERERERERERERERERERERERERERERERERERERERERERERERERERERCRjHco9gGI0NjZ2AtYDFi/3WOQHpgFPNzU1zSz3QERE6kGncg+gSDsBY4Hu5R6I/MBMYDhwa7kHIiJSD6o9ofcAVgAWKPdApFkbo4QuItIuGso9ABERESmeErqIiEgNUEIXERGpAdX+DL0lW5R7AHVmEeBYYIdyD0REpF7VZEJvamr6W7nHUE8aGxu7AR+XexwiIvVMS+4iIiI1QAldRESkBtTkkruISLmY2YLAcsnP8jn/vHPy+7/AR2UboNSKm9391NwXlNBFRFIysw7AGsD2wHbEhtwl27jtm1KPS+qTErqISAHMbEUigc9N4sskb80BngLuB94D3k9+NgdGEI84JwAHufu37TxsqQNK6CIirTCzxYAdmZfEV815+0WivPEDwMPuPi3nvm7AeGBP4Gsiof9KyVxKRQldROR7kqX0zYGDgT5Al+Stt4EriQT+oLtPaeH+TYGbgJWAu4A9gJvc/cvSjlzqmRK6iEjCzBYH9iMS+RrJy48D1wD3uftbbdzfABwNnAnMAoYSz9T3AK4v0bBFACV0EalzyWx8KyKJ7wPMD3wKXABc7u4v5BlnceBqoBfwCtDH3f9pZs8DU4H7SjB8ke/UZEJvbGxcte2rJEOLEuVfRaqGmS0JHEDMoldLXn4EuBy43d2/LiDWz4AbiWNqVwPD3P0rM1sHWAe41N1nZDl+ke+ryYQOXFvuAdSZTkBjuQchkg8z2wg4Dtgb6Ax8ApxLzMZfKTBWA3ACcBpxHG2wu1+dc8mxye/rih23SFtqNaFvWu4BiEhlMbMfAw70S156kJiN/8HdCz4bbmZLEc/WdyZ2u/dx95dy3t8E2B+4F3isuNGLtK1WE7qICPBd4jXgUGJGPhE4yd2fLyLm1sQS+zLAFcBwd/9vzvsdgPOB2cDROqom7aFWE7qWt9rXfMAmwMplHofId8xsYWLH+XHAwsDfgePd/eEiYnYERgKnECVcB7r7Dc1cOoBYKRyXO2sXKaVaTehHlHsAdWYx4HcooUsFMLPOwC+JpLs08DpwInBHMTNlM/sRMVnYHniOWGJ/rZnrFgLGEDvbf5P280QKVZMJvampaVrbV0lWGhsbG4CZ5R6H1LdkmXsf4AygBzAFOAy40t2L+v+nmW1PnCNfGrgEOMrdp7dw+QlEE5Yj3H1qMZ8rUoiaTOgiUl+SZ9pnE49+vgRGAWOLrcxmZp2SWJbE7evut7Ry/UrEEv+LwKXFfLZIoZTQRaRqmdnywO+B3YlVoguA0939PxnEXpbY+LYV8DSxxP5mG7eNIcrEjnD3WcWOQaQQSugiUnWS5fUDgbFAV+AWYud6Wwk33/i7EPUslgDGAce1dbTNzLYE+gJ3u/tfshiHSCGU0EWkqpjZCsBlwC7AB8RO83syit2JKBLza2AasI+735HHfQsSKwUzmVdMRqRdKaGLSFVIZuVDiKpuXYGriDPen2YUfwViiX0L4B/E8/KmPG+/CFgbGOnur2cxHpFCKaGLSMVLjoxdRVRl+wAY4O4TM4zfi6j61p34wnBivrXXzexAYDDwJ+CsrMYkUigldBGpaMkO9puAuUn9KHf/LKPYnYlWp8cQHdZ+4e5/LOD+dYml9n8B+7n7nCzGJZKGEnqJNDY2Lkh0cFoc6FiCj5gDfA682NTU9FUJ4ouUVU7jEwe+Bvq7+00Zxl+Z+KLQk+h53s/d3y3g/q7AbcTf7z7u/klWYxNJQwm9BJJkfjBwCLAspfnfeTbRJWpSY2PjUU1NTWrNKDXDzLoTS+C9iDPdvQvthNZG/D2BCUSVw7MBK6T4TPI8/wqigM0Id38iq7GJpKWEXho9iIS+eok/ZxHiS8OfgEx2+YqUm5ltDNwKrEQk9cPdPZNVKDObnzgrPhz4GNjV3f+UItQRwL7A7cTZd5GyU0IvjUWIzTXtoSOqoS41wswGE0fS5gBDibKtmXQqM7NViPPqGwGTiSX891PE6Un0LngDOEid1KRSKKGXxgfAK8BSQIcSf9ZnqNeyVLlkCfuU5OcdYE93fzbD+L2BK4kv2w6MTlPJLXkUcAvxhaO3u6tvhFQMJfTSeBc4mViWW4fowZy1mUATMZt5rgTxRdpFstP8MuLo11PAbu7+74xidyFm04cD/yGS8P0pYzUQjwBWBIa6u/7eSUVRQi+BpqamWcDkxsbGR9vhs7TcJ1UrZ6f4jsBEYqd5UQ1VcmL3IGbT6wMPERXlPiwmJLFJ7xpiti9SUZTQS0jJVqRlSWOVicC6RGeyI7JqaGJm/YlZ/0JET3J399lFxBsOjCZWww7Xc3OpREroItLuzGwt4F6ib/iJwJgskqSZLQCcT2yo+zdRKOahImMeApxH7IvZKasd9yJZU0IXkXaVJPOHiDPgA939hozirk4cd1sbuJ+o3DalyJgHAJcAbwLbZ9GWVaRUGso9ABGpH2a2BvAgkcx7Z5jM9yc21K0JjAR2ySCZ9wPGE7vut3P3D4oeqEgJaYYuIu3CzH5CJPPuwL7ufncGMRcCLiR2yL9PnC2fnEHcvYDrgA+JZJ53SViRclFCF5GSM7PViGX2JYm2pHdmEHMtYhf7mkS1xP3d/eMM4u4K3ExUktve3d8qNqZIe1BCF5GSSo6PPUQUWurv7rcXGW9uX/RxwHzA8cDvsuh0ZmbbA3cQjY92cPdXi40p0l6U0EWkZJKOZg8RrU8HuPutRcZbBLgYGEi0LO3r7o8XO84k9pbAH4nObju6+wtZxBVpL0roIlISZrYocc58WWCQu99cZLz1iCX21YC7gQPdfWrRA+W7+uyTgFnEhrpnsogr0p6U0EUkc2bWiXnPt48vZjd7ssR+MHG+vAE4Gjgvw6YtGxJn4huAnd39ySziirQ3JXQRyVSSgC8AdiJ6hp9TRKyuwOVAH+BtYon97xkMc278bYA/AF2AXu5e8nLNIqWic+gikrVfAYcRR9RSl0lNZs5PE8n8DmCDjJP5AcB9RAvi3dz9gaxii5SDZugikhkz2w0YC7xKFI6ZmSJGB2AY0SUN4EjgogyX2DsQ9d1HERvrdtUGOKkFSugikolk09pNwFRi+frTFDEWIzqZ7U2UW+3j7k9nOMb5k/gDicpyuxfZgU2kYiihi0jRkkR8F9CZ2CX+ZooYmxAFXVYmNtQNdffPMxxjd+J5+VbELvkBarQitUQJXUSKkixhXwqsBBxS6May5P4RwBhgDnAocFmWLUqT5/G3AD8mdssfU0w7VZFKpIQuIsU6kHkb1y4v5MZk1jwB+AXwGrHE/lxWA0u+LBxGPNeH2KR3cVbxRSqJErqIpJY0XBkHvEcskec9qzazzYgl9hWA64HD3P2LDMfWFbgM6As0EQ1hnsoqvkilUUIXkVSSDWY3Eme4B+Zbtc3MGoBjgTOAGcBBwISMl9jXJ5bYexArBwe5+2dZxRepREroIpLW6cAGwGnu/kg+N5jZEsA1wM+Bl4kl9syOjCVL7EOJwjYNwHBgXJZfFkQqlRK6iBTMzHYGjgEeB07N854tiRn9csBVwBFZ7jI3sxWBS4gvC2+TcVU5kUqnhC4iBUmOqF1FtBgd4O6z2ri+ATiRSPzTgQPc/ZoMx9NAbHw7C1iYWAEYkeYcvEg1U0IXkUKdRrRDHeLub7d2oZktDVwL7Ai8QGxMeyWrgZjZ6kS9+C2Ad4nqdPdmFV+kmiihi0jezGwD4HDgMeDqNq7dFriBSP6XA8Pd/euMxtEZOA44hShmcwEw0t2/zCK+SDVSQheRvCRL2xclfxzm7nNauK4jYESt9P8Sy/I3ZjiOHYg67+sCrxA72P+WVXyRaqWELiL5GgxsBpzfUvEXM1uGOFO+LfAssTHttSw+PFkdOItoyzoDcMDd/Zss4otUOyV0EWlTUtFtDDCFWOZu7podgeuApYDfE+VVp2fw2SsTyXsg8C3xTP5kd3+n2NgitUQJXUTy4cASwH7uPi33DTPrRLQjPQn4gjhbfmuxH5icWR9JPLOfD7gXOCHL0rAitUQJXURaZWYbEQ1TJhPL6bnvLU9sfNuSaEfaN02ntWZiHk70RO+axD3B3R8oJq5IrVNCF5G2nEt0QRuWW3HNzH5OLH8vTuwyP76Y59lm1pPoutab+G/T68AhwC0tbcATkXmU0EWkRWa2NdE/fLy7/zN5rTOxBH888Bmwl7vfmTJ+Z2BvIpFvmrz8AHAeMEmJXCR/Sugi0ppRwGyikcrc8qo3ApsDTwL92iou05wkzgBiWX154BuiQMwFc784iEhhlNBFpFlmtgWwHXCNu79pZrsTxWS6EefAT3L3GQXEW5lYTt8X2CR5+UPizPpl7v5RhsMXqTtK6CLSkpOJZ+e/NbPfAUcDU4Hd3f2efAKY2SrMS+I/TV7+gthIdyuxrJ73lwIRaZkSuoj8QLJBbWfgj8CVxIz6MaC/u/+rlfuWIYrPbEbM7jdM3vqc2EB3G3BfFufTReR/KaGLSHNOJoq4bEt0MDsLGOXuM+deYGbzAeszL4FvCqyUE+NTYon+NuB+VXQTKS0ldBH5H2a2GdAr+eMM4FjgfWCEma1AbGJbEVgL6JJz60vEbP4Jok/6y9qlLtJ+lNBF6pSZdSVm1Csnv1cikvROOZd1B8753q1ziM1sjxCJ+3HgSXf/rMRDFpFWKKGL1IFkZr0NsYS+IZHEF23llunAJKLH+L+A93J+f+jus0o4XBFJQQldpAaZ2bJE8t4m+f3jnLffBZ4B3kl+PgR2BXYnzoPPD/R09+fbccgiUiQldJEaYWbrAgcSz7975Lz1JvFs+yHgYXd/L+eenwC3EL3F/484WnabkrlI9VFCF6liZtYN6A8MATZKXn4HmEAk8L+2dMzMzAYAlwILEi1RNyV2to8u8bBFpASU0EWqjJk1EMvoQ4g66F2AL4nSqeOBJ3KbqDRz/4JEM5WDiOX23Yln5qOBm939hZL+C4hISSihi1QJM+sCHAwcRWxqg9hpPp5YJv8qjxhrEkvsawH3Ef3N/2NmfyZm56eWYOgi0g6U0EUqXFLA5UCi5vnywBTgTGCCu79eQJzBwEXEpreTgDHuPsfMNieqwt3g7i9lPHwRaSdK6CIVysw6AYOIjmeNwMfAMcDF7v51AXEWJhL5/kSBmH7u/mjOJaOJs+WanYtUMSV0kQqTPCPvQyTa1YgSqicB49z9ywJjrUMssa8OTAQGu/vHOe/vA+wAXOvur2bzbyAi5aCELlJBzGxtYnNbT6Ir2WhgrLtPKzBOB2LT2zji7/lxwLm5pVjNrDsxc5+avC8iVUwJXaQCmNnc59onAh2B84HT3P2TFLEWAS4BBhBFZPq6+xPNXHousDSwv7tPSTt2EakMSugiZZY0Q7kCWBP4J/BLd/97yljrE0vsPYC7gCHuPrWZ63YBDgD+DFyXcugiUkGU0EXKJNmsdgZwBDCTaFl6trvPSBGrA3AoMBZoAEYAFzR3Hj2ZwV9KnF0/pLUz6yJSPZTQRcrAzHoCNxMdzh4Dhrr7yyljLQpcDuwLNBFL7P9o5ZYzifanw9z93TSfKSKVRwldpB0lM+nDgPOA2cTs/OK0fcPN7KfEF4NVgNuJ5foW25ia2ZbAMGAy8ZxdRGqEErpIOzGzhYgkOohomNLb3Z9NGasDcCTRq/xbIklf3EbJ18WIGu/TicSf6kuEiFQmJXSRdmBmqxEz6LWBu4EDWptJtxGrG9E9bS/gDaCPuz/Txj0dgRuJNqpHuvtraT5bRCpXQ7kHIFLrzGxvojXpmsSxtL2KSOY9iV7mewE3ARu1lcwTpwO7EDP0i9J8tohUNs3QRUokWRY/Fjgb+AjY090fLCLW0cBZwCyiScsV+exQN7N+wAnAk8Bh2tUuUpuU0EVKICnfeg7RGe0FYNeW+pLnEWtx4CpgN+BVYon9+Tzv3YDoxvYhsLe7f5NmDCJS+ZTQRTKWdEebQFRqmwzs4e6fpoy1ObG0vgJwLXB4vvXczWxJ4E6i8tze7v5BmjGISHVQQhfJUFK05XZgRyKZDiikM1pOnAaivvrpwAxgCHBVvsvlZtaZqBi3IlEtrrnSryJSQ5TQRTJiZksBk4CNiEpsw9x9doo4SwLXEJvYXiKW2F8s4P5OwA3ANkSHtgmFjkFEqo92uYtkwMyWAB4kkvlviM1naZL5VsCzRDIfD2ycIplfC/QmZuhHFzoGEalOmqGLFCk5F34/sBZwnLufkyJGR+JI22jga6ID2rUpYlwF9COW/Qe5+6xCxyIi1UkJXaQIZtYVuBdYH7CUyXxpouPZDkS3tT7u/kqBMRqIjm0DicI1A9x9ZqFjEZHqpSV3kZSSbmmTgI0Bd/fTU8TYDniOSOaXAj1TJvNLgcHAROILQcEd20SkummGLpKCmS1AzIS3IM6bjyrw/o7JPScTbUz7u/tNKcbRkaj89ktipaC3zpqL1CcldJECJVXbrga2BS4Eji+k+pqZLQtcT+xCf4Zod/p6inEslMTZA/gLUVJ2eqFxRKQ2aMldpHAjid7jtwHDC0zmOxG72LchZtabp0zmywKPEMn8BmC3NOfdRaR2aIYuUgAz2ws4jXjuPTjfFqTJcbJTiZ3s04il8dtTjmE94B5geWJX/GjVZxcRJXSRPJnZOsQZ74+Icq5f5Xnf8kTr0p8B/wD6uftbKcfQiygFOx9xLO36NHFEpPYooYvkISkccxeRSHdx93fyvG9Xourb4sB5wAlpdqAnz+2PBMYCnxLNXiYXGkdEapcSukgbkrrotwKNwMHu/mie95xO1GP/jGidelfKz+8GXEZUf3sN6OXub6SJJSK1SwldpG3nEZvYLnT3y9u62MxWIpbFNwWeIJbY85rRNxNrC2LT24pE9behaTu3iUhtU0IXaYWZHQocTtRpb7MuupntQbRO7Qb8FhiZpmJbcr58JHAK8A0wFLhSm99EpCU1mdAbGxv1Hz0pmpltDYwD3iKqr7WYmJMe6GOAEcAnxDGyiSk/dwWiFOxWxG76/u7+cppYIlI/ajKhixQrWTa/DZgO/MLdP2nl2kbgZqIE7KNEAn4vxWd2ImbipxMz/AuITXQqFiMibVJCF/mepDb6tcASxPG0FtuXmtk+wJXAosCZwKg0Hc6Smu7nAesAHxDd1u5JMXwRqVNK6CI/NBzYktgEd3dzF5hZF6KG+zDiXPou7n5voR9kZqskcfYinpU7MMbdv0w5dhGpU9We0J8DnidmUlJZpgMPl3sQhTKzNYiZ9hvAr1u4ZlXgFmAD4K/AQHf/oMDPWYSoGncMcbb9NqKX+ttpxy4i9a3aE/qjwBBgsXIPRH7gS6CqNnIlz7CvBjoDBzRXCc7M+hFnwhcmSrme6u6zC/iMBmAQcBawDPGldLi7V92XHxGpLFWd0JuammYBL5V7HFIzTiA2tp3t7n/LfSNplzoWOASYQhSKebCQ4Ga2KXA+sAmxE/5Q4IpCvhCIiLSkqhO6SFaShienEF8QT/neez8hltjXJdqUDnL3KQXEXo6YkQ8CZhGb305VgRgRyZISutS95Az5NUQ74f1zj4mZ2SDgEmAB4GTgzHxn1Ga2MnAEcBiwIHAvcJTOlItIKSihi8AoYvY92t2fAjCzBYmiMkOIY2S98nnOnTRR2YrYKb8H8SXhZaKm+yRVehORUlFCl7pmZusTu82fIQq6YGZrEs1Y1gT+TMzaP2ojThegP5HI10tenkQUh7k/377pIiJpdSj3AETKJZlN/wXYFtgIeBYYDFxEHCUbCfy2tWRsZssSS+qHAEsCXxG13Me5+2ulHL+ISC7N0KWe7QZsB4wHXieOrO0HvEd0SHuspRvNbBNiNt6H+HvURJxfH+/u00o8bhGRH9AMXepS0q/8BWA54ln3hcDqwD3A4OZqt5tZD2AfYF9gw+Tlh4ijaPfo+JmIlJNm6FKvDgVWA+4mkngnomrb2NyNa2a2OtA7+Zn7bPwz4ApiWf359hy0iEhLNEOXumNm3YA3gS7EcbR3gL7u/mTyXH0t5iXxtZLbpgJ/IEq0PujuM9p94CIirVBCl7pjZtcBA5M/3kk0RFkH+BmwNbBq8t5HzEvif22tH7qISLkpoUvdSGbfo4kCMd8SjX1+BCydc9k7wEQiiU9O0wpVRKQcWkzoZjYK6NuOYxEptR8B3XP+PIdojvJo8vOYu79fjoGJiBRLm+KknswGvgBeJKrDPenun5d3SCIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiFef/AT5RzVxOuVPYAAAAAElFTkSuQmCC"
              ],
              [
                  "type" => "List",
                  "name" => "Gerät Information",
                  "caption" => "Informationen zu diesem Gerät [ Geschirrspüler ]",
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
                  IPS_SetHidden( $this->GetIDForIdent('remainStartTime'), true );
                  break;
              case 1:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), true );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), true );
                  IPS_SetHidden( $this->GetIDForIdent('remainStartTime'), true );
                  break;
              case 2:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), true );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), true );
                  IPS_SetHidden( $this->GetIDForIdent('remainStartTime'), false );
                  break;
              case 3:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), false );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainStartTime'), true );
                  break;
              default:
                  IPS_SetHidden( $this->GetIDForIdent('remoteStart'), false );
                  IPS_SetHidden( $this->GetIDForIdent('door'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainTime'), false );
                  IPS_SetHidden( $this->GetIDForIdent('progress'), false );
                  IPS_SetHidden( $this->GetIDForIdent('remainStartTime'), false );
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
      public function BuildList( string $profile ) {
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
          $profile = IPS_GetVariableProfile( "HC_DishwasherMode" )['Associations'];
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
          $profile = IPS_GetVariableProfile( 'HC_DishwasherMode' )['Associations'];
          $profile_count = count( $profile );

          $profile_list = array();

          for ( $i = 0; $i < $profile_count; $i++ ) {
              $profile_list[$profile[$i]["Value"]] = $profile[$i]["Name"];
          }

          return $profile_list[$this->GetValue('mode' )];
      }

      /** Counting Seconds down
       * @param string $var_name
       */
      public function DownCount( string $var_name ) {
          if ( $this->GetValue('state') == 3 || $this->GetValue('state') == 2 ) {
              $now = "1970-01-01 " . $this->GetValue( $var_name );
              $set = date("H:i:s", strtotime($now) - 1);
              $this->SetValue( $var_name, $set);
          } else {
              $this->SetValue( $var_name, "==:==:==");
          }
      }

      /** Function to show all options of a running Device [for dev]
       * @param array $input input array after api call
       * @param string $row The next array options after data
       * @return mixed return array with KEY => VALUE
       */
      protected function getKeys( array $input, string $row ) {
          // Get Options out of data
          $opt = $input['data'][$row];

          // Define vars and lenght
          $options_count = count( $opt );
          $option_list[] = array();

          // Build options list
          for( $i = 0; $i < $options_count; $i++) {
              // Get Data to set
              $option_name = $opt[$i]['key'];
              $option_value= $opt[$i]['value'];

              $options_list[$option_name] = $option_value;
          }

          return $options_list;
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
            case "BSH.Common.EnumType.OperationState.DelayedStart":
                return 2;
            case "BSH.Common.EnumType.OperationState.Run":
                return 3;
        }
        return 0;
    }
  }
?>