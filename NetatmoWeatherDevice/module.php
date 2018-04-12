<?php

// Constants will be defined with IP-Symcon 5.0 and newer
if (!defined('IPS_KERNELMESSAGE')) {
    define('IPS_KERNELMESSAGE', 10100);
}
if (!defined('KR_READY')) {
    define('KR_READY', 10103);
}

if (!defined('IPS_BOOLEAN')) {
    define('IPS_BOOLEAN', 0);
}
if (!defined('IPS_INTEGER')) {
    define('IPS_INTEGER', 1);
}
if (!defined('IPS_FLOAT')) {
    define('IPS_FLOAT', 2);
}
if (!defined('IPS_STRING')) {
    define('IPS_STRING', 3);
}

class NetatmoWeatherDevice extends IPSModule
{
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

        $this->RegisterPropertyInteger('minutes2fail', 30);

        $this->RegisterPropertyInteger('statusbox_script', 0);
        $this->RegisterPropertyInteger('webhook_script', 0);

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

        $this->CreateVarProfile('Netatmo.Temperatur', IPS_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Netatmo.Humidity', IPS_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.absHumidity', IPS_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Dewpoint', IPS_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Netatmo.Heatindex', IPS_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Netatmo.Pressure', IPS_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge');
        $this->CreateVarProfile('Netatmo.WindSpeed', IPS_FLOAT, ' km/h', 0, 100, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindStrength', IPS_INTEGER, ' bft', 0, 13, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Netatmo.WindAngle', IPS_INTEGER, ' °', 0, 360, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Netatmo.WindDirection', IPS_STRING, '', 0, 0, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Netatmo.Rainfall', IPS_FLOAT, ' mm', 0, 60, 0, 1, 'Rainfall');

        $associations = [];
        $associations[] = ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x008040];
        $associations[] = ['Wert' => 40, 'Name' => '%d', 'Farbe' => 0xFFFF31];
        $associations[] = ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 95, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Netatmo.Noise', IPS_INTEGER, ' dB', 0, 130, 0, 1, 'Speaker', $associations);

        $associations = [];
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000];
        $associations[] = ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Netatmo.CO2', IPS_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => 'Nein', 'Farbe' => -1];
        $associations[] = ['Wert' => true, 'Name' => 'Ja', 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Netatmo.Alarm', IPS_BOOLEAN, '', 0, 0, 0, 1, 'Alert', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->wifi_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->wifi_status2text(1), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 2, 'Name' => $this->wifi_status2text(2), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 3, 'Name' => $this->wifi_status2text(3), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.Wifi', IPS_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->signal_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->signal_status2text(1), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 2, 'Name' => $this->signal_status2text(2), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 3, 'Name' => $this->signal_status2text(3), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 4, 'Name' => $this->signal_status2text(4), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.RfSignal', IPS_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->battery_status2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->battery_status2text(1), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 2, 'Name' => $this->battery_status2text(2), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 3, 'Name' => $this->battery_status2text(3), 'Farbe' => 0x32CD32];
        $associations[] = ['Wert' => 4, 'Name' => $this->battery_status2text(4), 'Farbe' => 0x228B22];
        $associations[] = ['Wert' => 5, 'Name' => $this->battery_status2text(5), 'Farbe' => 0x228B22];
        $this->CreateVarProfile('Netatmo.Battery', IPS_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations);

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');

        // Inspired by module SymconTest/HookServe
        // We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    // Inspired by module SymconTest/HookServe
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        $module_type = $this->ReadPropertyString('module_type');

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            if ($module_type == 'Station') {
                $this->RegisterHook('/hook/NetatmoWeather');
            }
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');

        $station_id = $this->ReadPropertyString('station_id');
        $module_id = $this->ReadPropertyString('module_id');
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
            case 'Station':
                // station-global vars
                $this->MaintainVariable('Status', $this->Translate('State'), IPS_BOOLEAN, '~Alert.Reversed', $vpos++, true);
                $this->MaintainVariable('LastContact', $this->Translate('last transmission'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
                $this->MaintainVariable('Wifi', $this->Translate('Strength of wifi-signal'), IPS_INTEGER, 'Netatmo.Wifi', $vpos++, $with_signal);
                $this->MaintainVariable('ModuleAlarm', $this->Translate('station or modules stopped don\'t communicate'), IPS_BOOLEAN, 'Netatmo.Alarm', $vpos++, true);
                $this->MaintainVariable('BatteryAlarm', $this->Translate('Battery of one or more modules ist low or empty'), IPS_BOOLEAN, 'Netatmo.Alarm', $vpos++, true);
                $this->MaintainVariable('StatusBox', $this->Translate('State of station and modules'), IPS_STRING, '~HTMLBox', $vpos++, $with_status_box);
                $with_wunderground = $wunderground_id != '' && $wunderground_key != '';
                $this->MaintainVariable('Wunderground', $this->Translate('State of upload to wunderground'), IPS_BOOLEAN, '~Alert.Reversed', $vpos++, $with_wunderground);
                break;
            case 'NAMain':
                // Basismodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), IPS_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('CO2', $this->Translate('CO2'), IPS_INTEGER, 'Netatmo.CO2', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), IPS_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), IPS_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), IPS_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), IPS_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('Noise', $this->Translate('Noise'), IPS_INTEGER, 'Netatmo.Noise', $vpos++, true);
                $this->MaintainVariable('Pressure', $this->Translate('Air pressure'), IPS_FLOAT, 'Netatmo.Pressure', $vpos++, true);
                $this->MaintainVariable('AbsolutePressure', $this->Translate('absolute pressure'), IPS_FLOAT, 'Netatmo.Pressure', $vpos++, $with_absolute_pressure);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                break;
            case 'NAModule1':
                // Außenmodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), IPS_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), IPS_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), IPS_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), IPS_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Windchill', $this->Translate('Windchill'), IPS_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_windchill);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), IPS_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), IPS_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), IPS_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule2':
                // Windmesser
                $this->MaintainVariable('WindSpeed', $this->Translate('Windspeed'), IPS_FLOAT, 'Netatmo.WindSpeed', $vpos++, true);
                $this->MaintainVariable('WindStrength', $this->Translate('Windstrength'), IPS_INTEGER, 'Netatmo.WindStrength', $vpos++, $with_windstrength);
                $this->MaintainVariable('WindAngle', $this->Translate('Winddirection'), IPS_INTEGER, 'Netatmo.WindAngle', $vpos++, $with_windangle);
                $this->MaintainVariable('WindDirection', $this->Translate('WindDirection'), IPS_STRING, 'Netatmo.WindDirection', $vpos++, $with_winddirection);
                $this->MaintainVariable('GustSpeed', $this->Translate('Speed of gusts of last 5m'), IPS_FLOAT, 'Netatmo.WindSpeed', $vpos++, true);
                $this->MaintainVariable('GustStrength', $this->Translate('Strenth of gusts'), IPS_INTEGER, 'Netatmo.WindStrength', $vpos++, $with_windstrength);
                $this->MaintainVariable('GustAngle', $this->Translate('Direction of gusts of last 5m'), IPS_INTEGER, 'Netatmo.WindAngle', $vpos++, $with_windangle);
                $this->MaintainVariable('GustDirection', $this->Translate('Direction of gusts of last 5m'), IPS_STRING, 'Netatmo.WindDirection', $vpos++, $with_winddirection);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), IPS_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), IPS_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule3':
                // Regenmesser
                $this->MaintainVariable('Rain', $this->Translate('Rainfall'), IPS_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('Rain_1h', $this->Translate('Rainfall of last 1h'), IPS_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('Rain_24h', $this->Translate('Rainfall of last 24h'), IPS_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), IPS_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), IPS_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule4':
                // Innenmodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), IPS_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('CO2', $this->Translate('CO2'), IPS_INTEGER, 'Netatmo.CO2', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), IPS_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), IPS_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), IPS_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), IPS_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), IPS_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), IPS_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), IPS_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            default:
                $this->SendDebug(__FUNCTION__, "unknown module_type '$module_type'", 0);
                break;
        }

        switch ($module_type) {
            case 'Station':
                $module_info = $this->module_type2text($module_type) . ' (' . $station_id . ')';
                break;
            case 'NAMain':
                $module_info = $this->module_type2text($module_type);
                break;
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $module_info = $this->module_type2text($module_type) . ' (' . $module_id . ')';
                break;
            default:
                $module_info = 'unsupported module ' . $module_type;
                break;
        }

        $this->SetSummary($module_info);

        if ($module_type == 'Station') {
            // Inspired by module SymconTest/HookServe
            // Only call this in READY state. On startup the WebHook instance might not be available yet
            if (IPS_GetKernelRunlevel() == KR_READY) {
                $this->RegisterHook('/hook/NetatmoWeather');
            }
        }

        $this->SetStatus(102);
    }

    public function GetConfigurationForm()
    {
        $module_type = $this->ReadPropertyString('module_type');

        $formElements = [];

        switch ($module_type) {
            case 'Station':
                $formElements[] = ['type' => 'Label', 'label' => 'Netatmo Weather-Station'];
                break;
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
            case 'Station':
                $formElements[] = ['type' => 'Label', 'label' => 'station data'];
                $formElements[] = ['type' => 'NumberSpinner', 'name' => 'station_altitude', 'caption' => 'Altitude'];
                $formElements[] = ['type' => 'NumberSpinner', 'digits' => 5, 'name' => 'station_longitude', 'caption' => 'Longitude'];
                $formElements[] = ['type' => 'NumberSpinner', 'digits' => 5, 'name' => 'station_latitude', 'caption' => 'Latitude'];

                $formElements[] = ['type' => 'Label', 'label' => 'optional station data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_contact', 'caption' => ' ... last transmission to Netatmo'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_status_box', 'caption' => ' ... html-box with state of station and modules'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_signal', 'caption' => ' ... Wifi-Signal'];

                $formElements[] = ['type' => 'Label', 'label' => 'alternate script to use for ...'];
                $formElements[] = ['type' => 'SelectScript', 'name' => 'statusbox_script', 'caption' => ' ... "StatusBox"'];
                $formElements[] = ['type' => 'SelectScript', 'name' => 'webhook_script', 'caption' => ' ... Webhook'];

                $formElements[] = ['type' => 'Label', 'label' => 'Duration until the connection to netatmo or between stations is marked disturbed'];
                $formElements[] = ['type' => 'IntervalBox', 'name' => 'minutes2fail', 'caption' => 'Minutes'];
                break;
            case 'NAMain':
                $formElements[] = ['type' => 'Label', 'label' => 'optional module data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_measure', 'caption' => ' ... Measurement-Timestamp'];
                break;
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $formElements[] = ['type' => 'Label', 'label' => 'optional module data'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_measure', 'caption' => ' ... Measurement-Timestamp'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_signal', 'caption' => ' ... RF-Signal'];
                $formElements[] = ['type' => 'CheckBox', 'name' => 'with_battery', 'caption' => ' ... Battery (a global battery indicator is always present)'];
                break;
        }

        switch ($module_type) {
            case 'Station':
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
        $formStatus[] = ['code' => '104', 'icon' => 'inactive', 'caption' => 'Instance is inactive'];

        $formStatus[] = ['code' => '201', 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => '202', 'icon' => 'error', 'caption' => 'Instance is inactive (station missing)'];

        return json_encode(['elements' => $formElements, 'status' => $formStatus]);
    }

    protected function SetValue($Ident, $Value)
    {
        @$varID = $this->GetIDForIdent($Ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $Ident, 0);
            return;
        }

        if (IPS_GetKernelVersion() >= 5) {
            $ret = parent::SetValue($Ident, $Value);
        } else {
            $ret = SetValue($varID, $Value);
        }
        if ($ret == false) {
            $this->SendDebug(__FUNCTION__, 'mismatch of value "' . $Value . '" to variable ' . $Ident, 0);
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
        $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);

        $url = $wunderground_url . '?ID=' . $wunderground_id . '&PASSWORD=' . $wunderground_key . '&action=updateraw' . $param;
        $this->SendDebug(__FUNCTION__, 'wunderground-url: ' . utf8_decode($url), 0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $wstatus = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $do_abort = false;

        if ($httpcode != 200) {
            $err = "got http-code $httpcode from wunderground";
            $this->SendDebug(__FUNCTION__, $err, 0);
            $do_abort = true;
        }

        $wstatus = trim($wstatus, "\n");
        if ($wstatus != 'success') {
            $err = 'got from wunderground: ' . $wstatus;
            $this->SendDebug(__FUNCTION__, $err, 0);
            $do_abort = true;
        }

        if ($do_abort) {
            $this->SetValue('Wunderground', false);
            return -1;
        }

        $this->SetValue('Wunderground', true);
    }

    private function eval_Station($netatmo, $device)
    {
        $module_id = $this->ReadPropertyString('module_id');

        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $minutes2fail = $this->ReadPropertyInteger('minutes2fail');

        $now = time();

        $statuscode = 102;
        $battery_alarm = false;
        $module_alarm = false;
        $module_data = [];

        $station_status = true;

        $station_name = $device['station_name'];
        $module_name = $device['module_name'];

        $last_measure = $device['dashboard_data']['time_utc'];

        // letzte Kommunikation der Station mit Netatmo
        $last_contact = $device['last_status_store'];
        if (is_int($last_contact)) {
            $sec = $now - $last_contact;
            $min = floor($sec / 60);
            if ($min > $minutes2fail) {
                $station_status = false;
            }
        } else {
            $last_contact = 0;
        }

        $wifi_status = $this->map_wifi_status($device['wifi_status']);

        $module_data[] = [
                'module_type'  => $device['type'],
                'module_name'  => $module_name,
                'last_measure' => $last_measure,
                'wifi_status'  => $wifi_status,
            ];

        $msg = "station \"$module_name\": station_name=$station_name, wifi_status=$wifi_status, last_contact=$last_contact";
        $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);

        if ($with_last_contact) {
            $this->SetValue('LastContact', $last_contact);
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

                $last_measure = $module['dashboard_data']['time_utc'];

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
                        'last_measure'    => $last_measure,
                        'last_message'    => $last_message,
                        'rf_status'       => $rf_status,
                        'battery_status'  => $battery_status,
                    ];
            }
        }

        $station_data = [
                'last_query'      => $now,
                'status'          => $netatmo['status'],
                'last_contact'    => $last_contact,
                'station_name'    => $device['station_name'],
                'modules'         => $module_data,
            ];

        $this->SetBuffer('Data', json_encode($station_data));

        $this->SetValue('Status', $station_status);
        $this->SetValue('ModuleAlarm', $module_alarm);
        $this->SetValue('BatteryAlarm', $battery_alarm);

        if ($with_status_box) {
            $statusbox_script = $this->ReadPropertyInteger('statusbox_script');
            if ($statusbox_script > 0) {
                $html = IPS_RunScriptWaitEx($statusbox_script, ['InstanceID' => $this->InstanceID]);
            } else {
                $html = $this->Build_StatusBox($station_data);
            }
            $this->SetValue('StatusBox', $html);
        }

        return $statuscode;
    }

    private function eval_NAMain($netatmo, $device)
    {
        $module_type = $this->ReadPropertyString('module_type');
        $module_id = $this->ReadPropertyString('module_id');

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');

        $now = time();

        $statuscode = 102;

        $station_name = $device['station_name'];
        $module_name = $device['module_name'];

        $Temperature = $device['dashboard_data']['Temperature'];					// °C
        $CO2 = $device['dashboard_data']['CO2'];									// ppm
        $Humidity = $device['dashboard_data']['Humidity'];							// %
        $Noise = $device['dashboard_data']['Noise'];								// dB
        $Pressure = $device['dashboard_data']['Pressure'];							// mbar
        $AbsolutePressure = $device['dashboard_data']['AbsolutePressure'];			// mbar

        $last_measure = $device['dashboard_data']['time_utc'];

        $msg = "base-module \"$module_name\": Temperature=$Temperature, CO2=$CO2, Humidity=$Humidity, Noise=$Noise, Pressure=$Pressure, AbsolutePressure=$AbsolutePressure";
        $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
        $module_type_text = $this->module_type2text($module_type);
        $msg = "module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure";
        $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);

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
            $this->SetValue('LastMeasure', $last_measure);
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
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $now = time();

        $statuscode = 102;

        $station_name = $device['station_name'];

        $module_found = false;
        $modules = $netatmo['body']['modules'];
        foreach ($modules as $module) {
            $id = $module['_id'];
            if ($module_id != $module['_id']) {
                continue;
            }

            $module_found = true;

            $module_name = $module['module_name'];

            $last_measure = $module['dashboard_data']['time_utc'];

            $last_message = $module['last_message'];

            $rf_status = $this->map_rf_status($module['rf_status']);
            $battery_status = $this->map_battery_status($module_type, $module['battery_vp']);

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
                        $this->SetValue('LastMeasure', $last_measure);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "outdoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity";
                    $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
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
                        $this->SetValue('LastMeasure', $last_measure);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "wind gauge \"$module_name\": WindSpeed=$WindSpeed, WindAngle=$WindAngle, GustSpeed=$GustSpeed, GustAngle=$GustAngle";
                    $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
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
                        $this->SetValue('LastMeasure', $last_measure);
                    }
                    if ($with_signal) {
                        $this->SetValue('RfSignal', $rf_status);
                    }
                    if ($with_battery) {
                        $this->SetValue('Battery', $battery_status);
                    }

                    $msg = "rain gauge \"$module_name\": Rain=$Rain, sum_rain_1=$sum_rain_1, sum_rain_24=$sum_rain_24";
                    $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
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
                        SetValue($this->GetIDForIdent('LastMeasure'), $last_measure);
                    }
                    if ($with_signal) {
                        SetValue($this->GetIDForIdent('RfSignal'), $rf_status);
                    }
                    if ($with_battery) {
                        SetValue($this->GetIDForIdent('Battery'), $battery_status);
                    }

                    $msg = "indoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity, CO2=$CO2";
                    $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
                    break;
            }

            $module_type_text = $this->module_type2text($module_type);
            $msg = "  module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure, rf_status=$rf_status, battery_status=$battery_status";
            $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
        }

        if ($module_found == false) {
            $instName = IPS_GetName($this->InstanceID);
            $module_type_text = $this->module_type2text($module_type);
            $msg = "instance $this->InstanceID \"$instName\" ($module_type_text) module with id $module_id not found";
            echo "$msg";
            $this->SendDebug(__FUNCTION__, utf8_decode($msg), 0);
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
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);
        $buf = $jdata->Buffer;

        $station_id = $this->ReadPropertyString('station_id');

        $module_type = $this->ReadPropertyString('module_type');
        $module_id = $this->ReadPropertyString('module_id');

        $err = '';
        $statuscode = 0;
        $do_abort = false;

        if ($buf != '') {
            $netatmo = json_decode($buf, true);

            $station_found = false;
            $devices = $netatmo['body']['devices'];
            foreach ($devices as $device) {
                $_id = $device['_id'];
                if ($station_id == $_id) {
                    $station_found = true;
                    break;
                }
            }
            if ($station_found == false) {
                $err = "station_id \"$station_id\" not found";
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
            $this->SendDebug(__FUNCTION__, $err, 0);
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
             case 'Station':
                $statuscode = $this->eval_Station($netatmo, $device);
                break;
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
                $this->SendDebug(__FUNCTION__, 'unknown module_type ' . $module_type, 0);
                $statuscode = 102;
                break;
        }

        $this->SetStatus($statuscode);

        if ($module_type == 'Station') {
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

    private function Build_StatusBox($station_data)
    {
        $img_path = '/hook/NetatmoWeather/imgs/';

        $now = time();

        $html = '';

        $html .= "<body>\n";
        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1; }\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "#spalte_caption { width: 200px; }\n";
        $html .= "#spalte_type { width: 25px; }\n";
        $html .= "#spalte_signal { width: 60px; }\n";
        $html .= "#spalte_battery { width: 50px; }\n";
        $html .= "</style>\n";

        $dt = date('d.m.Y H:i:s', $station_data['last_query']);
        $status = $station_data['status'];
        $station_name = $station_data['station_name'];

        $last_contact = $station_data['last_contact'];
        if ($last_contact > 0) {
            $s = $this->seconds2duration($now - $last_contact);
            $last_contact_pretty = $s != '' ? "vor $s" : '';
        } else {
            $last_contact_pretty = '';
        }

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
        $html .= "<th>$last_contact_pretty</th>\n";
        $html .= "</tr>\n";

        $html .= "</tdata>\n";
        $html .= "</table>\n";
        $html .= "<table>\n";

        $html .= "<br>\n";

        $html .= "<colgroup><col id=\"spalte_type\"></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
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
        $html .= "<th>letzte Messung</th>\n";
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
            $s = $this->seconds2duration($now - $last_measure);
            $last_measure_pretty = $s != '' ? "vor $s" : '';

            $html .= "<tr>\n";
            $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type_text'</td>\n";
            $html .= "<td>$module_type_text</td>\n";
            $html .= "<td>$module_name</td>\n";
            $html .= "<td>$last_measure_pretty</td>\n";

            if ($module_type == 'NAMain') {
                $html .= "<td>&nbsp;</td>\n";

                $wifi_status = $module['wifi_status'];
                $wifi_status_text = $this->wifi_status2text($wifi_status);
                $wifi_status_img = $img_path . $this->wifi_status2img($wifi_status);
                $html .= "<td><img src=$wifi_status_img width='30' height='20' title='$wifi_status_text'></td>\n";
                $html .= "<td>&nbsp;</td>\n";
            } else {
                $last_message = $module['last_message'];
                $s = $this->seconds2duration($now - $last_message);
                $last_message_pretty = $s != '' ? "vor $s" : '';
                $html .= "<td>$last_message_pretty</td>\n";

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
        $html .= "</body>\n";

        return $html;
    }

    private function ProcessHook_Status()
    {
        $s = $this->GetBuffer('Data');
        $station_data = json_decode($s, true);

        $now = time();

        $html = '';

        $html .= "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head>\n";
        $html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
        $html .= "<link href=\"https://fonts.googleapis.com/css?family=Open+Sans\" rel=\"stylesheet\">\n";
        $html .= "<title>Status von Netatmo</title>\n";
        $html .= "<style>\n";
        $html .= "html { height: 100%; color: #ffffff; background-color: #303030; overflow: hidden; }\n";
        $html .= "body { table-cell; text-align: left; vertical-align: top; height: 100%; }\n";
        $html .= "</style>\n";
        $html .= "</head>\n";
        $html .= "<body>\n";
        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; font-family: 'Open Sans', sans-serif; font-size: 14px; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1; }\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "#spalte_type { width: 25px; }\n";
        $html .= "#spalte_signal { width: 30px; }\n";
        $html .= "#spalte_battery { width: 30px; }\n";
        $html .= "</style>\n";

        $this->SendDebug(__FUNCTION__, 'station_data=' . print_r($station_data, true), 0);

        $dt = date('d.m. H:i', $station_data['last_query']);
        $status = $station_data['status'];

        $last_contact = $station_data['last_contact'];
        if ($last_contact > 0) {
            $s = $this->seconds2duration($now - $last_contact);
            $last_contact_pretty = $s != '' ? "vor $s" : '';
        } else {
            $last_contact_pretty = '';
        }

        $s = '<font size="-1">Stand:</font> ';
        $s .= $dt;
        $s .= '&emsp;';
        $s .= '<font size="-1">Status:</font> ';
        $s .= $status;
        $s .= ' <font size="-2">(' . $last_contact_pretty . ')</font>';
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
                $module_type_text = $this->module_type2text($module_type);
                $module_type_img = $img_path . $this->module_type2img($module_type);
                $module_name = $module['module_name'];

                $last_measure = $module['last_measure'];
                $s = $this->seconds2duration($now - $last_measure);
                $last_measure_pretty = $s != '' ? "vor $s" : '';

                $html .= "<tr>\n";
                $html .= "<td><img src=$module_type_img width='20' height='20' title='$module_type_text'</td>\n";
                $html .= "<td>$module_name</td>\n";
                $html .= "<td>$last_measure_pretty</td>\n";

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

    public function GetRawData()
    {
        $img_path = '/hook/NetatmoWeather/imgs/';

        $s = $this->GetBuffer('Data');
        if ($s) {
            $station_data = json_decode($s, true);
            $modules = $station_data['modules'];
            if (isset($station_data['modules'])) {
                $_modules = [];
                $modules = $station_data['modules'];
                foreach ($modules as $module) {
                    $module_type = $module['module_type'];
                    $module['module_type_txt'] = $this->module_type2text($module_type);
                    $module['module_type_img'] = $img_path . $this->module_type2img($module_type);
                    if ($module_type == 'NAMain') {
                        $wifi_status = $module['wifi_status'];
                        $module['wifi_status_txt'] = $this->wifi_status2text($wifi_status);
                        $module['wifi_status_img'] = $img_path . $this->wifi_status2img($wifi_status);
                    } else {
                        $rf_status = $module['rf_status'];
                        $module['rf_status_txt'] = $this->signal_status2text($rf_status);
                        $module['rf_status_img'] = $img_path . $this->signal_status2img($rf_status);
                        $battery_status = $module['battery_status'];
                        $module['battery_status_txt'] = $this->battery_status2text($battery_status);
                        $module['battery_status_img'] = $img_path . $this->battery_status2img($battery_status);
                    }
                    $_modules[] = $module;
                }
                $station_data['modules'] = $_modules;
            }
            $s = json_encode($station_data);
        }
        return $s;
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
            $webhook_script = $this->ReadPropertyInteger('webhook_script');
            if ($webhook_script > 0) {
                $html = IPS_RunScriptWaitEx($webhook_script, ['InstanceID' => $this->InstanceID]);
                echo $html;
            } else {
                $this->ProcessHook_Status();
            }
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
            'Station'    => 'station',
            'NAMain'     => 'base module',
            'NAModule1'  => 'outdoor module',
            'NAModule2'  => 'wind gauge',
            'NAModule3'  => 'rain gauge',
            'NAModule4'  => 'indoor module',
        ];

        if ($val >= 0 && $val < count($val2txt)) {
            $txt = $this->Translate($val2txt[$val]);
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
            $instIDs = IPS_GetInstanceListByModuleID('{1023DB4A-D491-A0D5-17CD-380D3578D0FA}');
            foreach ($instIDs as $id) {
                $cfg = IPS_GetConfiguration($id);
                $jcfg = json_decode($cfg, true);
                if (!isset($jcfg['module_type'])) {
                    continue;
                }
                echo 'module_type=' . $jcfg['module_type'] . "\n";
                if ($jcfg['module_type'] == 'Station') {
                    $altitude = $jcfg['station_altitude'];
                    break;
                }
            }
            echo 'altitude=' . $altitude . "\n";
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
