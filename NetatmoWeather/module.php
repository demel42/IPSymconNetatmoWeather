<?php

class NetatmoWeather extends IPSModule
{
    private $scriptName = 'NetatmoWeather';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Netatmo_User', '');
        $this->RegisterPropertyString('Netatmo_Password', '');
        $this->RegisterPropertyString('Netatmo_Client', '');
        $this->RegisterPropertyString('Netatmo_Secret', '');
        $this->RegisterPropertyString('Wunderground_ID', '');
        $this->RegisterPropertyString('Wunderground_Key', '');

        $this->RegisterPropertyString('station_name', '');
        $this->RegisterPropertyString('base_module_name', '');
        $this->RegisterPropertyString('outdoor_module_name', '');
        $this->RegisterPropertyString('indoor1_module_name', '');
        $this->RegisterPropertyString('indoor2_module_name', '');
        $this->RegisterPropertyString('indoor3_module_name', '');
        $this->RegisterPropertyString('rain_module_name', '');
        $this->RegisterPropertyString('wind_module_name', '');

        $this->RegisterPropertyInteger('UpdateDataInterval', '5');

        $this->RegisterPropertyString('token', '');
        $this->RegisterPropertyInteger('token_expiration', 0);

        $this->RegisterPropertyInteger('station_altitude', 0);
        $this->RegisterPropertyInteger('station_longitude', 0);
        $this->RegisterPropertyInteger('station_latitude', 0);

        // Anzahl von Minuten, bis die Kommunikation von Netatmo Basis zu Server als gestört erklärt wird
        $this->RegisterPropertyInteger('minutes2fail', 15);

        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_absolute_humidity', false);
        $this->RegisterPropertyBoolean('with_dewpoint', false);
        $this->RegisterPropertyBoolean('with_windchill', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_windstrength', false);
        $this->RegisterPropertyBoolean('with_signal', false);
        $this->RegisterPropertyBoolean('with_battery', false);

        // Variablenprofil anlegen ($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon)
        $this->CreateVarProfile('Netatmo.Temperatur', 2, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Netatmo.Humidity', 2, ' %', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.absHumidity', 2, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Dewpoint', 2, ' °', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Heatindex', 2, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Netatmo.CO2', 1, ' ppm', 250, 1500, 0, 0, 'Gauge');
        $this->CreateVarProfile('Netatmo.Noise', 1, ' dB', 10, 130, 0, 0, 'Speaker');
        $this->CreateVarProfile('Netatmo.Pressure', 2, ' mbar', 500, 1200, 0, 0, 'Gauge');
        $this->CreateVarProfile('Netatmo.WindSpeed', 2, ' km/h', 0, 100, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindStrength', 1, ' bft', 0, 13, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindAngle', 1, ' °', 0, 360, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Netatmo.Rainfall', 2, ' mm', 0, 60, 0, 1, 'Rainfall');
        if (!IPS_VariableProfileExists('Netatmo.Wifi')) {
            IPS_CreateVariableProfile('Netatmo.Wifi', 1);
            IPS_SetVariableProfileText('Netatmo.Wifi', '', '');
            IPS_SetVariableProfileIcon('Netatmo.Wifi', 'Intensity');
            IPS_SetVariableProfileAssociation('Netatmo.Wifi', 0, $this->wifi_status2text(0), '', 0xEE0000);
            IPS_SetVariableProfileAssociation('Netatmo.Wifi', 1, $this->wifi_status2text(1), '', 0xFFFF00);
            IPS_SetVariableProfileAssociation('Netatmo.Wifi', 2, $this->wifi_status2text(2), '', 0x32CD32);
            IPS_SetVariableProfileAssociation('Netatmo.Wifi', 3, $this->wifi_status2text(3), '', 0x228B22);
        }
        if (!IPS_VariableProfileExists('Netatmo.RfSignal')) {
            IPS_CreateVariableProfile('Netatmo.RfSignal', 1);
            IPS_SetVariableProfileText('Netatmo.RfSignal', '', '');
            IPS_SetVariableProfileIcon('Netatmo.RfSignal', 'Intensity');
            IPS_SetVariableProfileAssociation('Netatmo.RfSignal', 0, $this->signal_status2text(0), '', 0xEE0000);
            IPS_SetVariableProfileAssociation('Netatmo.RfSignal', 1, $this->signal_status2text(1), '', 0xFFA500);
            IPS_SetVariableProfileAssociation('Netatmo.RfSignal', 2, $this->signal_status2text(2), '', 0xFFFF00);
            IPS_SetVariableProfileAssociation('Netatmo.RfSignal', 3, $this->signal_status2text(3), '', 0x32CD32);
            IPS_SetVariableProfileAssociation('Netatmo.RfSignal', 4, $this->signal_status2text(4), '', 0x228B22);
        }
        if (!IPS_VariableProfileExists('Netatmo.Battery')) {
            IPS_CreateVariableProfile('Netatmo.Battery', 1);
            IPS_SetVariableProfileText('Netatmo.Battery', '', '');
            IPS_SetVariableProfileIcon('Netatmo.Battery', 'Battery');
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 0, $this->battery_status2text(0), '', 0xEE0000);
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 1, $this->battery_status2text(1), '', 0xFFA500);
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 2, $this->battery_status2text(2), '', 0xFFFF00);
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 3, $this->battery_status2text(3), '', 0x32CD32);
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 4, $this->battery_status2text(4), '', 0x228B22);
            IPS_SetVariableProfileAssociation('Netatmo.Battery', 5, $this->battery_status2text(5), '', 0x228B22);
        }

        $this->RegisterTimer('UpdateDataWeather', 0, 'NetatmoWeather_UpdateData(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $netatmo_user = $this->ReadPropertyString('Netatmo_User');
        $netatmo_password = $this->ReadPropertyString('Netatmo_Password');
        $netatmo_client = $this->ReadPropertyString('Netatmo_Client');
        $netatmo_secret = $this->ReadPropertyString('Netatmo_Secret');
        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        $base_module_name = $this->ReadPropertyString('base_module_name');
        $outdoor_module_name = $this->ReadPropertyString('outdoor_module_name');
        $wind_module_name = $this->ReadPropertyString('wind_module_name');
        $rain_module_name = $this->ReadPropertyString('rain_module_name');
        $indoor1_module_name = $this->ReadPropertyString('indoor1_module_name');
        $indoor2_module_name = $this->ReadPropertyString('indoor2_module_name');
        $indoor3_module_name = $this->ReadPropertyString('indoor3_module_name');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        if ($netatmo_user != '' && $netatmo_password != '' && $netatmo_client != '' && $netatmo_secret != '') {
            $vpos = 1;
            // status of connection to netatmo
            $this->RegisterVariableBoolean('Status', 'Status', '~Alert.Reversed', $vpos++);
            $id = $this->RegisterVariableString('Data', 'Zusatzdaten', '', $vpos++);
            IPS_SetHidden($id, true);
            $this->RegisterVariableBoolean('Battery', 'Batterie-Indikator', '~Battery', $vpos++);
            $this->RegisterVariableString('LastContact', 'letzte Übertragung', '', $vpos++);

            $this->RegisterVariableString('StatusImage', 'Status der Station und Module', '~HTMLBox', $vpos++);

            if ($wunderground_id != '' && $wunderground_key != '') {
                $this->RegisterVariableBoolean('Wunderground', 'Status der Übertragung an Wunderground', '~Alert.Reversed', $vpos++);
            } else {
                $this->UnregisterVariable('Wunderground');
            }

            if ($base_module_name != '') {
                $vpos = 100;
                $s = $base_module_name . '\\';
                $this->RegisterVariableInteger('LastMeasure', 'letzte Messung', '~UnixTimestamp', $vpos++);

                $this->RegisterVariableFloat('BASE_Temperature', $s . 'Temperatur', 'Netatmo.Temperatur', $vpos++);
                $this->RegisterVariableInteger('BASE_CO2', $s . 'CO2', 'Netatmo.CO2', $vpos++);
                $this->RegisterVariableFloat('BASE_Humidity', $s . 'Luftfeuchtigkeit', 'Netatmo.Humidity', $vpos++);
                if ($with_absolute_humidity) {
                    $this->RegisterVariableFloat('BASE_AbsoluteHumidity', $s . 'absolute Luftfeuchtigkeit', 'Netatmo.absHumidity', $vpos++);
                } else {
                    $this->UnregisterVariable('BASE_AbsoluteHumidity');
                }
                $this->RegisterVariableInteger('BASE_Noise', $s . 'Lärm', 'Netatmo.Noise', $vpos++);
                $this->RegisterVariableFloat('BASE_Pressure', $s . 'Luftdruck', 'Netatmo.Pressure', $vpos++);
                if ($with_absolute_pressure) {
                    $this->RegisterVariableFloat('BASE_AbsolutePressure', $s . 'absoluter Luftdruck', 'Netatmo.Pressure', $vpos++);
                } else {
                    $this->UnregisterVariable('BASE_AbsolutePressure');
                }
                if ($with_signal) {
                    $this->RegisterVariableInteger('BASE_Wifi', $s . 'Stärke des Wifi-Signals', 'Netatmo.Wifi', $vpos++);
                } else {
                    $this->UnregisterVariable('BASE_Wifi');
                }
            } else {
                $this->UnregisterVariable('BASE_Temperature');
                $this->UnregisterVariable('BASE_CO2');
                $this->UnregisterVariable('BASE_Humidity');
                $this->UnregisterVariable('BASE_Noise');
                $this->UnregisterVariable('BASE_Pressure');
                $this->UnregisterVariable('BASE_AbsolutePressure');
                $this->UnregisterVariable('BASE_LastMeasure');
                $this->UnregisterVariable('BASE_LastContact');
                $this->UnregisterVariable('BASE_Wifi');
            }
            for ($i = 1; $i <= 3; $i++) {
                $vpos = 200 + (($i - 1) * 100);
                switch ($i) {
                    case 1: $module_name = $indoor1_module_name; break;
                    case 2: $module_name = $indoor2_module_name; break;
                    case 3: $module_name = $indoor3_module_name; break;
                }
                $pfx = 'IN' . $i;
                if ($module_name != '') {
                    $s = $module_name . '\\';
                    $this->RegisterVariableFloat($pfx . '_Temperature', $s . 'Temperatur', 'Netatmo.Temperatur', $vpos++);
                    $this->RegisterVariableInteger($pfx . '_CO2', $s . 'CO2', 'Netatmo.CO2', $vpos++);
                    $this->RegisterVariableFloat($pfx . '_Humidity', $s . 'Luftfeuchtigkeit', 'Netatmo.Humidity', $vpos++);
                    if ($with_absolute_humidity) {
                        $this->RegisterVariableFloat($pfx . '_AbsoluteHumidity', $s . 'absolute Luftfeuchtigkeit', 'Netatmo.absHumidity', $vpos++);
                    } else {
                        $this->UnregisterVariable($pfx . '_AbsoluteHumidity');
                    }
                    if ($with_signal) {
                        $this->RegisterVariableInteger($pfx . '_RfSignal', $s . 'Signal-Stärke', 'Netatmo.RfSignal', $vpos++);
                    } else {
                        $this->UnregisterVariable($pfx . '_RfSignal');
                    }
                    if ($with_battery) {
                        $this->RegisterVariableInteger($pfx . '_Battery', $s . 'Batterie-Status', 'Netatmo.Battery', $vpos++);
                    } else {
                        $this->UnregisterVariable($pfx . '_Battery');
                    }
                } else {
                    $this->UnregisterVariable($pfx . '_Temperature');
                    $this->UnregisterVariable($pfx . '_CO2');
                    $this->UnregisterVariable($pfx . '_Humidity');
                    $this->UnregisterVariable($pfx . '_AbsoluteHumidity');
                    $this->UnregisterVariable($pfx . '_RfSignal');
                    $this->UnregisterVariable($pfx . '_Battery');
                }
            }
            if ($outdoor_module_name != '') {
                $vpos = 500;
                $s = $outdoor_module_name . '\\';
                $this->RegisterVariableFloat('OUT_Temperature', $s . 'Temperatur', 'Netatmo.Temperatur', $vpos++);
                $this->RegisterVariableFloat('OUT_Humidity', $s . 'Luftfeuchtigkeit', 'Netatmo.Humidity', $vpos++);
                if ($with_absolute_humidity) {
                    $this->RegisterVariableFloat('OUT_AbsoluteHumidity', $s . 'absolute Luftfeuchtigkeit', 'Netatmo.absHumidity', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_AbsoluteHumidity');
                }
                if ($with_dewpoint) {
                    $this->RegisterVariableFloat('OUT_Dewpoint', $s . 'Taupunkt', 'Netatmo.Dewpoint', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_Dewpoint');
                }
                if ($with_windchill) {
                    $this->RegisterVariableFloat('OUT_Windchill', $s . 'Windchill', 'Netatmo.Temperatur', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_Windchill');
                }
                if ($with_heatindex) {
                    $this->RegisterVariableFloat('OUT_Heatindex', $s . 'Hitzeindex', 'Netatmo.Heatindex', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_Heatindex');
                }
                if ($with_signal) {
                    $this->RegisterVariableInteger('OUT_RfSignal', $s . 'Signal-Stärke', 'Netatmo.RfSignal', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_RfSignal');
                }
                if ($with_battery) {
                    $this->RegisterVariableInteger('OUT_Battery', $s . 'Batterie-Status', 'Netatmo.Battery', $vpos++);
                } else {
                    $this->UnregisterVariable('OUT_Battery');
                }
            } else {
                $this->UnregisterVariable('OUT_Temperature');
                $this->UnregisterVariable('OUT_Humidity');
                $this->UnregisterVariable('OUT_AbsoluteHumidity');
                $this->UnregisterVariable('OUT_Dewpoint');
                $this->UnregisterVariable('OUT_Windchill');
                $this->UnregisterVariable('OUT_Heatindex');
                $this->UnregisterVariable('OUT_RfSignal');
                $this->UnregisterVariable('OUT_Battery');
            }

            if ($wind_module_name != '') {
                $vpos = 600;
                $s = $wind_module_name . '\\';
                $this->RegisterVariableFloat('WIND_WindSpeed', $s . 'Windgeschwindigkeit', 'Netatmo.WindSpeed', $vpos++);
                $this->RegisterVariableInteger('WIND_WindAngle', $s . 'Windrichtung', 'Netatmo.WindAngle', $vpos++);
                $this->RegisterVariableFloat('WIND_GustSpeed', $s . 'Geschwindigkeit der Böen der letzten 5m', 'Netatmo.WindSpeed', $vpos++);
                $this->RegisterVariableInteger('WIND_GustAngle', $s . 'Richtung der Böen der letzten 5m', 'Netatmo.WindAngle', $vpos++);
                if ($with_windstrength) {
                    $this->RegisterVariableInteger('WIND_WindStrength', $s . 'Windstärke', 'Netatmo.WindStrength', $vpos++);
                    $this->RegisterVariableInteger('WIND_GustStrength', $s . 'Stärke der Böen', 'Netatmo.WindStrength', $vpos++);
                } else {
                    $this->UnregisterVariable('WIND_WindStrength');
                    $this->UnregisterVariable('WIND_GustStrength');
                }
                if ($with_signal) {
                    $this->RegisterVariableInteger('WIND_RfSignal', $s . 'Signal-Stärke', 'Netatmo.RfSignal', $vpos++);
                } else {
                    $this->UnregisterVariable('WIND_RfSignal');
                }
                if ($with_battery) {
                    $this->RegisterVariableInteger('WIND_Battery', $s . 'Batterie-Status', 'Netatmo.Battery', $vpos++);
                } else {
                    $this->UnregisterVariable('WIND_Battery');
                }
            } else {
                $this->UnregisterVariable('WIND_WindSpeed');
                $this->UnregisterVariable('WIND_WindAngle');
                $this->UnregisterVariable('WIND_WindStrength');
                $this->UnregisterVariable('WIND_GustSpeed');
                $this->UnregisterVariable('WIND_GustAngle');
                $this->UnregisterVariable('WIND_GustStrength');
                $this->UnregisterVariable('WIND_RfSignal');
                $this->UnregisterVariable('WIND_Battery');
            }

            if ($rain_module_name != '') {
                $vpos = 700;
                $s = $rain_module_name . '\\';
                $this->RegisterVariableFloat('RAIN_Rain', $s . 'Regenmenge', 'Netatmo.Rainfall', $vpos++);
                $this->RegisterVariableFloat('RAIN_Rain_1h', $s . 'Regenmenge der letzten 1h', 'Netatmo.Rainfall', $vpos++);
                $this->RegisterVariableFloat('RAIN_Rain_24h', $s . 'Regenmenge der letzten 24h', 'Netatmo.Rainfall', $vpos++);
                if ($with_signal) {
                    $this->RegisterVariableInteger('RAIN_RfSignal', $s . 'Signal-Stärke', 'Netatmo.RfSignal', $vpos++);
                } else {
                    $this->UnregisterVariable('RAIN_RfSignal');
                }
                if ($with_battery) {
                    $this->RegisterVariableInteger('RAIN_Battery', $s . 'Batterie-Status', 'Netatmo.Battery', $vpos++);
                } else {
                    $this->UnregisterVariable('RAIN_Battery');
                }
            } else {
                $this->UnregisterVariable('RAIN_Rain');
                $this->UnregisterVariable('RAIN_Rain_1h');
                $this->UnregisterVariable('RAIN_Rain_24h');
                $this->UnregisterVariable('RAIN_RfSignal');
                $this->UnregisterVariable('RAIN_Battery');
            }

            // Inspired by module SymconTest/HookServe
            $this->RegisterHook('/hook/NetatmoWeather');

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
        $min = $this->ReadPropertyInteger('UpdateDataInterval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->SetTimerInterval('UpdateDataWeather', $msec);
    }

    public function UpdateData()
    {
        $wunderground_url = 'https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php';
        $netatmo_auth_url = 'https://api.netatmo.net/oauth2/token';
        $netatmo_api_url = 'https://api.netatmo.net/api/devicelist';

        $netatmo_user = $this->ReadPropertyString('Netatmo_User');
        $netatmo_password = $this->ReadPropertyString('Netatmo_Password');
        $netatmo_client = $this->ReadPropertyString('Netatmo_Client');
        $netatmo_secret = $this->ReadPropertyString('Netatmo_Secret');
        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        $station_name = $this->ReadPropertyString('station_name');
        $base_module_name = $this->ReadPropertyString('base_module_name');
        $outdoor_module_name = $this->ReadPropertyString('outdoor_module_name');
        $indoor1_module_name = $this->ReadPropertyString('indoor1_module_name');
        $indoor2_module_name = $this->ReadPropertyString('indoor2_module_name');
        $indoor3_module_name = $this->ReadPropertyString('indoor3_module_name');
        $rain_module_name = $this->ReadPropertyString('rain_module_name');
        $wind_module_name = $this->ReadPropertyString('wind_module_name');

        $token = $this->ReadPropertyString('token');
        $token_expiration = $this->ReadPropertyInteger('token_expiration');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $minutes2fail = $this->ReadPropertyInteger('minutes2fail');

        $battery_indicator = false;
        $module_data = '';

        $err = '';

        if ($token_expiration < time()) {
            $postdata = [
                'grant_type'    => 'password',
                'client_id'     => $netatmo_client,
                'client_secret' => $netatmo_secret,
                'username'      => $netatmo_user,
                'password'      => $netatmo_password,
                'scope'         => 'read_station'
            ];

            $this->SendDebug($this->scriptName, "netatmo-auth-url: $netatmo_auth_url, postdata=" . print_r($postdata, true), 0);

            $token = '';
            $token_expiration = 0;

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
            } elseif ($response == '') {
                $err = 'no response from netatmo';
            } else {
                $params = json_decode($response, true);
                if ($params == '') {
                    $err = 'malformed response response from netatmo';
                } elseif ($params['access_token'] == '') {
                    $err = "no 'access_token' in response from netatmo";
                } else {
                    $token = $params['access_token'];
                    $expires_in = $params['expires_in'];
                    $token_expiration = time() + $expires_in - 60;
                }
            }

            IPS_SetProperty($this->InstanceID, 'token', $token);
            IPS_SetProperty($this->InstanceID, 'token_expiration', $token_expiration);
            $this->SendDebug($this->scriptName, "token=$token, expiration=$token_expiration", 0);
            IPS_ApplyChanges($this->InstanceID);

            if ($err != '') {
                $this->SendDebug($this->scriptName, $err, 0);
                IPS_LogMessage($this->scriptName, $err);
                SetValueBoolean($this->GetIDForIdent('Status'), fail);
                SetValueString($this->GetIDForIdent('Data'), '');
                SetValueBoolean($this->GetIDForIdent('Battery'), false);
                SetValueBoolean($this->GetIDForIdent('Wunderground'), fail);
                return -1;
            }
        }

        // Anfrage mit Token
        $api_url = $netatmo_api_url . '?access_token=' . $token;

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
        } elseif ($data == '') {
            $err = 'no data from netatmo';
        } else {
            $netatmo = json_decode($data, true);
            if ($netatmo == '') {
                $err = 'malformed data from netatmo';
            }
        }
        if ($err == '') {
            $status = $netatmo['status'];
            if ($status != 'ok') {
                $err = "got status \"$status\" from netamo";
            }
        }
        if ($err == '') {
            $station_found = false;
            $devices = $netatmo['body']['devices'];
            if ($station_name != '') {
                foreach ($devices as $device) {
                    if ($station_name == $device['station_name']) {
                        $station_found = true;
                        break;
                    }
                }
            } else {
                if (count($devices) > 0) {
                    $device = $devices[0];
                    $station_name = $device['station_name'];
                    $station_found = true;
                }
            }
            if (!$station_found) {
                $err = "station \"$station_name\" don't exists";
            }
        }
        if ($err != '') {
            $this->SendDebug($this->scriptName, $err, 0);
            IPS_LogMessage($this->scriptName, $err);
            SetValueBoolean($this->GetIDForIdent('Status'), fail);
            SetValueString($this->GetIDForIdent('Data'), '');
            SetValueBoolean($this->GetIDForIdent('Battery'), false);
            SetValueBoolean($this->GetIDForIdent('Wunderground'), fail);
            return -1;
        }

        $now = time();

        $place = $device['place'];
        $altitude = $place['altitude'];
        $longitude = $place['location'][0];
        $latitude = $place['location'][1];

        IPS_SetProperty($this->InstanceID, 'station_altitude', $altitude);
        IPS_SetProperty($this->InstanceID, 'station_longitude', $longitude);
        IPS_SetProperty($this->InstanceID, 'station_latitude', $latitude);
        $this->SendDebug($this->scriptName, "station \"$station_name\": altitude=$altitude, longitude=$longitude, latitude=$latitude", 0);
        IPS_ApplyChanges($this->InstanceID);

        // base module
        $module_type = 'Basismodul';
        $module_name = $device['module_name'];
        $Temperature = $device['dashboard_data']['Temperature'];					// °C
        $CO2 = $device['dashboard_data']['CO2'];									// ppm
        $Humidity = $device['dashboard_data']['Humidity'];							// %
        $Noise = $device['dashboard_data']['Noise'];								// dB
        $Pressure = $device['dashboard_data']['Pressure'];							// mbar
        $AbsolutePressure = $device['dashboard_data']['AbsolutePressure'];			// mbar

        $time_utc = $device['dashboard_data']['time_utc'];
        $s = $this->seconds2duration($now - $time_utc);
        $last_seen = $s != '' ? "vor $s" : '';

        // letzte Kommunikation der Station mit Netatmo
        $last_status_store = $device['last_status_store'];
        if (is_int($last_status_store)) {
            $sec = $now - $last_status_store;
            $last_contact = $this->seconds2duration($sec);
            $min = floor($sec / 60);
            if ($min > $minutes2fail) {
                $status = 'fail';
            }
        } else {
            $last_contact = $last_status_store;
        }

        $wifi_status = $device['wifi_status'];
        if ($wifi_status <= 56) {
            $wifi_status = 3;
        } // "high";
        elseif ($wifi_status <= 71) {
            $wifi_status = 2;
        } // "good";
        elseif ($wifi_status <= 86) {
            $wifi_status = 1;
        } // "average";
        else {
            $wifi_status = 0;
        } // "bad";

        $module_data[] = [
                'module_type'       => $module_type,
                'module_name'       => $module_name,
                'last_seen'         => $last_seen,
                'wifi_status'       => $wifi_status,
            ];

        $msg = "base-module \"$module_name\": Temperature=$Temperature, CO2=$CO2, Humidity=$Humidity, Noise=$Noise, Pressure=$Pressure, AbsolutePressure=$AbsolutePressure";
        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
        $msg = "        module_type=$module_type, module_name=$module_name, last_seen=$last_seen, wifi_status=$wifi_status, last_contact=$last_contact";
        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

        SetValue($this->GetIDForIdent('LastMeasure'), $time_utc);
        SetValue($this->GetIDForIdent('LastContact'), $last_contact);

        SetValue($this->GetIDForIdent('BASE_Temperature'), $Temperature);
        SetValue($this->GetIDForIdent('BASE_CO2'), $CO2);
        SetValue($this->GetIDForIdent('BASE_Humidity'), $Humidity);
        SetValue($this->GetIDForIdent('BASE_Noise'), $Noise);
        SetValue($this->GetIDForIdent('BASE_Pressure'), $Pressure);
        if ($with_absolute_pressure) {
            SetValue($this->GetIDForIdent('BASE_AbsolutePressure'), $AbsolutePressure);
        }
        if ($with_signal) {
            SetValue($this->GetIDForIdent('BASE_Wifi'), $wifi_status);
        }
        if ($with_absolute_humidity) {
            $abs_humidity = $this->calcAbsoluteHumidity($Temperature, $Humidity);
            SetValue($this->GetIDForIdent('BASE_AbsoluteHumidity'), $abs_humidity);
        }

        $modules = $netatmo['body']['modules'];
        foreach (['NAModule4', 'NAModule1', 'NAModule3', 'NAModule2'] as $types) {
            foreach ($modules as $module) {
                if ($module['type'] != $types) {
                    continue;
                }
                $module_name = $module['module_name'];
                switch ($module['type']) {
                    case 'NAModule1':
                        // outdoor module
                        $module_type = 'Außenmodul';
                        $Temperature = $module['dashboard_data']['Temperature'];		// °C
                        $Humidity = $module['dashboard_data']['Humidity'];				// %
                        $time_utc = $module['dashboard_data']['time_utc'];
                        $min_temp = $module['dashboard_data']['min_temp'];
                        $max_temp = $module['dashboard_data']['max_temp'];
                        $date_min_temp = $module['dashboard_data']['date_min_temp'];
                        $date_max_temp = $module['dashboard_data']['date_max_temp'];

                        $battery_map = [4000, 4500, 5000, 5500, 6000];
                        break;
                    case 'NAModule2':
                        // wind gauge
                        $module_type = 'Windmesser';
                        $WindSpeed = $module['dashboard_data']['WindStrength'];		// km/h
                        $WindAngle = $module['dashboard_data']['WindAngle'];			// angles
                        $GustSpeed = $module['dashboard_data']['GustStrength'];		// km/h
                        $GustAngle = $module['dashboard_data']['GustAngle'];			// angles
                        $time_utc = $module['dashboard_data']['time_utc'];

                        $battery_map = [4360, 4770, 5180, 5590, 6000];
                        break;
                    case 'NAModule3':
                        // rain gauge
                        $module_type = 'Regenmesser';
                        $Rain = $module['dashboard_data']['Rain'];						// mm
                        $sum_rain_1 = $module['dashboard_data']['sum_rain_1'];			// mm
                        $sum_rain_24 = $module['dashboard_data']['sum_rain_24'];		// mm
                        $time_utc = $module['dashboard_data']['time_utc'];

                        $battery_map = [4000, 4500, 5000, 5500, 6000];
                        break;
                    case 'NAModule4':
                        // indoor modules
                        $module_type = 'Innenmodul';
                        $Temperature = $module['dashboard_data']['Temperature'];		// °C
                        $Humidity = $module['dashboard_data']['Humidity'];				// %
                        $CO2 = $module['dashboard_data']['CO2'];						// ppm
                        $time_utc = $module['dashboard_data']['time_utc'];
                        $min_temp = $module['dashboard_data']['min_temp'];
                        $max_temp = $module['dashboard_data']['max_temp'];
                        $date_min_temp = $module['dashboard_data']['date_min_temp'];
                        $date_max_temp = $module['dashboard_data']['date_max_temp'];

                        $battery_map = [4560, 4920, 5280, 5640, 6000];
                        break;
                }

                $s = $this->seconds2duration($now - $time_utc);
                $last_seen = $s != '' ? "vor $s" : '';

                $rf_status = $module['rf_status'];
                if ($rf_status <= 60) {
                    $rf_status = 4;
                } // "full";
                elseif ($rf_status <= 70) {
                    $rf_status = 3;
                } // "high";
                elseif ($rf_status <= 80) {
                    $rf_status = 2;
                } // "medium";
                elseif ($rf_status <= 90) {
                    $rf_status = 1;
                } // "low";
                else {
                    $rf_status = 0;
                } // "verylow";
                $battery_status = '';
                $battery_vp = $module['battery_vp'];
                if ($battery_vp < $battery_map[0]) {
                    $battery_status = 0;
                } // "very low";
                elseif ($battery_vp < $battery_map[1]) {
                    $battery_status = 1;
                } // "low";
                elseif ($battery_vp < $battery_map[2]) {
                    $battery_status = 2;
                } // "medium";
                elseif ($battery_vp < $battery_map[3]) {
                    $battery_status = 3;
                } // "high";
                elseif ($battery_vp < $battery_map[4]) {
                    $battery_status = 4;
                } // "full";
                else {
                    $battery_status = 5;
                } // "max";
                if ($battery_status < 2) {
                    $battery_indicator = true;
                }

                $module_data[] = [
                        'module_type'		  => $module_type,
                        'module_name'		  => $module_name,
                        'last_seen'			   => $last_seen,
                        'rf_status'			   => $rf_status,
                        'battery_status'	=> $battery_status,
                    ];

                switch ($module['type']) {
                    case 'NAModule1':
                        SetValue($this->GetIDForIdent('OUT_Temperature'), $Temperature);
                        SetValue($this->GetIDForIdent('OUT_Humidity'), $Humidity);
                        if ($with_signal) {
                            SetValue($this->GetIDForIdent('OUT_RfSignal'), $rf_status);
                        }
                        if ($with_battery) {
                            SetValue($this->GetIDForIdent('OUT_Battery'), $battery_status);
                        }
                        if ($with_absolute_humidity) {
                            $abs_humidity = $this->calcAbsoluteHumidity($Temperature, $Humidity);
                            SetValue($this->GetIDForIdent('OUT_AbsoluteHumidity'), $abs_humidity);
                        }
                        if ($with_dewpoint) {
                            $dewpoint = $this->calcDewpoint($Temperature, $Humidity);
                            SetValue($this->GetIDForIdent('OUT_Dewpoint'), $dewpoint);
                        }
                        if ($with_heatindex) {
                            $heatindex = $this->calcHeatindex($Temperature, $Humidity);
                            SetValue($this->GetIDForIdent('OUT_Heatindex'), $heatindex);
                        }
                        if ($with_dewpoint) {
                            $dewpoint = $this->calcDewpoint($Temperature, $Humidity);
                            SetValue($this->GetIDForIdent('OUT_Dewpoint'), $dewpoint);
                        }

                        $msg = "outdoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity";
                        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

                        break;
                    case 'NAModule2':
                        SetValue($this->GetIDForIdent('WIND_WindSpeed'), $WindSpeed);
                        SetValue($this->GetIDForIdent('WIND_WindAngle'), $WindAngle);
                        SetValue($this->GetIDForIdent('WIND_GustSpeed'), $GustSpeed);
                        SetValue($this->GetIDForIdent('WIND_GustAngle'), $GustAngle);
                        if ($with_windstrength) {
                            $windstrength = $this->windspeed2bft($WindSpeed);
                            SetValue($this->GetIDForIdent('WIND_WindStrength'), $windstrength);
                            $guststrength = $this->windspeed2bft($GustSpeed);
                            SetValue($this->GetIDForIdent('WIND_GustStrength'), $guststrength);
                        }
                        if ($with_signal) {
                            SetValue($this->GetIDForIdent('WIND_RfSignal'), $rf_status);
                        }
                        if ($with_battery) {
                            SetValue($this->GetIDForIdent('WIND_Battery'), $battery_status);
                        }

                        $msg = "wind gauge \"$module_name\": WindSpeed=$WindSpeed, WindAngle=$WindAngle, GustSpeed=$GustSpeed, GustAngle=$GustAngle";
                        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

                        break;
                    case 'NAModule3':
                        SetValue($this->GetIDForIdent('RAIN_Rain'), $Rain);
                        SetValue($this->GetIDForIdent('RAIN_Rain_1h'), $sum_rain_1);
                        SetValue($this->GetIDForIdent('RAIN_Rain_24h'), $sum_rain_24);
                        if ($with_signal) {
                            SetValue($this->GetIDForIdent('RAIN_RfSignal'), $rf_status);
                        }
                        if ($with_battery) {
                            SetValue($this->GetIDForIdent('RAIN_Battery'), $battery_status);
                        }

                        $msg = "rain gauge \"$module_name\": Rain=$Rain, sum_rain_1=$sum_rain_1, sum_rain_24=$sum_rain_24";
                        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

                        break;
                    case 'NAModule4':
                        if ($module_name == $indoor1_module_name) {
                            $pfx = 'IN1';
                        } elseif ($module_name == $indoor2_module_name) {
                            $pfx = 'IN2';
                        } elseif ($module_name == $indoor3_module_name) {
                            $pfx = 'IN3';
                        }
                        SetValue($this->GetIDForIdent($pfx . '_Temperature'), $Temperature);
                        SetValue($this->GetIDForIdent($pfx . '_CO2'), $CO2);
                        SetValue($this->GetIDForIdent($pfx . '_Humidity'), $Humidity);
                        if ($with_signal) {
                            SetValue($this->GetIDForIdent($pfx . '_RfSignal'), $rf_status);
                        }
                        if ($with_battery) {
                            SetValue($this->GetIDForIdent($pfx . '_Battery'), $battery_status);
                        }
                        if ($with_absolute_humidity) {
                            $abs_humidity = $this->calcAbsoluteHumidity($Temperature, $Humidity);
                            SetValue($this->GetIDForIdent($pfx . '_AbsoluteHumidity'), $abs_humidity);
                        }

                        $msg = "indoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity, CO2=$CO2";
                        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

                        break;
                }
                $msg = "        module_type=$module_type, module_name=$module_name, last_seen=$last_seen, rf_status=$rf_status, battery_status=$battery_status";
                $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
            }

            if ($with_windchill) {
                $temp = GetValue($this->GetIDForIdent('OUT_Temperature'));
                $speed = GetValue($this->GetIDForIdent('WIND_WindSpeed'));
                $windchill = $this->calcWindchill($temp, $speed);
                SetValue($this->GetIDForIdent('OUT_Windchill'), $windchill);
            }
        }

        $station_data = [
                'now'			       => $now,
                'status'		     => $netatmo['status'],
                'last_contact'	=> $last_contact,
                'station_name'	=> $station_name,
                'modules'		    => $module_data,
            ];

        SetValueBoolean($this->GetIDForIdent('Status'), true);
        SetValueString($this->GetIDForIdent('Data'), json_encode($station_data));
        SetValueBoolean($this->GetIDForIdent('Battery'), $battery_indicator);

        $img_path = '/hook/NetatmoWeather/imgs/';

        $html = '';

        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; font-family: 'Open Sans', sans-serif; font-size: 14px; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1; }\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "#spalte_type { width: 25px; }\n";
        $html .= "#spalte_signal { width: 50px; }\n";
        $html .= "#spalte_battery { width: 50px; }\n";
        $html .= "</style>\n";

        $dt = date('d.m. H:i', $now);
        $s = '<font size="-1">Stand:</font> ';
        $s .= $dt;
        $s .= '&emsp;';
        $s .= '<font size="-1">Status:</font> ';
        $s .= $station_data['status'];
        $s .= ' <font size="-2">(' . $station_data['last_contact'] . ')</font>';
        $html .= "<center>$s</center>\n";

        // Tabelle
        $html .= "<table>\n";
        // Spaltenbreite
        $html .= "<colgroup><col id=\"spalte_type\"></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col id=\"spalte_signal\"></colgroup>\n";
        $html .= "<colgroup><col id=\"spalte_battry\"></colgroup>\n";
        $html .= "<tdata>\n";

        $html .= "<tr>\n";
        $html .= "<th></td>\n";
        $html .= "<th>Modultyp</th>\n";
        $html .= "<th>Name</th>\n";
        $html .= "<th>letzte Meldung</th>\n";
        $html .= "<th style='padding: 0; text-align: left'>Signal</th>\n";
        $html .= "<th style='padding: 0; text-align: left'>Batterie</th>\n";
        $html .= "</tr>\n";

        $modules = $station_data['modules'];
        foreach ($modules as $module) {
            $module_type = $module['module_type'];
            $module_type_img = $img_path . $this->module2img($module_type);
            $module_name = $module['module_name'];
            $module_type = $module['module_type'];
            $last_seen = $module['last_seen'];

            $html .= "<tr>\n";
            $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type'</td>\n";
            $html .= "<td>$module_type</td>\n";
            $html .= "<td>$module_name</td>\n";
            $html .= "<td>$last_seen</td>\n";

            if ($module_type == 'Basismodul') {
                $wifi_status = $module['wifi_status'];
                $wifi_status_text = $this->wifi_status2text($wifi_status);
                $wifi_status_img = $img_path . $this->wifi_stautus2img($wifi_status);
                $html .= "<td><img src=$wifi_status_img width='30' height='20' title='$wifi_status_text'></td>\n";
                $html .= "<td>&nbsp;</td>\n";
            } else {
                $rf_status = $module['rf_status'];
                $rf_status_text = $this->signal_status2text($rf_status);
                $rf_status_img = $img_path . $this->signal_status2img($rf_status);
                $battery_status = $module['battery_status'];
                $battery_status_text = $this->battery_status2text($battery_status);
                $battery_status_img = $img_path . $this->battery_status2img($battery_status);
                $html .= "<td><img src=$rf_status_img width='25' height='20' title='$rf_status_text'></td>\n";
                $html .= "<td><img src=$battery_status_img width='30' height='15' title='$battery_status_text'></td>\n";
            }

            $html .= "</tr>\n";
        }

        $html .= "</tdata>\n";
        $html .= "</table>\n";

        SetValueString($this->GetIDForIdent('StatusImage'), $html);

        // Messwerte für Wunderground bereitstellen
        $pressure = '';
        $time_utc = '';
        $temp = '';
        $humidity = '';
        $rain = '';
        $sum_rain_1 = '';
        $sum_rain_24 = '';
        $winddir = '';
        $windspeed = '';
        $windgustdir = '';
        $windgust = '';

        $pressure = $device['dashboard_data']['AbsolutePressure'];
        $time_utc = $device['dashboard_data']['time_utc'];

        $modules = $netatmo['body']['modules'];
        foreach ($modules as $i => $value) {
            $module = $modules[$i];
            switch ($module['type']) {
                case 'NAModule1':
                    $type = 'outdoor';
                    $temp = $module['dashboard_data']['Temperature'];
                    $humidity = $module['dashboard_data']['Humidity'];
                    break;
                case 'NAModule2':
                    $type = 'wind';
                    $winddir = $module['dashboard_data']['WindAngle'];
                    $windspeed = $module['dashboard_data']['WindStrength'];
                    $windgustdir = $module['dashboard_data']['GustAngle'];
                    $windgust = $module['dashboard_data']['GustStrength'];
                    break;
                case 'NAModule3':
                    $type = 'rain';
                    $rain = $module['dashboard_data']['Rain'];
                    $sum_rain_1 = $module['dashboard_data']['sum_rain_1'];
                    $sum_rain_24 = $module['dashboard_data']['sum_rain_24'];
                    break;
                case 'NAModule4':
                    $type = 'indoor';
                    break;
                default:
                    break;
            }
        }

        $param = '&dateutc=' . rawurlencode(date('Y-m-d G:i:s', $time_utc));
        if (strlen($temp)) {
            $param .= '&tempf=' . rawurlencode($this->celsius2farenheit($temp));
        }
        if (strlen($humidity)) {
            $param .= '&humidity=' . rawurlencode($humidity);
        }
        if (strlen($temp) && strlen($humidity)) {
            $dewpoint = $this->calcDewpoint($temp, $humidity);
            $param .= '&dewptf=' . rawurlencode($this->celsius2farenheit($dewpoint));
        }
        if (strlen($pressure)) {
            $param .= '&baromin=' . rawurlencode($this->mb2inch($pressure));
        }
        if (strlen($sum_rain_1)) {
            $param .= '&rainin=' . rawurlencode($this->mm2inch($sum_rain_1));
        }
        if (strlen($sum_rain_24)) {
            $param .= '&dailyrainin=' . rawurlencode($this->mm2inch($sum_rain_24));
        }
        if (strlen($windspeed)) {
            $param .= '&windspeedmph=' . rawurlencode($this->km2mile($windspeed)) . '&winddir=' . rawurlencode($winddir);
        }
        if (strlen($windgust)) {
            $param .= '&windgustmph=' . rawurlencode($this->km2mile($windgust)) . '&windgustdir=' . rawurlencode($windgustdir);
        }

        $msg = 'temp=' . $temp . '°C';
        $msg .= ', humidity=' . $humidity . '%';
        $msg .= ', rain 1h=' . $sum_rain_1 . ' mm';
        $msg .= ', 24h=' . $sum_rain_24 . ' mm';
        $msg .= ', wind=' . $windspeed . ' km/h';
        $msg .= ' (' . $winddir . '°)';
        $msg .= ', gust=' . $windgust . ' km/h';
        $msg .= ' (' . $windgustdir . '°)';
        $msg .= ', pressure=' . $pressure . ' mb';

        IPS_LogMessage($this->scriptName, $msg);

        $url = $wunderground_url . '?ID=' . $wunderground_id . '&PASSWORD=' . $wunderground_key . '&action=updateraw' . $param;

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
        if ($err != '') {
            $this->SendDebug($this->scriptName, $err, 0);
            IPS_LogMessage($this->scriptName, $err);
            SetValueBoolean($this->GetIDForIdent('Wunderground'), fail);
            return -1;
        }

        SetValueBoolean($this->GetIDForIdent('Wunderground'), true);
    }

    // Variablenprofile erstellen
    private function CreateVarProfile($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon)
    {
        if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, $ProfileType);
            IPS_SetVariableProfileText($name, '', $Suffix);
            IPS_SetVariableProfileValues($name, $MinValue, $MaxValue, $StepSize);
            IPS_SetVariableProfileDigits($name, $Digits);
            IPS_SetVariableProfileIcon($name, $Icon);
        }
    }

    // Inspired from module SymconTest/HookServe
    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        IPS_LogMessage('RegisterHook', print_r($ids, true));
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function ProcessHook_Status()
    {
        $s = GetValueString($this->GetIDForIdent('Data'));
        $station_data = json_decode($s, true);

        $html = '';

        $html .= "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
        $html .= "<link href=\"https://fonts.googleapis.com/css?family=Open+Sans\" rel=\"stylesheet\">\n";
        $html .= "<title>Status von Netatmo</title>\n";
        $html .= "<style>\n";
        $html .= "html { height: 100%; color: #ffffff; background-color: #303030; overflow: hidden; }\n";
        $html .= "body { table-cell; text-align: left; vertical-align: top; height: 100%; }\n";
        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; font-family: 'Open Sans', sans-serif; font-size: 14px; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1; }\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "#spalte_type { width: 25px; }\n";
        $html .= "#spalte_signal { width: 30px; }\n";
        $html .= "#spalte_battery { width: 30px; }\n";
        $html .= "</style>\n";

        $dt = date('d.m. H:i', $station_data['now']);
        $s = '<font size="-1">Stand:</font> ';
        $s .= $dt;
        $s .= '&emsp;';
        $s .= '<font size="-1">Status:</font> ';
        $s .= $station_data['status'];
        $s .= ' <font size="-2">(' . $station_data['last_contact'] . ')</font>';
        $html .= "<center>$s</center>\n";

        if (isset($station_data['modules'])) {
            // Tabelle
            $html .= "<table>\n";
            // Spaltenbreite
            $html .= "<colgroup><col id=\"spalte_type\"></colgroup>\n";
            $html .= "<colgroup><col></colgroup>\n";
            $html .= "<colgroup><col></colgroup>\n";
            $html .= "<colgroup><col id=\"spalte_signal\"></colgroup>\n";
            $html .= "<colgroup><col id=\"spalte_battry\"></colgroup>\n";
            $html .= "<tdata>\n";

            $img_path = '/hook/NetatmoWeather/imgs/';

            $modules = $station_data['modules'];
            foreach ($modules as $module) {
                $module_type = $module['module_type'];
                $module_type_img = $img_path . $this->module2img($module_type);
                $module_name = $module['module_name'];
                $last_seen = $module['last_seen'];

                $html .= "<tr>\n";
                $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type'</td>\n";
                $html .= "<td>$module_name</td>\n";
                $html .= "<td>$last_seen</td>\n";

                if ($module_type == 'Basismodul') {
                    $wifi_status = $module['wifi_status'];
                    $wifi_status_text = $this->wifi_status2text($wifi_status);
                    $wifi_status_img = $img_path . $this->wifi_stautus2img($wifi_status);
                    $html .= "<td><img src=$wifi_status_img width='30' height='20' title='$wifi_status_text'></td>\n";
                    $html .= "<td>&nbsp;</td>\n";
                } else {
                    $rf_status = $module['rf_status'];
                    $rf_status_text = $this->signal_status2text($rf_status);
                    $rf_status_img = $img_path . $this->signal_status2img($rf_status);
                    $battery_status = $module['battery_status'];
                    $battery_status_text = $this->battery_status2text($battery_status);
                    $battery_status_img = $img_path . $this->battery_status2img($battery_status);
                    $html .= "<td><img src=$rf_status_img width='25' height='20' title='$rf_status_text'></td>\n";
                    $html .= "<td><img src=$battery_status_img width='30' height='15' title='$battery_status_text'></td>\n";
                }

                $html .= "</tr>\n";
            }

            $html .= "</tdata>\n";
            $html .= "</table>\n";
        }
        $html .= "</body>\n";
        $html .= "</html>\n";

        echo $html;
    }

    // Inspired from module SymconTest/HookServe
    protected function ProcessHookData()
    {
        $this->SendDebug('WebHook SERVER', print_r($_SERVER, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        $basename = substr($uri, strlen('/hook/NetatmoWeather/'));
        if ($basename == 'status') {
            $this->ProcessHook_Status();
            return;
        }
        $path = realpath($root . '/' . $basename);
        if ($path === false) {
            http_response_code(404);
            die('File not found!');
        }
        if (substr($path, 0, strlen($root)) != $root) {
            http_response_code(403);
            die('Security issue. Cannot leave root folder!');
        }
        header('Content-Type: ' . $this->GetMimeType(pathinfo($path, PATHINFO_EXTENSION)));
        readfile($path);
    }

    // Inspired from module SymconTest/HookServe
    private function GetMimeType($extension)
    {
        $lines = file(IPS_GetKernelDirEx() . 'mime.types');
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $extension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    // Module
    private function module2img($val)
    {
        $val2img = [
            'Basismodul'  => 'module_int.png',
            'Außenmodul'  => 'module_ext.png',
            'Windmesser'  => 'module_wind.png',
            'Regenmesser' => 'module_rain.png',
            'Innenmodul'  => 'module_ext.png',
        ];

        if ($val >= 0 && $val < count($val2img)) {
            $img = $val2img[$val];
        } else {
            $img = '';
        }
        return $img;
    }

    // Wifi-Status
    private function wifi_status2text($status)
    {
        $status2txt = [
            'schwach',
            'mittel',
            'gut',
            'hoch',
        ];

        if ($status >= 0 && $status < count($status2txt)) {
            $txt = $status2txt[$status];
        } else {
            $txt = '';
        }
        return $txt;
    }

    private function wifi_stautus2img($status)
    {
        $status2img = [
            'wifi_low.png',
            'wifi_medium.png',
            'wifi_high.png',
            'wifi_full.png',
        ];

        if ($status >= 0 && $status < count($status2img)) {
            $img = $status2img[$status];
        } else {
            $img = '';
        }
        return $img;
    }

    // RF-Status
    private function signal_status2text($status)
    {
        $status2txt = [
            'minimal',
            'schwach',
            'mittel',
            'hoch',
            'voll',
        ];

        if ($status >= 0 && $status < count($status2txt)) {
            $txt = $status2txt[$status];
        } else {
            $txt = '';
        }
        return $txt;
    }

    private function signal_status2img($status)
    {
        $status2img = [
            'signal_verylow.png',
            'signal_low.png',
            'signal_medium.png',
            'signal_high.png',
            'signal_full.png',
        ];

        if ($status >= 0 && $status < count($status2img)) {
            $img = $status2img[$status];
        } else {
            $img = '';
        }
        return $img;
    }

    // Battery-Status
    private function battery_status2text($status)
    {
        $status2txt = [
            'leer',
            'schwach',
            'mittel',
            'hoch',
            'voll',
            'max',
        ];

        if ($status >= 0 && $status < count($status2txt)) {
            $txt = $status2txt[$status];
        } else {
            $txt = '';
        }
        return $txt;
    }

    private function battery_status2img($status)
    {
        $status2img = [
            'battery_verylow.png',
            'battery_low.png',
            'battery_medium.png',
            'battery_high.png',
            'battery_full.png',
            'battery_full.png',
        ];

        if ($status >= 0 && $status < count($status2img)) {
            $img = $status2img[$status];
        } else {
            $img = '';
        }
        return $img;
    }

    private function km2mile($i)
    {
        if (is_int($i) || is_float($i)) {
            $o = $i / 1.609344;
        } else {
            $o = '';
        }
        return number_format($o, 6, '.', '');
    }

    private function mm2inch($i)
    {
        if (is_int($i) || is_float($i)) {
            $o = $i / 25.4;
        } else {
            $o = '';
        }
        return number_format($o, 4, '.', '');
    }

    private function celsius2farenheit($i)
    {
        if (is_int($i) || is_float($i)) {
            $o = ($i * 1.8) + 32;
        } else {
            $o = '';
        }
        return number_format($o, 1, '.', '');
    }

    private function mb2inch($i)
    {
        if (is_int($i) || is_float($i)) {
            $o = $i / 1000 * 29.53;
        } else {
            $o = '';
        }
        return number_format($o, 4, '.', '');
    }

    // Sekunden in Menschen-lesbares Format umwandeln
    private function seconds2duration($sec)
    {
        $duration = '';
        if ($sec > 3600) {
            $duration .= sprintf('%dh', floor($sec / 3600));
            $sec = $sec % 3600;
        }
        if ($sec > 60) {
            $duration .= sprintf('%dm', floor($sec / 60));
            $sec = $sec % 60;
        }
        if ($sec > 0) {
            $duration .= sprintf('%ds', $sec);
            $sec = floor($sec);
        }

        return $duration;
    }

    // Taupunkt berechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    private function calcDewpoint($temp, $humidity)
    {
        if ($temp > 0) {
            $k2 = 17.62;
            $k3 = 243.12;
        } else {
            $k2 = 22.46;
            $k3 = 272.62;
        }
        $dewpoint = $k3 * (($k2 * $temp) / ($k3 + $temp) + log($humidity / 100));
        $dewpoint = $dewpoint / (($k2 * $k3) / ($k3 + $temp) - log($humidity / 100));
        $dewpoint = round($dewpoint, 0);
        return $dewpoint;
    }

    // relative Luffeuchtigkeit in absolute Feuchte umrechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    private function calcAbsoluteHumidity($temp, $humidity)
    {
        if ($temp >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }

        $R = 8314.3; // universelle Gaskonstante in J/(kmol*K)
        $mw = 18.016; // Molekulargewicht des Wasserdampfes in kg/kmol

        // Sättigungsdamphdruck in hPa
        $SDD = 6.1078 * pow(10, (($a * $temp) / ($b + $temp)));

        // Dampfdruck in hPa
        $DD = $humidity / 100 * $SDD;

        $v = log10($DD / 6.1078);

        // Taupunkttemperatur in °C
        $TD = $b * $v / ($a - $v);

        // Temperatur in Kelvin
        $TK = $temp + 273.15;

        // absolute Feuchte in g Wasserdampf pro m3 Luft
        $AF = pow(10, 5) * $mw / $R * $DD / $TK;
        $AF = round($AF * 10) / 10; // auf eine NK runden

        return $AF;
    }

    // gemessenen Luftdruck in absoluen Luftdruck (Meereshöhe) umrechnen
    //   Quelle: https://rechneronline.de/barometer/hoehe.php
    private function calcAbsolutePressure($pressure, $temp, $altitude)
    {
        // Temperaturgradient (geschätzt)
        $TG = 0.0065;

        // Höhe = Differenz Meereshöhe zu Standort
        $ad = $altitude * -1;

        // Temperatur auf Meereshöhe herunter rechnen
        //     Schätzung: Temperatur auf Meereshöhe = Temperatur + Temperaturgradient * Höhe
        $T = $temp + $TG * $ad;
        // Temperatur in Kelvin
        $TK = $T + 273.15;

        // Luftdruck auf Meereshöhe = Barometeranzeige / (1-Temperaturgradient*Höhe/Temperatur auf Meereshöhe in Kelvin)^(0,03416/Temperaturgradient)
        $AP = $pressure / pow((1 - $TG * $ad / $TK), (0.03416 / $TG));

        return $AP;
    }

    // Windrichtung in Grad als Bezeichnung ausgeben
    //   Quelle: https://www.windfinder.com/wind/windspeed.htm
    public function winddir2text($dir)
    {
        $dir2txt = [
            'N',
            'NNO',
            'NO',
            'ONO',
            'O',
            'OSO',
            'SO',
            'SSO',
            'S',
            'SSW',
            'SW',
            'WSW',
            'W',
            'WNW',
            'NW',
            'NNW',
        ];

        $idx = int((($dir + 11.25) % 360) / 22.5);
        if ($idx >= 0 && $idx < count($dir2txt)) {
            $txt = $dir2txt[$idx];
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Windgeschwindigkeit in Beaufort umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Beaufortskala
    public function windspeed2bft($speed)
    {
        $kmh2bft = [0.3, 1.6, 3.4, 5.5, 8.0, 10.8, 13.9, 17.2, 20.8, 24.5, 28.5, 32.7];

        $ms = $speed / 3.6;
        for ($i = 0; $i < count($kmh2bft); $i++) {
            if ($ms < $kmh2bft[$i]) {
                break;
            }
        }
        return $i;
    }

    // Windstärke als Text ausgeben
    //  Quelle: https://de.wikipedia.org/wiki/Beaufortskala
    public function bft2text($bft)
    {
        $bft2txt = [
            'Windstille',
            'leiser Zug',
            'leichte Brise',
            'schwache Brise',
            'mäßige Brise',
            'frische Brise',
            'starker Wind',
            'steifer Wind',
            'stürmischer Wind',
            'Sturm',
            'schwerer Sturm',
            'orkanartiger Sturm',
            'Orkan'
        ];

        if ($bft >= 0 && $bft < count($bft2txt)) {
            $txt = $bft2txt[$bft];
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Temperautur in Windchill umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Windchill
    public function calcWindchill($temp, $speed)
    {
        if ($speed >= 5.0) {
            $wct = 13.12 + (0.6215 * $temp) - (11.37 * pow($speed, 0.16)) + (0.3965 * $temp * pow($speed, 0.16));
            $wct = round($wct * 10) / 10; // auf eine NK runden
        } else {
            $wct = $temp;
        }
        return $wct;
    }

    // Temperatur als Heatindex umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Hitzeindex
    public function calcHeatindex($temp, $hum)
    {
        $c1 = -8.784695;
        $c2 = 1.61139411;
        $c3 = 2.338549;
        $c4 = -0.14611605;
        $c5 = -1.2308094 * pow(10, -2);
        $c6 = -1.6424828 * pow(10, -2);
        $c7 = 2.211732 * pow(10, -3);
        $c8 = 7.2546 * pow(10, -4);
        $c9 = -3.582 * pow(10, -6);

        $hi = $c1
            + $c2 * $temp
            + $c3 * $hum
            + $c4 * $temp * $hum
            + $c5 * pow($temp, 2)
            + $c6 * pow($hum, 2)
            + $c7 * pow($temp, 2) * $hum
            + $c8 * $temp * pow($hum, 2)
            + $c9 * pow($temp, 2) * pow($hum, 2);
        $hi = round($hi); // ohne NK
        return $hi;
    }
}