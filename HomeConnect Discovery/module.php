<?php

require_once( dirname(dirname(__FILE__) ) . "/libs/tools/HomeConnectApi.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );


class HomeConnectDiscovery extends IPSModule {

    /*
    * Internal function of SDK
    */
    public function Create()
    {
        // Overwrite ips function
        parent::Create();

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

    /** Function for Authorization and Token
     * @param $opt
     * @return bool|mixed
     */
    public function tm($opt) {
        switch ($opt) {
            case "auth":
                authorize($this->ReadPropertyString("auth_url"));
                break;
            case "token":
                return getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
            case "reset":
                resetData();
                break;
        }
    }

    public function GetDevices() {
        global $data;

        $auth_code = getAuthorizeCode();
        $token = getAccessToken();

        if ( is_string( $auth_code ) && !is_string( $token ) ) {
            getToken("https://api.home-connect.com/security/oauth/token", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "EC9B4140CB439DF1BEEE39860141077C92C553AC65FEE729B88B7092B745B1F7");
            return [['name' => 'Retry [Refresh]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe ob du eingeloggt bist/Check if youre logged in', 'connected' => ' ', 'rowColor' => '#ff0000']];
        } else if ( !is_string( $auth_code ) && !is_string( $token ) ) {
            //login message
            return [[ 'name' => 'No Devices [Login]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe ob du eingeloggt bist/Check if youre logged in', 'connected' => ' ', 'rowColor' => '#ff0000']];
        }

        $data = Api("homeappliances");
        // catch null exception
        if ( $data == null ) { $error_return = [[ 'name' => 'No Devices [Login]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe ob du eingeloggt bist/Check if youre logged in', 'connected' => ' ', 'rowColor' => '#ff0000']]; return $error_return;}
        // else set data source
        $data = $data['data']['homeappliances'];

        $len = count($data);

        $devices = [];

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

                switch ( $type ) {
                    case "Dryer":
                        $module = "{F8AE3556-6835-DD3C-E8E0-F686BE81850D}";
                        break;
                    case "Oven":
                        $module = "{5899C50B-7033-9DA4-BD0A-D8ED2BF227B9}";
                        break;
                    case "CoffeeMaker":
                        $module = "{D5EF280F-8F60-C250-008F-8B17C4B69FD2}";
                        break;
                    case "FridgeFreezer":
                        $module = "{B03C2C23-A59C-026C-AD0B-CEA47312A5AB}";
                        break;
                    case "Dishwasher":
                        $module = "{CCE508B4-7A15-4541-06B0-03C9DA28A5F1}";
                        break;
                }


                $config_list[] = [
                    'name' => $name,
                    'device' => $type,
                    'company' => $brand,
                    'haId' => $haId,
                    'connected' => $connected,
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
                "type" => "Button",
                "caption" => "Logout",
                "onClick" => 'HomeConnectDiscovery_tm( $id, "reset" );',
                'confirm' => 'Bist du sicher, dass du dich ausloggen willst.'
            ],
            [
                "type" => "Button",
                "caption" => "Login",
                "onClick" => 'HomeConnectDiscovery_tm( $id, "auth" );',
            ]
        ];
    }

    /**
     * @return array[] Form Elements
     */
    protected function FormElements() {
        return[
            [
                "type" => "Label",
                "name" => "loginInfo",
                "caption" => "Logge dich bitte ein, indem du auf diesen Link klickst. Wenn du fertig bist (der Browser keine Page mehr anzeigt) kopiere die ganze url kopiere sie dann in die Eingabe.",
            ],
            [
                "type" => "Label",
                "name" => "link",
                "caption" => "https://api.home-connect.com/security/oauth/authorize?response_type=code&client_id=35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5&scope=&redirect_uri=http%3A%2F%2Flocalhost%3A8080",
            ],
            [
                "type" => "ValidationTextBox",
                "name" => "auth_url",
                "caption" => "Url",
            ],
            [
                "type" => "Label",
                "name" => "loginInfo2",
                "caption" => "Wenn du fertig bist, dann klicke auf login. Wenn du dich Ausloggen willst, dann klicke auf Logout",
            ],
            [
                "type" => "Configurator",
                "name" => "Home-Connect Discovery",
                "caption" => "HomeConnect Discovery",
                "rowCount" => 14,
                "add" => false,
                "delete"=> true,
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
        return[
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
    }

}
?>