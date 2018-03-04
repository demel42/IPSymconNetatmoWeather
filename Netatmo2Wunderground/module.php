<?

class Netatmo2Wunderground extends IPSModule
{

    public function Create()
    {
		//Never delete this line!
        parent::Create();

		$this->RegisterTimer("TransferWeather", $this->ReadPropertyInteger("TransferInterval"), 'Netatmo2Wunderground_Transfer($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
		//Never delete this line!
        parent::ApplyChanges();

		$pwsid = $this->ReadPropertyString("PWSID");

		$netatmo_user = $this->ReadPropertyString("Netatmo_User");
		$netatmo_password = $this->ReadPropertyString("Netatmo_Password");
		$netatmo_client = $this->ReadPropertyString("Netatmo_ClientID");
		$netatmo_secret = $this->ReadPropertyString("Netatmo_Secret");
		$wunderground_pwsid = $this->ReadPropertyString("Wunderground_PWSID");
		$wunderground_password = $this->ReadPropertyString("Wunderground_Passwort");

		if ($netatmo_user != "" && $netatmo_password != "" && $netatmo_client != "" && $netatmo_secret != "" && $wunderground_pwsid != "" && $wunderground_password != "") {
			// refresh-timer
			$min = $this->ReadPropertyInteger("TransferInterval")
			$msec = $min > 0 ? $min * 1000 * 60 : 0;
			$this->SetTimerInterval("TransferWeather", $msec);

			// status of transfer
			$this->MaintainVariable("Status", "Netatmo2Wunderground_Status", 3, "", 10, true);

			// instanz is activ
			$this->SetStatus(102);
		} else {
			// instance is inactiv
			$this->SetStatus(104);
		}
    }

	public function Netatmo2Wunderground_Transfer()
	{
		$netatmo_user = $this->ReadPropertyString("Netatmo_User");
		$netatmo_password = $this->ReadPropertyString("Netatmo_Password");
		$netatmo_client = $this->ReadPropertyString("Netatmo_ClientID");
		$netatmo_secret = $this->ReadPropertyString("Netatmo_Secret");
		$wunderground_pwsid = $this->ReadPropertyString("Wunderground_PWSID");
		$wunderground_password = $this->ReadPropertyString("Wunderground_Passwort");

		$this->SendDebug("N2W", "in Netatmo2Wunderground_Transfer");
	}
}

?>

{ "code": 210, "icon": "error", "caption": "missing access-data" },
