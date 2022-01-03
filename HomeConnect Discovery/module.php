<?php
// require
require_once(dirname(dirname(__FILE__)) . "/libs/tools/api.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/mode-translate.php");

// import
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );


class HomeConnectDiscovery extends IPSModule {

    /*
    * Internal function of SDK
    */
    public function Create()
    {
        // Overwrite ips function
        parent::Create();

        // SSE Client is required for device connection
        $this->RequireParent('{29BCE126-7037-F9E3-C4AE-BBC515C56203}');

        $this->RegisterPropertyString("auth_url", null);
    }
    /*
     * Internal function of SDK
     */
    public function ApplyChanges() {
        // Overwrite ips function
        parent::ApplyChanges();

        $this->ReloadForm();
    }

    //-----------------------------------------------------< Profiles >------------------------------

    public function login() {
        // open browser with login field ( ip-symcon api will open echo url in browser )
        echo 'https://api.home-connect.com/security/oauth/authorize?response_type=code&client_id=E1C592D4F052423018B7BE8AE500FBDC8B7D86CA386181A3BC9102119AF81B6C&redirect_uri=http%3A%2F%2Flocalhost%3A8080';
        // try to authorize the user with the code in the url, otherwise check the error code
        try {
            // authorize through a button
            authorize($this->ReadPropertyString("auth_url"));
            $this->ReloadForm();
        } catch (Exception $ex) {
            $this->SetStatus( analyseEX($ex) );
        }
    }

    /** Function for Authorization and Token
     * @param $opt
     * @return bool|mixed
     */
    public function tm( bool $opt) {
        switch ($opt) {
            case "auth":
                try {
                    // authorize through a button
                    authorize($this->ReadPropertyString("auth_url"));
                    $this->ReloadForm();
                } catch (Exception $ex) {
                    $this->SetStatus( analyseEX($ex) );
                }
                break;
            case "token":
                try {
                    // refresh token with a button
                    return getToken("https://api.home-connect.com/security/oauth/token", "E1C592D4F052423018B7BE8AE500FBDC8B7D86CA386181A3BC9102119AF81B6C", "D008096E80951049FE2FB577CABF8B074E11C699699724C8989E8FFC80EE059E");
                } catch (Exception $ex) {
                    $this->SetStatus( analyseEX($ex) );
                }
                break;
            case "reset":
                // reset the data.json
                resetData();
                break;
        }
    }

    /** Return Visible
     * @return bool
     */
    protected function visible() {
        return getAccessToken() == null;
    }


    //-----------------------------------------------------< Setting Form.json >------------------------------
    protected function GetDevices() {
        global $data;

        // Reading the auth Code and token from the data.json file
        $auth_code = getAuthorizeCode();
        $token = getAccessToken();

        // Send Api request
        try {
            // Send information that the token is now ready and after a new refresh the devices will show up
            if ( is_string( $auth_code ) && !is_string( $token ) ) {
                getToken("https://api.home-connect.com/security/oauth/token", "E1C592D4F052423018B7BE8AE500FBDC8B7D86CA386181A3BC9102119AF81B6C", "D008096E80951049FE2FB577CABF8B074E11C699699724C8989E8FFC80EE059E");
                return [['name' => 'Klicke erneut auf Aktualisieren [Refresh]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe ob du eingeloggt bist/Check if youre logged in', 'connected' => ' ', 'rowColor' => '#ff0000']];
            }
            // else get device list:
            $data = Api("homeappliances")['data']['homeappliances'];
            // reset Status after error
            $this->SetStatus( 102 );
        } catch(Exception $ex) {
            $this->SetStatus( analyseEX($ex) );
            // Catch in case of error reset Data (most of the error caused by wrong auth code which can only get fixed by clearing the data.json file)
            resetData();
            // Return the User a information, what to do next
            return [[ 'name' => 'No Devices [Login]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe ob du eingeloggt bist/Check if youre logged in', 'connected' => ' ', 'rowColor' => '#ff0000']];
        }

        // count the devices in the array
        $len = count($data);
        // define $devices
        $devices = [];
        // build array up
        for ($i = 0; $i < $len; $i++) {
            array_push($devices, $data[$i] );
        }

        $config_list = [];

        if (!empty($devices)) {
            foreach ($devices as $device) {
                $name = $device['name'];
                $brand = $device['brand'];
                $connected = $device['connected'];
                $type = $device['type'];
                $haId = $device['haId'];
                $device_instanceID = 0;
                $id = substr( $haId, -2 );

                // Search for matching module
                switch ( $type ) {
                    case "Oven":
                        $module = "{4D8D592A-63C7-B2BD-243F-C6BF1DCAD66C}";
                        break;
                    case "Dishwasher":
                        $module = "{CCE508B4-7A15-4541-06B0-03C9DA28A5F1}";
                        break;
                    case "Dryer":
                        $module = "{0276BAFD-1B64-97DC-AFED-9B7562C35491}";
                        break;
                    default:
                        echo 'NO MODULE FOUND  ';
                        $module = '{}';
                        break;
                }

                $instanceIDs = IPS_GetInstanceListByModuleID($module);
                foreach ($instanceIDs as $instanceID) {
                    if (IPS_GetProperty($instanceID, 'haId') == $haId) {
                        $device_instanceID = $instanceID;
                    }
                }

                $config_list[] = [
                    'name' => $name,
                    'device' => $type,
                    'company' => $brand,
                    'haId' => $haId,
                    'connected' => $connected,
                    'instanceID' => $device_instanceID,
                    'create'     => [
                        'moduleID'      => $module,
                        'configuration' => [
                            'name' => $name,
                            'device' => $type,
                            'company' => $brand,
                            'haId' => $haId,
                        ],
                    ],
                ];
            }
        }

        return  $config_list;
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
        return[
            [
                "type" => "RowLayout",
                "items" => [
                    [
                        "type" => "Button",
                        "caption" => "Logout",
                        "onClick" => 'HomeConnectDiscovery_tm( ' . $this->InstanceID . ', "reset" );',
                        'confirm' => 'logging out'
                    ],
                    [
                        "type" => "Button",
                        "caption" => "Login",
                        "confirm" => "After you finished the login process in your browser. Copy the url of localhost and paste it into the url field in this module instance",
                        "onClick" => 'HomeConnectDiscovery_login( $this->InstanceID );',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array[] Form Elements
     */
    protected function FormElements() {
        $visible = $this->visible();
        $token = getAccessToken();

        return[
            [
                "type" => "ExpansionPanel",
                "caption" => "Logged in successfully  👍",
                "visible" => !$visible,
                "items" => [
                    [
                        "type" => "Label",
                        "name" => "showToken",
                        "caption" => "Token: " . $token,
                        "visible" => !$visible,
                    ]
                ]
            ],
            [
                "type" => "Label",
                "name" => "loginInfo",
                "caption" => "No account found! Click on login to grant access to your device list",
                "visible" => $visible,
            ],
            [
                "type" => "ValidationTextBox",
                "name" => "auth_url",
                "caption" => "Url",
                "visible" => $visible,
            ],
            [
                "type" => "Label",
                "name" => "loginInfo2",
                "caption" => "After that click on Login to authorise your account!",
                "visible" => $visible,
            ],
            [
                "type" => "Configurator",
                "name" => "Home-Connect Discovery",
                "caption" => "HomeConnect Discovery",
                "rowCount" => 8,
                "add" => false,
                "delete" => true,
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
                    [
                        "caption" => "Connection",
                        "name" => "connected",
                        "width" => "100px",
                        "add" => false,
                    ],
                ],
                "values" => $this->GetDevices(),
            ],
        ];
    }

    /**
     * @return array[] Form Status
     */
    protected function FormStatus() {
        return [
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
                'icon'    => 'error',
                'caption' => 'Error is unknown   [ 201 ]',
            ],
            [
                'code'    => 206,
                'icon'    => 'error',
                'caption' => 'User not authorized   [ 206 ]',
            ],
            [
                'code'    => 207,
                'icon'    => 'error',
                'caption' => 'Client has not token   [ 207 ]',
            ],
            [
                'code'    => 401,
                'icon'    => 'error',
                'caption' => 'Device is offline   [ 401 ]',
            ],
            [
                'code'    => 402,
                'icon'    => 'inactive',
                'caption' => 'Program is unknown   [ 402 ]',
            ],
            [
                'code'    => 403,
                'icon'    => 'error',
                'caption' => 'Cant start program   [ 403 ]',
            ],
            [
                'code'    => 404,
                'icon'    => 'error',
                'caption' => 'Cant stop program   [ 404 ]',
            ],
            [
                'code'    => 405,
                'icon'    => 'inactive',
                'caption' => 'Request failed   [ 405 ]',
            ],
            [
                'code'    => 406,
                'icon'    => 'inactive',
                'caption' => 'Request limit reached   [ 406 ]',
            ],
            [
                'code'    => 407,
                'icon'    => 'error',
                'caption' => 'HomeConnect cloud is offline   [ 407 ]',
            ],
            [
                'code'    => 408,
                'icon'    => 'error',
                'caption' => 'HomeConnect error   [ 408 ]',
            ],
            [
                'code'    => 409,
                'icon'    => 'error',
                'caption' => 'Permission is missing   [ 409 ]',
            ],
            [
                'code'    => 410,
                'icon'    => 'error',
                'caption' => 'Operation state is unknown   [ 410 ]',
            ],
            [
                'code'    => 411,
                'icon'    => 'error',
                'caption' => 'Remote Control not allowed   [ 411 ]',
            ],
            [
                'code'    => 412,
                'icon'    => 'error',
                'caption' => 'Remote Start not allowed   [ 412 ]',
            ],
            [
                'code'    => 413,
                'icon'    => 'error',
                'caption' => 'Device is locked   [ 413 ]',
            ],
            [
                'code'    => 414,
                'icon'    => 'error',
                'caption' => 'Front Panel is open   [ 414 ]',
            ],
            [
                'code'    => 415,
                'icon'    => 'error',
                'caption' => 'Door is open  [ 415 ]',
            ],
            [
                'code'    => 416,
                'icon'    => 'error',
                'caption' => 'Meatprobe is plugged   [ 416 ]',
            ],
            [
                'code'    => 417,
                'icon'    => 'error',
                'caption' => 'Battery Level Low   [ 417 ]',
            ],
            [
                'code'    => 418,
                'icon'    => 'error',
                'caption' => 'Device is lifted   [ 418 ]',
            ],
            [
                'code'    => 419,
                'icon'    => 'error',
                'caption' => 'Dust Box not inserted   [ 419 ]',
            ],
            [
                'code'    => 420,
                'icon'    => 'error',
                'caption' => 'Already at Home   [ 420 ]',
            ],
            [
                'code'    => 421,
                'icon'    => 'error',
                'caption' => 'Active Program   [ 421 ]',
            ],
        ];
    }

}
?>