<?php

class NetatmoWeatherDevice extends IPSModule
{
    private $scriptName = 'NetatmoWeatherDevice';

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Wunderground_ID', '');
        $this->RegisterPropertyString('Wunderground_Key', '');

        $this->RegisterPropertyString('module_id', '');
        $this->RegisterPropertyString('module_type', '');

        $this->RegisterPropertyString('station_id', '');

        $this->RegisterPropertyInteger('station_altitude', 0);
        $this->RegisterPropertyFloat('station_longitude', 0);
        $this->RegisterPropertyFloat('station_latitude', 0);

        $this->RegisterPropertyInteger('minutes2fail', 0);

        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_absolute_humidity', false);
        $this->RegisterPropertyBoolean('with_dewpoint', false);
        $this->RegisterPropertyBoolean('with_windchill', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_windstrength', false);
        $this->RegisterPropertyBoolean('with_windangle', false);
        $this->RegisterPropertyBoolean('with_winddirection', true);

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_status_box', false);

        $this->RegisterPropertyBoolean('with_last_measure', false);
        $this->RegisterPropertyBoolean('with_signal', false);
        $this->RegisterPropertyBoolean('with_battery', false);

        $this->CreateVarProfile('Netatmo.Temperatur', 2, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Netatmo.Humidity', 2, ' %', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.absHumidity', 2, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Dewpoint', 2, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Heatindex', 2, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Netatmo.Pressure', 2, ' mbar', 500, 1200, 0, 0, 'Gauge');
        $this->CreateVarProfile('Netatmo.WindSpeed', 2, ' km/h', 0, 100, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindStrength', 1, ' bft', 0, 13, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindAngle', 1, ' °', 0, 360, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Netatmo.WindDirection', 3, '', 0, 0, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Netatmo.Rainfall', 2, ' mm', 0, 60, 0, 1, 'Rainfall');

        $associations = '';
        $associations[] = ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x008040];
        $associations[] = ['Wert' => 40, 'Name' => '%d', 'Farbe' => 0xFFFF31];
        $associations[] = ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 95, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Netatmo.Noise', 1, ' dB', 0, 130, 0, 1, 'Speaker', $associations);

        $associations = '';
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000];
        $associations[] = ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Netatmo.CO2', 1, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = '';
        $associations[] = ['Wert' => false, 'Name' => 'Nein', 'Farbe' => -1];
        $associations[] = ['Wert' => true, 'Name' => 'Ja', 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Netatmo.Alarm', 0, '', 0, 0, 0, 1, 'Alert', $associations);

        $associations = '';
        $associations[] = ['Wert' => 0, 'Name' => $this->wifi_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->wifi_status2text(1), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 2, 'Name' => $this->wifi_status2text(2), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 3, 'Name' => $this->wifi_status2text(3), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.Wifi', 1, '', 0, 0, 0, 1, 'Intensity', $associations);

        $associations = '';
        $associations[] = ['Wert' => 0, 'Name' => $this->signal_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->signal_status2text(1), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 2, 'Name' => $this->signal_status2text(2), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 3, 'Name' => $this->signal_status2text(3), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 4, 'Name' => $this->signal_status2text(4), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.RfSignal', 1, '', 0, 0, 0, 1, 'Intensity', $associations);

        $associations = '';
        $associations[] = ['Wert' => 0, 'Name' => $this->battery_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->battery_status2text(1), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 2, 'Name' => $this->battery_status2text(2), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 3, 'Name' => $this->battery_status2text(3), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 4, 'Name' => $this->battery_status2text(4), 'Farbe' => 0x228B22];
        $associations[] = ['Wert' => 5, 'Name' => $this->battery_status2text(5), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.Battery', 1, '', 0, 0, 0, 1, 'Intensity', $associations);

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        $module_type = $this->ReadPropertyString('module_type');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_windangle = $this->ReadPropertyBoolean('with_windangle');
        $with_winddirection = $this->ReadPropertyBoolean('with_winddirection');
        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $vpos = 1;
        switch ($module_type) {
            case 'NAMain':
                // Basismodul

                // station-global vars

                // status of connection to netatmo
                $this->RegisterVariableBoolean('Status', $this->Translate('State'), '~Alert.Reversed', $vpos);
                $vpos++;

                if ($with_last_contact) {
                    $this->RegisterVariableString('LastContact', $this->Translate('last transmission'), '', $vpos);
                } else {
                    $this->UnregisterVariable('LastContact');
                }
                $vpos++;

                $this->RegisterVariableBoolean('BatteryAlarm', $this->Translate('Battery of one or more modules ist low or empty'), 'Netatmo.Alarm', $vpos);
                $vpos++;

                $this->RegisterVariableBoolean('ModuleAlarm', $this->Translate('station or modules stopped don\'t communicate'), 'Netatmo.Alarm', $vpos);
                $vpos++;

                if ($with_status_box) {
                    $this->RegisterVariableString('StatusBox', $this->Translate('State of station and modules'), '~HTMLBox', $vpos);
                } else {
                    $this->UnregisterVariable('StatusBox');
                }
                $vpos++;

                if ($wunderground_id != '' && $wunderground_key != '') {
                    $this->RegisterVariableBoolean('Wunderground', $this->Translate('State of upload to wunderground'), '~Alert.Reversed', $vpos);
                } else {
                    $this->UnregisterVariable('Wunderground');
                }
                $vpos++;

                // module vars

                $this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), 'Netatmo.Temperatur', $vpos);
                $vpos++;

                $this->RegisterVariableInteger('CO2', $this->Translate('CO2'), 'Netatmo.CO2', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Humidity', $this->Translate('Humidity'), 'Netatmo.Humidity', $vpos);
                $vpos++;

                if ($with_absolute_humidity) {
                    $this->RegisterVariableFloat('AbsoluteHumidity', $this->Translate('absolute humidity'), 'Netatmo.absHumidity', $vpos);
                } else {
                    $this->UnregisterVariable('AbsoluteHumidity');
                }
                $vpos++;

                if ($with_dewpoint) {
                    $this->RegisterVariableFloat('Dewpoint', $this->Translate('Dewpoint'), 'Netatmo.Dewpoint', $vpos);
                } else {
                    $this->UnregisterVariable('Dewpoint');
                }
                $vpos++;

                if ($with_heatindex) {
                    $this->RegisterVariableFloat('Heatindex', $this->Translate('Heatindex'), 'Netatmo.Heatindex', $vpos);
                } else {
                    $this->UnregisterVariable('Heatindex');
                }
                $vpos++;

                $this->RegisterVariableInteger('Noise', $this->Translate('Noise'), 'Netatmo.Noise', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Pressure', $this->Translate('Air pressure'), 'Netatmo.Pressure', $vpos);
                $vpos++;

                if ($with_absolute_pressure) {
                    $this->RegisterVariableFloat('AbsolutePressure', $this->Translate('absolute pressure'), 'Netatmo.Pressure', $vpos);
                } else {
                    $this->UnregisterVariable('AbsolutePressure');
                }
                $vpos++;

                if ($with_last_measure) {
                    $this->RegisterVariableInteger('LastMeasure', $this->Translate('last measurement'), '~UnixTimestamp', $vpos);
                } else {
                    $this->UnregisterVariable('LastMeasure');
                }
                $vpos++;

                if ($with_signal) {
                    $this->RegisterVariableInteger('Wifi', $this->Translate('Strength of wifi-signal'), 'Netatmo.Wifi', $vpos);
                } else {
                    $this->UnregisterVariable('Wifi');
                }
                $vpos++;

                break;
            case 'NAModule1':
                // Außenmodul

                $this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), 'Netatmo.Temperatur', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Humidity', $this->Translate('Humidity'), 'Netatmo.Humidity', $vpos);
                $vpos++;

                if ($with_absolute_humidity) {
                    $this->RegisterVariableFloat('AbsoluteHumidity', $this->Translate('absolute humidity'), 'Netatmo.absHumidity', $vpos);
                } else {
                    $this->UnregisterVariable('AbsoluteHumidity');
                }
                $vpos++;

                if ($with_dewpoint) {
                    $this->RegisterVariableFloat('Dewpoint', $this->Translate('Dewpoint'), 'Netatmo.Dewpoint', $vpos);
                } else {
                    $this->UnregisterVariable('Dewpoint');
                }
                $vpos++;

                if ($with_windchill) {
                    $this->RegisterVariableFloat('Windchill', $this->Translate('Windchill'), 'Netatmo.Temperatur', $vpos);
                } else {
                    $this->UnregisterVariable('Windchill');
                }
                $vpos++;

                if ($with_heatindex) {
                    $this->RegisterVariableFloat('Heatindex', $this->Translate('Heatindex'), 'Netatmo.Heatindex', $vpos);
                } else {
                    $this->UnregisterVariable('Heatindex');
                }
                $vpos++;

                if ($with_last_measure) {
                    $this->RegisterVariableInteger('LastMeasure', $this->Translate('last measurement'), '~UnixTimestamp', $vpos);
                } else {
                    $this->UnregisterVariable('LastMeasure');
                }
                $vpos++;

                if ($with_signal) {
                    $this->RegisterVariableInteger('RfSignal', $this->Translate('Signal-strength'), 'Netatmo.RfSignal', $vpos);
                } else {
                    $this->UnregisterVariable('RfSignal');
                }
                $vpos++;

                if ($with_battery) {
                    $this->RegisterVariableInteger('Battery', $this->Translate('Battery-Status'), 'Netatmo.Battery', $vpos);
                } else {
                    $this->UnregisterVariable('Battery');
                }
                $vpos++;

                break;
            case 'NAModule2':
                // Windmesser

                $this->RegisterVariableFloat('WindSpeed', $this->Translate('Windspeed'), 'Netatmo.WindSpeed', $vpos);
                $vpos++;

                if ($with_windstrength) {
                    $this->RegisterVariableInteger('WindStrength', $this->Translate('Windstrength'), 'Netatmo.WindStrength', $vpos);
                } else {
                    $this->UnregisterVariable('WindStrength');
                }
                $vpos++;

                if ($with_windangle) {
                    $this->RegisterVariableInteger('WindAngle', $this->Translate('Winddirection'), 'Netatmo.WindAngle', $vpos);
                } else {
                    $this->UnregisterVariable('WindAngle');
                }
                $vpos++;

                if ($with_winddirection) {
                    $this->RegisterVariableString('WindDirection', $this->Translate('Winddirection'), 'Netatmo.WindDirection', $vpos);
                } else {
                    $this->UnregisterVariable('WindDirection');
                }
                $vpos++;

                $this->RegisterVariableFloat('GustSpeed', $this->Translate('Speed of gusts of last 5m'), 'Netatmo.WindSpeed', $vpos);
                $vpos++;

                if ($with_windstrength) {
                    $this->RegisterVariableInteger('GustStrength', $this->Translate('Strenth of gusts'), 'Netatmo.WindStrength', $vpos);
                } else {
                    $this->UnregisterVariable('GustStrength');
                }
                $vpos++;

                if ($with_windangle) {
                    $this->RegisterVariableInteger('GustAngle', $this->Translate('Direction of gusts of last 5m'), 'Netatmo.WindAngle', $vpos);
                } else {
                    $this->UnregisterVariable('GustAngle');
                }
                $vpos++;

                if ($with_winddirection) {
                    $this->RegisterVariableString('GustDirection', $this->Translate('Direction of gusts of last 5m'), 'Netatmo.WindDirection', $vpos);
                } else {
                    $this->UnregisterVariable('GustDirection');
                }
                $vpos++;

                if ($with_last_measure) {
                    $this->RegisterVariableInteger('LastMeasure', $this->Translate('last measurement'), '~UnixTimestamp', $vpos);
                } else {
                    $this->UnregisterVariable('LastMeasure');
                }
                $vpos++;

                if ($with_signal) {
                    $this->RegisterVariableInteger('RfSignal', $this->Translate('Signal-strength'), 'Netatmo.RfSignal', $vpos);
                } else {
                    $this->UnregisterVariable('RfSignal');
                }
                $vpos++;

                if ($with_battery) {
                    $this->RegisterVariableInteger('Battery', $this->Translate('Battery-Status'), 'Netatmo.Battery', $vpos);
                } else {
                    $this->UnregisterVariable('Battery');
                }
                $vpos++;

                break;
            case 'NAModule3':
                // Regenmesser

                $this->RegisterVariableFloat('Rain', $this->Translate('Rainfall'), 'Netatmo.Rainfall', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Rain_1h', $this->Translate('Rainfall of last 1h'), 'Netatmo.Rainfall', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Rain_24h', $this->Translate('Rainfall of last 24h'), 'Netatmo.Rainfall', $vpos);
                $vpos++;

                if ($with_last_measure) {
                    $this->RegisterVariableInteger('LastMeasure', $this->Translate('last measurement'), '~UnixTimestamp', $vpos);
                } else {
                    $this->UnregisterVariable('LastMeasure');
                }
                $vpos++;

                if ($with_signal) {
                    $this->RegisterVariableInteger('RfSignal', $this->Translate('Signal-strength'), 'Netatmo.RfSignal', $vpos);
                } else {
                    $this->UnregisterVariable('RfSignal');
                }
                $vpos++;

                if ($with_battery) {
                    $this->RegisterVariableInteger('Battery', $this->Translate('Battery-Status'), 'Netatmo.Battery', $vpos);
                } else {
                    $this->UnregisterVariable('Battery');
                }
                $vpos++;

                break;
            case 'NAModule4':
                // Innenmodul

                $this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), 'Netatmo.Temperatur', $vpos);
                $vpos++;

                $this->RegisterVariableInteger('CO2', $this->Translate('CO2'), 'Netatmo.CO2', $vpos);
                $vpos++;

                $this->RegisterVariableFloat('Humidity', $this->Translate('Humidity'), 'Netatmo.Humidity', $vpos);
                $vpos++;

                if ($with_absolute_humidity) {
                    $this->RegisterVariableFloat('AbsoluteHumidity', $this->Translate('absolute humidity'), 'Netatmo.absHumidity', $vpos);
                } else {
                    $this->UnregisterVariable('AbsoluteHumidity');
                }
                $vpos++;

                if ($with_dewpoint) {
                    $this->RegisterVariableFloat('Dewpoint', $this->Translate('Dewpoint'), 'Netatmo.Dewpoint', $vpos);
                } else {
                    $this->UnregisterVariable('Dewpoint');
                }
                $vpos++;

                if ($with_heatindex) {
                    $this->RegisterVariableFloat('Heatindex', $this->Translate('Heatindex'), 'Netatmo.Heatindex', $vpos);
                } else {
                    $this->UnregisterVariable('Heatindex');
                }
                $vpos++;

                if ($with_last_measure) {
                    $this->RegisterVariableInteger('LastMeasure', $this->Translate('last measurement'), '~UnixTimestamp', $vpos);
                } else {
                    $this->UnregisterVariable('LastMeasure');
                }
                $vpos++;

                if ($with_signal) {
                    $this->RegisterVariableInteger('RfSignal', $this->Translate('Signal-strength'), 'Netatmo.RfSignal', $vpos);
                } else {
                    $this->UnregisterVariable('RfSignal');
                }
                $vpos++;

                if ($with_battery) {
                    $this->RegisterVariableInteger('Battery', $this->Translate('Battery-Status'), 'Netatmo.Battery', $vpos);
                } else {
                    $this->UnregisterVariable('Battery');
                }
                $vpos++;

                break;
            default:
                $this->SendDebug($this->scriptName, "unknown module_type '$module_type'", 0);
                break;
        }

        if ($module_type == 'NAMain') {
            // Inspired by module SymconTest/HookServe
            $this->RegisterHook('/hook/NetatmoWeatherDevice');
        }

        $this->SetStatus(102);
    }

    public function GetConfigurationForm()
    {
        $module_type = $this->ReadPropertyString('module_type');

        $formElements = [];

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station - Module: base module'];
                break;
            case 'NAModule1':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station - Module: outdoor module'];
                break;
            case 'NAModule2':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station - Module: wind gauge'];
                break;
            case 'NAModule3':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station - Module: rain gauge'];
                break;
            case 'NAModule4':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station - Module: indoor module'];
                break;
        }

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'optional weather data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_absolute_pressure', 'caption' => ' ... absolute Pressure'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_absolute_humidity', 'caption' => ' ... absolute Humidity'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_dewpoint', 'caption' => ' ... Dewpoint'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_heatindex', 'caption' => ' ... Heatindex'];
                break;
            case 'NAModule1':
                $formElements[] = ['type' => 'Label', 'label' => 'optional weather data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_absolute_humidity', 'caption' => ' ... absolute Humidity'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_dewpoint', 'caption' => ' ... Dewpoint'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_windchill', 'caption' => ' ... Windchill'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_heatindex', 'caption' => ' ... Heatindex'];
                break;
            case 'NAModule2':
                $formElements[] = ['type' => 'Label', 'label' => 'optional weather data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_windstrength', 'caption' => ' ... Windstrength'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_windangle', 'caption' => ' ... Winddirection with degree'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_winddirection', 'caption' => ' ... Winddirection with label'];
                break;
            case 'NAModule3':
                break;
            case 'NAModule4':
                $formElements[] = ['type' => 'Label', 'label' => 'optional weather data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_absolute_humidity', 'caption' => ' ... absolute Humidity'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_dewpoint', 'caption' => ' ... Dewpoint'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_heatindex', 'caption' => ' ... Heatindex'];
                break;
        }

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'optional global data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_contact', 'caption' => ' ... last transmission to Netatmo'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_status_box', 'caption' => ' ... html-box with state of station and modules'];
                break;
            default:
                break;
        }

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'optional data per module'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_measure', 'caption' => ' ... Measurement-Timestamp'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_signal', 'caption' => ' ... Wifi-Signal'];
                break;
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $formElements[] = ['type' => 'Label', 'label' => 'optional data per module'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_measure', 'caption' => ' ... Measurement-Timestamp'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_signal', 'caption' => ' ... RF-Signal'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_battery', 'caption' => ' ... Battery (a global battery indicator is always present)'];
                break;
        }

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'Konfiguration to update Wunderground (only if filled)'];
                $formElements[] = ['type' => 'Label', 'label' => 'Wunderground Access-Details from https://www.wunderground.com/personal-weather-station/mypws'];
                $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'Wunderground_ID', 'caption' => 'Station ID'];
                $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'Wunderground_Key', 'caption' => 'Station Key'];
                break;
            default:
                break;
        }

        $formStatus = [];
        $formStatus[] = ['code' => '101', 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => '102', 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => '103', 'icon' => 'inactive', 'caption' => 'Instance is inactive'];

        $formStatus[] = ['code' => '201', 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => '202', 'icon' => 'error', 'caption' => 'Instance is inactive (station missing)'];

        return json_encode(['elements' => $formElements, 'status' => $formStatus]);
    }

    protected function SetValue($Ident, $Value)
    {
        if (IPS_GetKernelVersion() >= 5) {
            parent::SetValue($Ident, $Value);
        } else {
            SetValue($this->GetIDForIdent($Ident), $Value);
        }
    }

    private function update_Wunderground($netatmo, $device)
    {
        $wunderground_url = 'https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php';
        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        if ($wunderground_id == '' || $wunderground_key == '') {
            return;
        }

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
                    $temp = $module['dashboard_data']['Temperature'];
                    $humidity = $module['dashboard_data']['Humidity'];
                    break;
                case 'NAModule2':
                    $winddir = $module['dashboard_data']['WindAngle'];
                    $windspeed = $module['dashboard_data']['WindStrength'];
                    $windgustdir = $module['dashboard_data']['GustAngle'];
                    $windgust = $module['dashboard_data']['GustStrength'];
                    break;
                case 'NAModule3':
                    $rain = $module['dashboard_data']['Rain'];
                    $sum_rain_1 = $module['dashboard_data']['sum_rain_1'];
                    $sum_rain_24 = $module['dashboard_data']['sum_rain_24'];
                    break;
                case 'NAModule4':
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
            $dewpoint = $this->CalcDewpoint($temp, $humidity);
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
        $this->SendDebug($this->scriptName, $msg, 0);

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
            $this->SendDebug($this->scriptName, $err, 0);
            $this->SetValue('Wunderground', fail);
            return -1;
        }

        $this->SetValue('Wunderground', true);
    }

    private function eval_NAMain($netatmo, $device)
    {
        $module_type = $this->ReadPropertyString('module_type');
        $module_id = $this->ReadPropertyString('module_id');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $minutes2fail = $this->ReadPropertyInteger('minutes2fail');

        $now = time();

        $statuscode = 102;
        $battery_alarm = false;
        $module_alarm = false;
        $module_data = '';

        $station_status = true;

        $module_name = $device['module_name'];

        $Temperature = $device['dashboard_data']['Temperature'];					// °C
        $CO2 = $device['dashboard_data']['CO2'];									// ppm
        $Humidity = $device['dashboard_data']['Humidity'];							// %
        $Noise = $device['dashboard_data']['Noise'];								// dB
        $Pressure = $device['dashboard_data']['Pressure'];							// mbar
        $AbsolutePressure = $device['dashboard_data']['AbsolutePressure'];			// mbar

        $time_utc = $device['dashboard_data']['time_utc'];
        $s = $this->seconds2duration($now - $time_utc);
        $last_measure = $s != '' ? "vor $s" : '';

        // letzte Kommunikation der Station mit Netatmo
        $last_status_store = $device['last_status_store'];
        if (is_int($last_status_store)) {
            $sec = $now - $last_status_store;
            $s = $this->seconds2duration($sec);
            $last_contact = $s != '' ? "vor $s" : '';
            $min = floor($sec / 60);
            if ($min > $minutes2fail) {
                $station_status = false;
            }
        } else {
            $last_contact = $last_status_store;
        }

        $wifi_status = $this->map_wifi_status($device['wifi_status']);

        $module_data[] = [
                'module_type'  => $module_type,
                'module_name'  => $module_name,
                'time_utc'     => $time_utc,
                'last_measure' => $last_measure,
                'wifi_status'  => $wifi_status,
            ];

        $msg = "base-module \"$module_name\": Temperature=$Temperature, CO2=$CO2, Humidity=$Humidity, Noise=$Noise, Pressure=$Pressure, AbsolutePressure=$AbsolutePressure";
        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
        $module_type_text = $this->module_type2text($module_type);
        $msg = "module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure, wifi_status=$wifi_status, last_contact=$last_contact";
        $this->SendDebug($this->scriptName, utf8_decode($msg), 0);

        if ($with_last_contact) {
            $this->SetValue('LastContact', $last_contact);
        }

        $this->SetValue('Temperature', $Temperature);
        $this->SetValue('CO2', $CO2);
        $this->SetValue('Humidity', $Humidity);
        $this->SetValue('Noise', $Noise);
        $this->SetValue('Pressure', $Pressure);
        if ($with_absolute_pressure) {
            $this->SetValue('AbsolutePressure', $AbsolutePressure);
        }
        if ($with_absolute_humidity) {
            $abs_humidity = $this->CalcAbsoluteHumidity($Temperature, $Humidity);
            $this->SetValue('AbsoluteHumidity', $abs_humidity);
        }
        if ($with_dewpoint) {
            $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
            $this->SetValue('Dewpoint', $dewpoint);
        }
        if ($with_heatindex) {
            $heatindex = $this->CalcHeatindex($Temperature, $Humidity);
            $this->SetValue('Heatindex', $heatindex);
        }
        if ($with_last_measure) {
            $this->SetValue('LastMeasure', $time_utc);
        }
        if ($with_signal) {
            $this->SetValue('Wifi', $wifi_status);
        }

        $modules = $netatmo['body']['modules'];
        foreach (['NAModule4', 'NAModule1', 'NAModule3', 'NAModule2'] as $types) {
            foreach ($modules as $module) {
                if ($module['type'] != $types) {
                    continue;
                }
                $module_name = $module['module_name'];

                $time_utc = $module['dashboard_data']['time_utc'];
                $s = $this->seconds2duration($now - $time_utc);
                $last_measure = $s != '' ? "vor $s" : '';

                $last_message = $module['last_message'];
                if (is_int($last_message)) {
                    $sec = $now - $last_message;
                    $min = floor($sec / 60);
                    if ($min > $minutes2fail) {
                        $module_alarm = true;
                    }
                }

                $rf_status = $this->map_rf_status($module['rf_status']);
                $battery_status = $this->map_battery_status($module['type'], $module['battery_vp']);

                $module_data[] = [
                        'module_type'     => $module['type'],
                        'module_name'     => $module_name,
                        'time_utc'        => $time_utc,
                        'last_measure'    => $last_measure,
                        'rf_status'       => $rf_status,
                        'battery_status'  => $battery_status,
                    ];
            }
        }

        $this->SetValue('Status', $station_status);

        $this->SetValue('BatteryAlarm', $battery_alarm);
        $this->SetValue('ModuleAlarm', $module_alarm);

        $station_data = [
                'now'          => $now,
                'status'       => $netatmo['status'],
                'last_contact' => $last_contact,
                'station_name' => $device['station_name'],
                'modules'      => $module_data,
            ];

        $this->SetBuffer('Data', json_encode($station_data));

        if ($with_status_box) {
            $html = $this->Build_StatusBox($station_data);
            $this->SetValue('StatusBox', $html);
        }

        return $statuscode;
    }

    private function eval_NAModule($netatmo, $device)
    {
        $module_type = $this->ReadPropertyString('module_type');
        $module_id = $this->ReadPropertyString('module_id');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_windangle = $this->ReadPropertyBoolean('with_windangle');
        $with_winddirection = $this->ReadPropertyBoolean('with_winddirection');
        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $now = time();

        $statuscode = 102;

        $module_found = false;
        $modules = $netatmo['body']['modules'];
        foreach ($modules as $module) {
            $id = $module['_id'];
            if ($module_id != $module['_id']) {
                continue;
            }

            $module_found = true;

            $module_name = $module['module_name'];

            $time_utc = $module['dashboard_data']['time_utc'];

            $s = $this->seconds2duration($now - $time_utc);
            $last_measure = $s != '' ? "vor $s" : '';

            $last_message = $module['last_message'];

            $rf_status = $this->map_rf_status($module['rf_status']);
            $battery_status = $this->map_battery_status($module_type, $module['battery_vp']);

            $module_data[] = [
                    'module_type'     => $module_type,
                    'module_name'     => $module_name,
                    'time_utc'        => $time_utc,
                    'last_measure'    => $last_measure,
                    'rf_status'       => $rf_status,
                    'battery_status'  => $battery_status,
                ];

            switch ($module_type) {
                case 'NAModule1':
                    // Außenmodul
                    $Temperature = $module['dashboard_data']['Temperature'];		// °C
                    $Humidity = $module['dashboard_data']['Humidity'];				// %

                    $this->SetValue('Temperature', $Temperature);
                    $this->SetValue('Humidity', $Humidity);
                    if ($with_absolute_humidity) {
                        $abs_humidity = $this->CalcAbsoluteHumidity($Temperature, $Humidity);
                        $this->SetValue('AbsoluteHumidity', $abs_humidity);
                    }
                    if ($with_dewpoint) {
                        $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
                        $this->SetValue('Dewpoint', $dewpoint);
                    }
                    if ($with_heatindex) {
                        $heatindex = $this->CalcHeatindex($Temperature, $Humidity);
                        $this->SetValue('Heatindex', $heatindex);
                    }
                    if ($with_dewpoint) {
                        $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
                        $this->SetValue('Dewpoint', $dewpoint);
                    }
                    if ($with_last_measure) {
                        $this->SetValue('LastMeasure', $time_utc);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "outdoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity";
                    $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
                    break;
                case 'NAModule2':
                    // Windmesser
                    $WindSpeed = $module['dashboard_data']['WindStrength'];			// km/h
                    $WindAngle = $module['dashboard_data']['WindAngle'];			// angles
                    $GustSpeed = $module['dashboard_data']['GustStrength'];			// km/h
                    $GustAngle = $module['dashboard_data']['GustAngle'];			// angles

                    $this->SetValue('WindSpeed', $WindSpeed);
                    $this->SetValue('GustSpeed', $GustSpeed);
                    if ($with_windangle) {
                        $this->SetValue('WindAngle', $WindAngle);
                        $this->SetValue('GustAngle', $GustAngle);
                    }
                    if ($with_windstrength) {
                        $windstrength = $this->ConvertWindSpeed2Strength($WindSpeed);
                        $this->SetValue('WindStrength', $windstrength);
                        $guststrength = $this->ConvertWindSpeed2Strength($GustSpeed);
                        $this->SetValue('GustStrength', $guststrength);
                    }
                    if ($with_winddirection) {
                        $dir = $this->ConvertWindDirection2Text($WindAngle) . ' (' . $WindAngle . '°)';
                        $this->SetValue('WindDirection', $dir);
                        $dir = $this->ConvertWindDirection2Text($GustAngle) . ' (' . $GustAngle . '°)';
                        $this->SetValue('GustDirection', $dir);
                    }
                    if ($with_last_measure) {
                        $this->SetValue('LastMeasure', $time_utc);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "wind gauge \"$module_name\": WindSpeed=$WindSpeed, WindAngle=$WindAngle, GustSpeed=$GustSpeed, GustAngle=$GustAngle";
                    $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
                    break;
                case 'NAModule3':
                    // Regenmesser
                    $Rain = $module['dashboard_data']['Rain'];						// mm
                    $sum_rain_1 = $module['dashboard_data']['sum_rain_1'];			// mm
                    $sum_rain_24 = $module['dashboard_data']['sum_rain_24'];		// mm

                    $this->SetValue('Rain', $Rain);
                    $this->SetValue('Rain_1h', $sum_rain_1);
                    $this->SetValue('Rain_24h', $sum_rain_24);
                    if ($with_last_measure) {
                        $this->SetValue('LastMeasure', $time_utc);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "rain gauge \"$module_name\": Rain=$Rain, sum_rain_1=$sum_rain_1, sum_rain_24=$sum_rain_24";
                    $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
                    break;
                case 'NAModule4':
                    // Innenmodul
                    $Temperature = $module['dashboard_data']['Temperature'];		// °C
                    $Humidity = $module['dashboard_data']['Humidity'];				// %
                    $CO2 = $module['dashboard_data']['CO2'];						// ppm

                    SetValue($this->GetIDForIdent('Temperature'), $Temperature);
                    SetValue($this->GetIDForIdent('CO2'), $CO2);
                    SetValue($this->GetIDForIdent('Humidity'), $Humidity);
                    if ($with_absolute_humidity) {
                        $abs_humidity = $this->CalcAbsoluteHumidity($Temperature, $Humidity);
                        SetValue($this->GetIDForIdent('AbsoluteHumidity'), $abs_humidity);
                    }
                    if ($with_dewpoint) {
                        $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
                        $this->SetValue('Dewpoint', $dewpoint);
                    }
                    if ($with_heatindex) {
                        $heatindex = $this->CalcHeatindex($Temperature, $Humidity);
                        $this->SetValue('Heatindex', $heatindex);
                    }
                    if ($with_last_measure) {
                        SetValue($this->GetIDForIdent('LastMeasure'), $time_utc);
                    }
                    if ($with_signal) {
                        SetValue($this->GetIDForIdent('RfSignal'), $rf_status);
                    }
                    if ($with_battery) {
                        SetValue($this->GetIDForIdent('Battery'), $battery_status);
                    }

                    $msg = "indoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity, CO2=$CO2";
                    $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
                    break;
            }

            $module_type_text = $this->module_type2text($module_type);
            $msg = "  module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure, rf_status=$rf_status, battery_status=$battery_status";
            $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
        }

        if ($module_found == false) {
            $instName = IPS_GetName($this->InstanceID);
            $module_type_text = $this->module_type2text($module_type);
            $msg = "instance $this->InstanceID \"$instName\" ($module_type_text) module with id $module_id not found";
            echo "$msg";
            $this->SendDebug($this->scriptName, utf8_decode($msg), 0);
        }

        if ($module_type == 'NAModule1') {
            // Außenmodul

            if ($with_windchill) {
                $temp = '';
                $windspeed = '';
                $modules = $netatmo['body']['modules'];
                foreach ($modules as $i => $value) {
                    $module = $modules[$i];
                    switch ($module['type']) {
                        case 'NAModule1':
                            $temp = $module['dashboard_data']['Temperature'];
                            break;
                        case 'NAModule2':
                            $type = 'wind';
                            $windspeed = $module['dashboard_data']['WindStrength'];
                            break;
                        default:
                            break;
                    }
                }

                if ($temp != '' && $windspeed != '') {
                    $windchill = $this->CalcWindchill($temp, $windspeed);
                    $this->SetValue('Windchill', $windchill);
                }
            }
        }

        return $statuscode;
    }

    public function ReceiveData($data)
    {
        $jdata = json_decode($data);
        $this->SendDebug($this->scriptName, 'data=' . print_r($jdata, true), 0);
        $buf = $jdata->Buffer;

        $wunderground_url = 'https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php';
        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        $station_id = $this->ReadPropertyString('station_id');

        $module_type = $this->ReadPropertyString('module_type');
        $module_id = $this->ReadPropertyString('module_id');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_winddirection = $this->ReadPropertyBoolean('with_winddirection');
        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $battery_alarm = false;
        $module_alarm = false;
        $module_data = '';

        $err = '';
        $statuscode = 0;
        $do_abort = false;

        if ($buf != '') {
            $netatmo = json_decode($buf, true);

            $station_found = false;
            $id = $module_type == 'NAMain' ? $module_id : $station_id;
            $devices = $netatmo['body']['devices'];
            foreach ($devices as $device) {
                $_id = $device['_id'];
                if ($id == $_id) {
                    $station_found = true;
                    break;
                }
            }
            if ($station_found == false) {
                $err = "station_id \"$id\" not found";
                $statuscode = 202;
                $do_abort = true;
            }
        } else {
            $err = 'no data';
            $statuscode = 201;
            $do_abort = true;
        }

        if ($do_abort) {
            echo "statuscode=$statuscode, err=$err";
            $this->SendDebug($this->scriptName, $err, 0);
            $this->SetStatus($statuscode);

            if ($module_type == 'NAMain') {
                $this->SetValue('Status', false);
                $this->SetValue('BatteryAlarm', true);
                $this->SetValue('ModuleAlarm', true);
                $this->SetValue('Wunderground', false);
            }
            return -1;
        }

        $now = time();

        switch ($module_type) {
             case 'NAMain':
                $statuscode = $this->eval_NAMain($netatmo, $device);
                break;
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $statuscode = $this->eval_NAModule($netatmo, $device);
                break;
            default:
                echo 'unknown module_type ' . $module_type;
                $this->SendDebug($this->scriptName, 'unknown module_type ' . $module_type, 0);
                $statuscode = 102;
                break;
        }

        $this->SetStatus($statuscode);

        if ($module_type == 'NAMain') {
            $this->update_Wunderground($netatmo, $device);
        }
    }

    // Variablenprofile erstellen
    private function CreateVarProfile($Name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon, $Asscociations = '')
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $ProfileType);
            IPS_SetVariableProfileText($Name, '', $Suffix);
            IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
            IPS_SetVariableProfileDigits($Name, $Digits);
            IPS_SetVariableProfileIcon($Name, $Icon);
            if ($Asscociations != '') {
                foreach ($Asscociations as $a) {
                    $w = isset($a['Wert']) ? $a['Wert'] : '';
                    $n = isset($a['Name']) ? $a['Name'] : '';
                    $i = isset($a['Icon']) ? $a['Icon'] : '';
                    $f = isset($a['Farbe']) ? $a['Farbe'] : 0;
                    IPS_SetVariableProfileAssociation($Name, $w, $n, $i, $f);
                }
            }
        }
    }

    // Inspired from module SymconTest/HookServe
    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
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

    private function do_HttpRequest($url, $postdata = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($postdata != '') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $cdata = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $statuscode = 0;
        $err = '';
        $data = '';
        if ($httpcode != 200) {
            if ($httpcode == 400 || $httpcode == 401) {
                $statuscode = 201;
                $err = "got http-code $httpcode (unauthorized) from netatmo";
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = 202;
                $err = "got http-code $httpcode (server error) from netatmo";
            } else {
                $statuscode = 203;
                $err = "got http-code $httpcode from netatmo";
            }
        } elseif ($cdata == '') {
            $statuscode = 204;
            $err = 'no data from netatmo';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = 204;
                $err = 'malformed response from netatmo';
            } else {
                $data = $cdata;
            }
        }

        if ($statuscode) {
            echo "statuscode=$statuscode, err=$err";
            $this->SendDebug($this->scriptName, $err, 0);
            $this->SetStatus($statuscode);
        }

        return $data;
    }

    private function Build_StatusBox($station_data)
    {
        $img_path = '/hook/NetatmoWeatherDevice/imgs/';

        $html = '';

        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1; }\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "#spalte_caption { width: 200px; }\n";
        $html .= "#spalte_type { width: 25px; }\n";
        $html .= "#spalte_signal { width: 50px; }\n";
        $html .= "#spalte_battery { width: 50px; }\n";
        $html .= "</style>\n";

        $dt = date('d.m.Y H:i:s', $station_data['now']);
        $status = $station_data['status'];
        $station_name = $station_data['station_name'];
        $last_contact = $station_data['last_contact'];

        $html .= "<table>\n";
        $html .= "<colgroup><col id=\"spalte_caption\"></colgroup>\n";
        $html .= "<tdata>\n";

        $html .= "<tr>\n";
        $html .= "<td>Stationsname:</td>\n";
        $html .= "<th>$station_name</th>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "<td>Status:</td>\n";
        $html .= "<th>$status</th>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "<td>&nbsp;&nbsp;... aktualisiert:</td>\n";
        $html .= "<th>$dt</th>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "<td>letzte Kommunikation:</td>\n";
        $html .= "<th>$last_contact</th>\n";
        $html .= "</tr>\n";

        $html .= "</tdata>\n";
        $html .= "</table>\n";
        $html .= "<table>\n";

        $html .= "<br>\n";

        $html .= "<colgroup><col id=\"spalte_type\"></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col id=\"spalte_signal\"></colgroup>\n";
        $html .= "<colgroup><col id=\"spalte_battry\"></colgroup>\n";

        $html .= "<tdata>\n";

        $html .= "<tr>\n";
        $html .= "<th></th>\n";
        $html .= "<th>Modultyp</th>\n";
        $html .= "<th>Name</th>\n";
        $html .= "<th>letzte Meldung</th>\n";
        $html .= "<th style='padding: 0; text-align: left'>Signal</th>\n";
        $html .= "<th style='padding: 0; text-align: left'>Batterie</th>\n";
        $html .= "</tr>\n";

        $modules = $station_data['modules'];
        foreach ($modules as $module) {
            $module_type = $module['module_type'];
            $module_type_text = $this->module_type2text($module_type);
            $module_type_img = $img_path . $this->module_type2img($module_type);
            $module_name = $module['module_name'];
            $module_type = $module['module_type'];
            $last_measure = $module['last_measure'];

            $html .= "<tr>\n";
            $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type_text'</td>\n";
            $html .= "<td>$module_type_text</td>\n";
            $html .= "<td>$module_name</td>\n";
            $html .= "<td>$last_measure</td>\n";

            if ($module_type == 'NAMain') {
                $wifi_status = $module['wifi_status'];
                $wifi_status_text = $this->wifi_status2text($wifi_status);
                $wifi_status_img = $img_path . $this->wifi_status2img($wifi_status);
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

        return $html;
    }

    private function ProcessHook_Status()
    {
        $s = $this->GetBuffer('Data');
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

        $this->SendDebug($this->scriptName, 'station_data=$' . print_r($station_data, true), 0);

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

            $img_path = '/hook/NetatmoWeatherDevice/imgs/';

            $modules = $station_data['modules'];
            foreach ($modules as $module) {
                $module_type = $module['module_type'];
                $module_type_text = $this->module_type2text($module_type);
                $module_type_img = $img_path . $this->module_type2img($module_type);
                $module_name = $module['module_name'];
                $last_measure = $module['last_measure'];

                $html .= "<tr>\n";
                $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type_text'</td>\n";
                $html .= "<td>$module_name</td>\n";
                $html .= "<td>$last_measure</td>\n";

                if ($module_type == 'NAMain') {
                    $wifi_status = $module['wifi_status'];
                    $wifi_status_text = $this->wifi_status2text($wifi_status);
                    $wifi_status_img = $img_path . $this->wifi_status2img($wifi_status);
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
        $basename = substr($uri, strlen('/hook/NetatmoWeatherDevice/'));
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

    // Modul-Typ
    private function module_type2text($val)
    {
        $val2txt = [
            'NAMain'     => 'Basismodul',
            'NAModule1'  => 'Außenmodul',
            'NAModule2'  => 'Windmesser',
            'NAModule3'  => 'Regenmesser',
            'NAModule4'  => 'Innenmodul',
        ];

        if ($val >= 0 && $val < count($val2txt)) {
            $txt = $val2txt[$val];
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Modul-Typ
    private function module_type2img($val)
    {
        $val2img = [
            'NAMain'     => 'module_int.png',
            'NAModule1'  => 'module_ext.png',
            'NAModule2'  => 'module_wind.png',
            'NAModule3'  => 'module_rain.png',
            'NAModule4'  => 'module_ext.png',
        ];

        if ($val >= 0 && $val < count($val2img)) {
            $img = $val2img[$val];
        } else {
            $img = '';
        }
        return $img;
    }

    // Wifi-Status
    private function map_wifi_status($status)
    {
        if ($status <= 56) {
            // "high"
            $val = 3;
        } elseif ($status <= 71) {
            // "good"
            $val = 2;
        } elseif ($status <= 86) {
            // "average"
            $val = 1;
        } else {
            // "bad"
            $val = 0;
        }

        return $val;
    }

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

    private function wifi_status2img($status)
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
    private function map_rf_status($status)
    {
        if ($status <= 60) {
            // "full"
            $val = 4;
        } elseif ($status <= 70) {
            // "high"
            $val = 3;
        } elseif ($status <= 80) {
            // "medium"
            $val = 2;
        } elseif ($status <= 90) {
            // "low"
            $val = 1;
        } else {
            // "verylow"
            $val = 0;
        }

        return $val;
    }

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
    private function map_battery_status($module_type, $battery_vp)
    {
        switch ($module_type) {
            case 'NAModule1':
                $vp_map = [4000, 4500, 5000, 5500, 6000];
                break;
            case 'NAModule2':
                $vp_map = [4360, 4770, 5180, 5590, 6000];
                break;
            case 'NAModule3':
                $vp_map = [4000, 4500, 5000, 5500, 6000];
                break;
            case 'NAModule4':
                $vp_map = [4560, 4920, 5280, 5640, 6000];
                break;
            default:
                return 0;
        }

        if ($battery_vp < $vp_map[0]) {
            // "very low"
            $val = 0;
        } elseif ($battery_vp < $vp_map[1]) {
            // "low"
            $val = 1;
        } elseif ($battery_vp < $vp_map[2]) {
            // "medium"
            $val = 2;
        } elseif ($battery_vp < $vp_map[3]) {
            // "high"
            $val = 3;
        } elseif ($battery_vp < $vp_map[4]) {
            // "full"
            $val = 4;
        } else {
            // "max"
            $val = 5;
        }

        return $val;
    }

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
    private function seconds2duration(int $sec)
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
    public function CalcDewpoint(float $temp, float $humidity)
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
    public function CalcAbsoluteHumidity(float $temp, float $humidity)
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

        // absolute Feuchte in g Wasserdampf pro m³ Luft
        $AF = pow(10, 5) * $mw / $R * $DD / $TK;
        $AF = round($AF * 10) / 10; // auf eine NK runden

        return $AF;
    }

    // gemessenen Luftdruck in absoluen Luftdruck (Meereshöhe) umrechnen
    //   Quelle: https://rechneronline.de/barometer/hoehe.php
    public function CalcAbsolutePressure(float $pressure, float $temp, int $altitude = null)
    {
        if (!isset($altitude) || $altitude === null) {
            $altitude = $this->ReadPropertyInteger('station_altitude');
        }

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
    public function ConvertWindDirection2Text(int $dir)
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

        $idx = floor((($dir + 11.25) % 360) / 22.5);
        if ($idx >= 0 && $idx < count($dir2txt)) {
            $txt = $dir2txt[$idx];
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Windgeschwindigkeit in Beaufort umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Beaufortskala
    public function ConvertWindSpeed2Strength(int $speed)
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
    public function ConvertWindStrength2Text(int $bft)
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
    public function CalcWindchill(float $temp, int $speed)
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
    public function CalcHeatindex(float $temp, float $hum)
    {
        if ($temp < 27 || $hum < 40) {
            return $temp;
        }
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
