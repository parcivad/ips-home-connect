<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . "/libs/tools/HomeConnectApi.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");


class HomeConnectDiscovery extends IPSModule {

    /*
    * Internal function of SDK
    */
    public function Create()
    {
        // Overwrite ips function
        parent::Create();

        // User Data
        $this->RegisterPropertyString("user", "your@mail.de");
        $this->RegisterPropertyString("password", "password");
        $this->RegisterPropertyBoolean("simulator", true);
    }
    /*
     * Internal function of SDK
     */
    public function ApplyChanges() {
        // Overwrite ips function
        parent::ApplyChanges();
    }

    /*
     * Function for User Authorization
     */
    public function Auth() {
        authorize("https://simulator.home-connect.com/security/oauth/authorize", "35C7EC3372C6EB5FB5378505AB9CE083D80A97713698ACB07B20C6E41E5E2CD5", "IdentifyAppliance");
    }

    public function GetDevices() {

        $api = new HomeConnectApi();
        $api->SetUser( $this->ReadPropertyString('user') );
        $api->SetPassword( $this->ReadPropertyString('password') );
        $api->SetSimulator( $this->ReadPropertyBoolean('simulator') );

        $data = $api->Api("homeappliances");
        // catch null exception
        if ( $data == null ) { $error_return = [[ 'name' => 'Login failed [Token/Auth]', 'device' => ' ', 'company' => ' ', 'haId' => 'Überprüfe dein Passwort oder Nutzer/Check your passwort or user', 'connected' => ' ', 'rowColor' => '#ff0000']]; return $error_return;}
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
                "type" => "Button",
                "caption" => "Login",
                "onClick" => 'HomeConnectDiscovery_Auth( $this->InstanceID() );'
            ],
            [
                "type" => "ValidationTextBox",
                "name" => "user",
                "caption" => "HomeConnect - User-Email",
            ],
            [
                "type" => "PasswordTextBox",
                "name" => "password",
                "caption" => "HomeConnect - Password",
            ],
            [
                "type" => "CheckBox",
                "name" => "simulator",
                "caption" => "HomeConnect Simulation verwenden."
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