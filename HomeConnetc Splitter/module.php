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

    // Empfangene Daten vom Parent (RX Paket) vom Typ Erweitert (SSE)
    public function ReceiveData($JSONString) {
        $data = json_decode($JSONString, true);

        //Im Meldungsfenster zu Debug zwecken ausgeben
        IPS_LogMessage("Splitter", print_r($data, true));

        $this->SendDataToChildren($JSONString);
        $this->SendDataToChildren(json_encode([
            'DataID' => "{29BCE126-7037-F9E3-C4AE-BBC515C56203}",
            'Event' => utf8_encode( $data['Event'] ),
            'Data' => utf8_encode( $data['Data'] ),
            'Retry' => utf8_encode( $data['Retry'] ),
            'ID' => $data['ID']
        ]));
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