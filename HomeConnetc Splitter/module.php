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

        //Im Meldungsfenster zu Debug zwecken ausgeben
        IPS_LogMessage("Splitter", print_r($data, true));

        $devices = [
            "{874DFA8F-327E-51F2-7DAD-967865BB5738}"
        ];

        foreach ($devices as $device) {
            $msg = [
                "DataID" => $device,
                "Event" => $data['Event'],
                "Data" => $data['Data'],
                "Retry" => $data['Retry'],
                "ID" => $data['ID']
            ];

            $this->SendData($msg);
        }
    }

    /** Function to send data to child
     * @param array $msg
     */
    public function SendData( $msg ) {
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
        // get parent instance
        $parent = IPS_GetInstance( $this->InstanceID )['ConnectionID'];
        // build url
        $url = "https://api.home-connect.com/api/homeappliances/events";
        // setup
        IPS_SetProperty( $parent, "URL", $url);
        IPS_SetProperty( $parent, 'Headers', json_encode([['Name' => 'Authorization', 'Value' => 'Bearer ' . getAccessToken()]]));
        IPS_SetProperty( $parent, "Active", false);
        IPS_ApplyChanges( $parent );
        IPS_SetProperty( $parent, 'Active', true );
        IPS_ApplyChanges( $parent );
    }
}
?>