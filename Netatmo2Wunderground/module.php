<?

class Netatmo2Wunderground extends IPSModule
{

	private $scriptName = "Netatmo2Wunderground";

    public function Create()
    {
		//Never delete this line!
        parent::Create();

		$this->RegisterPropertyString("Netatmo_User", "");
		$this->RegisterPropertyString("Netatmo_Password", "");
		$this->RegisterPropertyString("Netatmo_Client", "");
		$this->RegisterPropertyString("Netatmo_Secret", "");
		$this->RegisterPropertyString("Wunderground_ID", "");
		$this->RegisterPropertyString("Wunderground_Key", "");

		$this->RegisterPropertyInteger("TransferInterval", "5");

		$this->RegisterTimer("TransferWeather", 0, 'Netatmo2Wunderground_Transfer(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
		//Never delete this line!
        parent::ApplyChanges();

		$netatmo_user = $this->ReadPropertyString("Netatmo_User");
		$netatmo_password = $this->ReadPropertyString("Netatmo_Password");
		$netatmo_client = $this->ReadPropertyString("Netatmo_Client");
		$netatmo_secret = $this->ReadPropertyString("Netatmo_Secret");
		$wunderground_id = $this->ReadPropertyString("Wunderground_ID");
		$wunderground_key = $this->ReadPropertyString("Wunderground_Key");

		if ($netatmo_user != "" && $netatmo_password != "" && $netatmo_client != "" && $netatmo_secret != "" && $wunderground_id != "" && $wunderground_key != "") {
			// status of transfer
			$this->RegisterVariableString("Status", "Netatmo2Wunderground_Status", "", 1 );

			// instanz is activ
			$this->SetStatus(102);
			$this->SetUpdateInterval();
		} else {
			// instance is inactiv
			$this->SetStatus(104);
		}
    }

	protected function SetUpdateInterval()
	{
		// refresh-timer
		$min = $this->ReadPropertyInteger("TransferInterval");
		$msec = $min > 0 ? $min * 1000 * 60 : 0;
		$this->SetTimerInterval("TransferWeather", $msec);
	}

	private function km2mile($i)
	{
		if (is_int($i) || is_float($i)) {
			$o = $i / 1.609344;
		} else {
			$o = "";
		}
		return ($o);
	}

	private function mm2inch($i)
	{
		if (is_int($i) || is_float($i)) {
			$o = $i / 25.4;
		} else {
			$o = "";
		}
		return ($o);
	}

	private function farenheit2celsius($i)
	{
		if (is_int($i) || is_float($i)) {
			$o = ($i * 1.8) + 32;
		} else {
			$o = "";
		}
		return ($o);
	}

	private function mb2inch($i)
	{
		if (is_int($i) || is_float($i)) {
			$o = $i / 1000 * 29.53;
		} else {
			$o = "";
		}
		return ($o);
	}

	public function Transfer()
	{
		$wunderground_url = "https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php";
		$netatmo_auth_url = "https://api.netatmo.net/oauth2/token";
		$netatmo_api_url  = "https://api.netatmo.net/api/devicelist";

		$netatmo_user = $this->ReadPropertyString("Netatmo_User");
		$netatmo_password = $this->ReadPropertyString("Netatmo_Password");
		$netatmo_client = $this->ReadPropertyString("Netatmo_Client");
		$netatmo_secret = $this->ReadPropertyString("Netatmo_Secret");
		$wunderground_id = $this->ReadPropertyString("Wunderground_ID");
		$wunderground_key = $this->ReadPropertyString("Wunderground_Key");

		$err = "";

		$postdata = array(
			'grant_type' => "password",
			'client_id' => $netatmo_client,
			'client_secret' => $netatmo_secret,
			'username' => $netatmo_user,
			'password' => $netatmo_password,
			'scope' => 'read_station'
		);

		$this->SendDebug($this->scriptName, "netatmo-auth-url: $netatmo_auth_url, postdata=" . print_r($postdata, true), 0);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $netatmo_auth_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpcode != 200) {
			$err = "got http-code $httpcode from netatmo";
		} else if ($response == "") {
			$err = "no response from netatmo";
		} else {
			$params = json_decode($response, true);
			if ($params == "") {
				$err = "malformed response response from netatmo";
			} else if ($params['access_token'] == "") {
				$err = "no 'access_token' in response from netatmo";
			}
		}
		if ($err != "") {
			$this->SendDebug($this->scriptName, $err, 0);
			IPS_LogMessage($this->scriptName, $err);
			SetValueString($this->GetIDForIdent('Status'), "Netatmo error");
			return (-1);
		}

		// Anfrage mit Token
		$api_url = $netatmo_api_url . "?access_token=" . $params['access_token'];

		$this->SendDebug($this->scriptName, "netatmo-data-url: $api_url", 0);

		// Daten abrufen
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpcode != 200) {
			$err = "got http-code $httpcode from netatmo";
		} else if ($data == "") {
			$err = "no data from netatmo";
		} else {
			$netatmo = json_decode($data,true);
			if ($netatmo == "") {
				$err = "malformed data from netatmo";
			}
		}
		if ($err != "") {
			$this->SendDebug($this->scriptName, $err, 0);
			IPS_LogMessage($this->scriptName, $err);
			SetValueString($this->GetIDForIdent('Status'), "Netatmo error");
			return (-1);
		}

		$status = $netatmo["status"];
		if ( $status != "ok" ) {
			$err = "got status \"$status\" from netamo";
			$this->SendDebug($this->scriptName, $err, 0);
			IPS_LogMessage($this->scriptName, $err);
			SetValueString($this->GetIDForIdent('Status'), "Netatmo error");
			return (-1);
		}

		// Messwerte bereitstellen
		$pressure = "";
		$time_utc = "";
		$temp = "";
		$humidity = "";
		$rain = "";
		$sum_rain_1 = "";
		$sum_rain_24 = "";
		$winddir = "";
		$windspeed = "";
		$windgustdir = "";
		$windgust = "";

		$devices = $netatmo["body"]["devices"];

		$device = $devices[0];
		$station_name = $device["station_name"];
		$module_name = $device["module_name"];
		$pressure = $device["dashboard_data"]["Pressure"];
		$time_utc = $device["dashboard_data"]["time_utc"];

		$modules = $netatmo["body"]["modules"];
		foreach ($modules as $i => $value) {
			$module = $modules[$i];
			$module_name = $module["module_name"];
			switch($module["type"])
			{
				case "NAModule1":
					$type = "outdoor";
					$temp = $module["dashboard_data"]["Temperature"];
					$humidity = $module["dashboard_data"]["Humidity"];
					break;
				case "NAModule2":
					$type = "wind";
					$winddir = $module["dashboard_data"]["WindAngle"];
					$windspeed = $module["dashboard_data"]["WindStrength"];
					$windgustdir = $module["dashboard_data"]["GustAngle"];
					$windgust = $module["dashboard_data"]["GustStrength"];
					break;
				case "NAModule3":
					$type = "rain";
					$rain = $module["dashboard_data"]["Rain"];
					$sum_rain_1 = $module["dashboard_data"]["sum_rain_1"];
					$sum_rain_24 = $module["dashboard_data"]["sum_rain_24"];
					break;
				case "NAModule4":
					$type = "indoor";
					break;
				default:
					break;
			}
		}

		$param = "&dateutc=" . rawurlencode(date('Y-m-d G:i:s', $time_utc));
		if (strlen($temp)) {
			$param .= "&tempf=" . rawurlencode($this->farenheit2celsius($temp));
		}
		if (strlen($humidity)) {
			$param .= "&humidity=" . rawurlencode($humidity);
		}
		if ( strlen($temp) && strlen($humidity) ) {
			if ($temp > 0) {
				$k2 = 17.62;
				$k3 = 243.12;
			} else {
				$k2 = 22.46;
				$k3 = 272.62;
			}
			$dewpoint = $k3 *(($k2 * $temp) / ($k3 + $temp) + log($humidity / 100));
			$dewpoint = $dewpoint / (($k2 * $k3) / ($k3 + $temp) - log($humidity / 100));
			$dewpoint = round($dewpoint, 0);
			$param .= "&dewptf=" . rawurlencode($this->farenheit2celsius($dewpoint));
		}
		if (strlen($pressure)) {
			$param .= "&baromin=" . rawurlencode($this->mb2inch($pressure));
		}
		if (strlen($sum_rain_1)) {
			$param .= "&rainin=" . rawurlencode($this->mm2inch($sum_rain_1));
		}
		if (strlen($sum_rain_24)) {
			$param .= "&dailyrainin=" . rawurlencode($this->mm2inch($sum_rain_24));
		}
		if (strlen($windspeed)) {
			$param .= "&windspeedmph=" . rawurlencode($this->km2mile($windspeed)) . "&winddir=" . rawurlencode($winddir);
		}
		if (strlen($windgust)) {
			$param .= "&windgustmph=" . rawurlencode($this->km2mile($windgust)) . "&windgustdir=" . rawurlencode($windgustdir);
		}

		$msg = "temp=" . $temp . "°C";
		$msg .= ", humidity=" . $humidity . "%";
		$msg .= ", rain 1h=" . $sum_rain_1 . " mm";
		$msg .= ", 24h=" . $sum_rain_24 . " mm";
		$msg .= ", wind=" . $windspeed . " km/h";
		$msg .= " (" . $winddir . "°)";
		$msg .= ", gust=" . $windgust. " km/h";
		$msg .= " (" . $windgustdir . "°)";
		$msg .= ", pressure=" . $pressure . " mb";

		IPS_LogMessage($this->scriptName, $msg);

		$url = $wunderground_url . "?ID=" . $wunderground_id . "&PASSWORD=" . $wunderground_key . "&action=updateraw" . $param;

		$this->SendDebug($this->scriptName, "wunderground-url: $url", 0);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$wstatus = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpcode != 200) {
			$err = "got http-code $httpcode from wunderground";
		}
		if ($err != "") {
			$this->SendDebug($this->scriptName, $err, 0);
			IPS_LogMessage($this->scriptName, $err);
			SetValueString($this->GetIDForIdent('Status'), "Wunderground error");
			return (-1);
		}

		SetValueString($this->GetIDForIdent('Status'), "ok");
	}
}

?>
