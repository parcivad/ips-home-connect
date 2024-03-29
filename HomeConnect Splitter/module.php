<?php
// require
require_once(dirname(dirname(__FILE__)) . "/libs/tools/api.php");
require_once( dirname(dirname(__FILE__) ) . "/libs/tools/tm/tm.php");

// import
$data = json_decode( file_get_contents( dirname(dirname(__FILE__) ) . "/libs/tools/tm/data.json" ), true );


class HomeConnectSplitter extends IPSModule {

    /*
    * Internal function of SDK
    */
    public function Create()
    {
        // Overwrite ips function
        parent::Create();

        // Register Timer in case of new token
        $this->RegisterTimer('sse', 60000, "HCSplitter_setupSSE( $this->InstanceID );");

        // SSE Client is required for device connection
        $this->RequireParent('{2FADB4B7-FDAB-3C64-3E2C-068A4809849A}');
    }
    /*
     * Internal function of SDK
     */
    public function ApplyChanges() {
        // Overwrite ips function
        parent::ApplyChanges();

        $this->setupSSE();
    }

    // From SSE -> Device
    public function ReceiveData($JSONString) {
        $data = json_decode($JSONString, true);

        // reset refresh timer
        $this->sseRefresh();

        $devices = [
            "{874DFA8F-327E-51F2-7DAD-967865BB5738}",
            "{55C053EA-CF85-D540-BAB6-A10EB11C9370}"
        ];

        foreach ($devices as $device) {
            $msg = [
                "DataID" => $device,
                "Event" => $data['Event'],
                "Data" => $data['Data'],
                "Retry" => $data['Retry'],
                "ID" => $data['ID']
            ];

            $this->SendData( $msg );
        }
    }

    /** Function to send data to child
     * @param array $msg
     */
    private function SendData( $msg ) {
        $this->SendDataToChildren( json_encode($msg) );
    }

    /**
     *  A function called by a timer when the sse client work wrong
     */
    private function sseRefresh() {
        $this->SetTimerInterval('sse', 0 );
        $this->SetTimerInterval('sse', 60000 );
    }

    /** This function will set all important information for a working sse client ( I/O parent ) */
    public function setupSSE() {
        // token
        try {
            $token = getToken("https://api.home-connect.com/security/oauth/token", "E1C592D4F052423018B7BE8AE500FBDC8B7D86CA386181A3BC9102119AF81B6C", "D008096E80951049FE2FB577CABF8B074E11C699699724C8989E8FFC80EE059E");
        } catch (Exception $ex) {
            $token = "PUSH-BUTTON-IN-MENU";
        }
        // get parent instance
        $parent = IPS_GetInstance( $this->InstanceID )['ConnectionID'];
        // build url
        $url = "https://api.home-connect.com/api/homeappliances/events";
        // setup
        IPS_SetProperty( $parent, "URL", $url);
        IPS_SetProperty( $parent, 'Headers', json_encode([['Name' => 'Authorization', 'Value' => 'Bearer ' . $token ] ] ) );
        IPS_SetProperty( $parent, "Active", false);
        IPS_ApplyChanges( $parent );
        IPS_SetProperty( $parent, 'Active', true );
        IPS_ApplyChanges( $parent );

        // update
        IPS_LogMessage("HomeConnect Splitter", "sse client update!");
    }

    /** This Function will set the IP Symcon Form.json
     * @return false|string Form json
     */
    public function GetConfigurationForm() {
        // return current form
        $Form = json_encode([
            'actions'  => $this->FormActions(),
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
                "caption" => "Upate Token for SSE Client",
                "onClick" => "HCSplitter_setupSSE( $this->InstanceID );"
            ],
        ];
    }
}
?>