<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class NetatmoWeatherDevice extends IPSModule
{
    use NetatmoWeather\StubsCommonLib;
    use NetatmoWeatherLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

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

        $this->RegisterPropertyBoolean('with_minmax', false);
        $this->RegisterPropertyBoolean('with_trend', false);

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_status_box', false);

        $this->RegisterPropertyBoolean('with_last_measure', false);
        $this->RegisterPropertyBoolean('with_signal', false);
        $this->RegisterPropertyBoolean('with_battery', false);

        $this->RegisterPropertyString('hook', '/hook/NetatmoWeather');

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($tstamp, $senderID, $message, $data)
    {
        parent::MessageSink($tstamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $module_type = $this->ReadPropertyString('module_type');
            if ($module_type == 'Station') {
                $hook = $this->ReadPropertyString('hook');
                if ($hook != '') {
                    $this->RegisterHook($hook);
                }
            }
        }
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $station_id = $this->ReadPropertyString('station_id');
        if ($station_id == '') {
            $this->SendDebug(__FUNCTION__, '"station_id" is empty', 0);
            $r[] = $this->Translate('Station-ID must be specified');
        }

        $module_type = $this->ReadPropertyString('module_type');
        switch ($module_type) {
            case 'Station':
                break;
            case 'NAMain':
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $module_id = $this->ReadPropertyString('module_id');
                if ($module_id == '') {
                    $this->SendDebug(__FUNCTION__, '"module_id" is empty', 0);
                    $r[] = $this->Translate('Module-ID must be specified');
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, '"module_type" is unsupported', 0);
                $r[] = $this->Translate('unknown Module-Type') . ' "' . $module_type . '"';
                break;
        }

        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');
        if ($wunderground_id != '' && $wunderground_key == '') {
            $this->SendDebug(__FUNCTION__, '"Wunderground_ID" only with "Wunderground_Key"', 0);
            $r[] = $this->Translate('if given Wunderground Station ID, the Station Key is also needed');
        }

        return $r;
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $r = [];

        if ($this->version2num($oldInfo) < $this->version2num('1.36')) {
            $r[] = $this->Translate('Error in variableprofile \'Netatmo.absHumidity\'');
        }

        if ($this->version2num($oldInfo) < $this->version2num('1.39')) {
            $r[] = $this->Translate('Adjusting the value range of various variable profiles');
        }

        return $r;
    }

    private function CompleteModuleUpdate(array $oldInfo, array $newInfo)
    {
        if ($this->version2num($oldInfo) < $this->version2num('1.36')) {
            if (IPS_VariableProfileExists('Netatmo.absHumidity')) {
                IPS_DeleteVariableProfile('Netatmo.absHumidity');
            }
            $this->InstallVarProfiles(false);
        }

        if ($this->version2num($oldInfo) < $this->version2num('1.39')) {
            $vps = [
                'Netatmo.Temperatur',
                'Netatmo.Humidity',
                'Netatmo.absHumidity',
                'Netatmo.Dewpoint',
                'Netatmo.Heatindex',
                'Netatmo.WindSpeed',
            ];
            foreach ($vps as $vp) {
                if (IPS_VariableProfileExists($vp)) {
                    IPS_DeleteVariableProfile($vp);
                }
            }
            $this->InstallVarProfiles(false);
        }

        return '';
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $propertyNames = ['statusbox_script', 'webhook_script', 'ImportCategoryID'];
        $this->MaintainReferences($propertyNames);

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_windchill = $this->ReadPropertyBoolean('with_windchill');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_windstrength = $this->ReadPropertyBoolean('with_windstrength');
        $with_windangle = $this->ReadPropertyBoolean('with_windangle');
        $with_winddirection = $this->ReadPropertyBoolean('with_winddirection');
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');
        $with_trend = $this->ReadPropertyBoolean('with_trend');
        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_status_box = $this->ReadPropertyBoolean('with_status_box');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $wunderground_id = $this->ReadPropertyString('Wunderground_ID');
        $wunderground_key = $this->ReadPropertyString('Wunderground_Key');
        $with_wunderground = $wunderground_id != '' && $wunderground_key != '';

        $vpos = 1;

        $module_type = $this->ReadPropertyString('module_type');
        switch ($module_type) {
            case 'Station':
                // station-global vars
                $this->MaintainVariable('Status', $this->Translate('State'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, true);
                $this->MaintainVariable('LastContact', $this->Translate('last transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
                $this->MaintainVariable('Wifi', $this->Translate('Strength of wifi-signal'), VARIABLETYPE_INTEGER, 'Netatmo.Wifi', $vpos++, $with_signal);
                $this->MaintainVariable('ModuleAlarm', $this->Translate('station or modules don\'t communicate'), VARIABLETYPE_BOOLEAN, 'Netatmo.Alarm', $vpos++, true);
                $this->MaintainVariable('BatteryAlarm', $this->Translate('Battery of one or more modules ist low or empty'), VARIABLETYPE_BOOLEAN, 'Netatmo.Alarm', $vpos++, true);
                $this->MaintainVariable('StatusBox', $this->Translate('State of station and modules'), VARIABLETYPE_STRING, '~HTMLBox', $vpos++, $with_status_box);
                $this->MaintainVariable('Wunderground', $this->Translate('State of upload to wunderground'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, $with_wunderground);
                break;
            case 'NAMain':
                // Basismodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('CO2', $this->Translate('CO2'), VARIABLETYPE_INTEGER, 'Netatmo.CO2', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('Noise', $this->Translate('Noise'), VARIABLETYPE_INTEGER, 'Netatmo.Noise', $vpos++, true);
                $this->MaintainVariable('Pressure', $this->Translate('Air pressure'), VARIABLETYPE_FLOAT, 'Netatmo.Pressure', $vpos++, true);
                $this->MaintainVariable('AbsolutePressure', $this->Translate('absolute pressure'), VARIABLETYPE_FLOAT, 'Netatmo.Pressure', $vpos++, $with_absolute_pressure);
                $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureTrend', $this->Translate('Trend of temperature'), VARIABLETYPE_INTEGER, 'Netatmo.Trend', $vpos++, $with_trend);
                $this->MaintainVariable('PressureTrend', $this->Translate('Trend of air pressure'), VARIABLETYPE_INTEGER, 'Netatmo.Trend', $vpos++, $with_trend);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                break;
            case 'NAModule1':
                // Außenmodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Windchill', $this->Translate('Windchill'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_windchill);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureTrend', $this->Translate('Trend of temperature'), VARIABLETYPE_INTEGER, 'Netatmo.Trend', $vpos++, $with_trend);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), VARIABLETYPE_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), VARIABLETYPE_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule2':
                // Windmesser
                $this->MaintainVariable('WindSpeed', $this->Translate('Windspeed'), VARIABLETYPE_FLOAT, 'Netatmo.WindSpeed', $vpos++, true);
                $this->MaintainVariable('WindStrength', $this->Translate('Windstrength'), VARIABLETYPE_INTEGER, 'Netatmo.WindStrength', $vpos++, $with_windstrength);
                $this->MaintainVariable('WindAngle', $this->Translate('Winddirection'), VARIABLETYPE_INTEGER, 'Netatmo.WindAngle', $vpos++, $with_windangle);
                $this->MaintainVariable('WindDirection', $this->Translate('Winddirection'), VARIABLETYPE_STRING, 'Netatmo.WindDirection', $vpos++, $with_winddirection);
                $this->MaintainVariable('GustSpeed', $this->Translate('Speed of gusts of last 5m'), VARIABLETYPE_FLOAT, 'Netatmo.WindSpeed', $vpos++, true);
                $this->MaintainVariable('GustStrength', $this->Translate('Strength of gusts of last 5m'), VARIABLETYPE_INTEGER, 'Netatmo.WindStrength', $vpos++, $with_windstrength);
                $this->MaintainVariable('GustAngle', $this->Translate('Direction of gusts of last 5m'), VARIABLETYPE_INTEGER, 'Netatmo.WindAngle', $vpos++, $with_windangle);
                $this->MaintainVariable('GustDirection', $this->Translate('Direction of gusts of last 5m'), VARIABLETYPE_STRING, 'Netatmo.WindDirection', $vpos++, $with_winddirection);
                $this->MaintainVariable('GustMaxSpeed', $this->Translate('Speed of today\'s strongest gust'), VARIABLETYPE_FLOAT, 'Netatmo.WindSpeed', $vpos++, $with_minmax);
                $this->MaintainVariable('GustMaxStrength', $this->Translate('Strength of today\'s strongest gust'), VARIABLETYPE_INTEGER, 'Netatmo.WindStrength', $vpos++, $with_minmax && $with_windstrength);
                $this->MaintainVariable('GustMaxAngle', $this->Translate('Direction of today\'s strongest gust'), VARIABLETYPE_INTEGER, 'Netatmo.WindAngle', $vpos++, $with_minmax && $with_windangle);
                $this->MaintainVariable('GustMaxDirection', $this->Translate('Direction of today\'s strongest gust'), VARIABLETYPE_STRING, 'Netatmo.WindDirection', $vpos++, $with_minmax && $with_winddirection);
                $this->MaintainVariable('GustMaxTimestamp', $this->Translate('Time of today\'s strongest gust'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), VARIABLETYPE_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), VARIABLETYPE_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule3':
                // Regenmesser
                $this->MaintainVariable('Rain', $this->Translate('Rainfall'), VARIABLETYPE_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('Rain_1h', $this->Translate('Rainfall of last hour'), VARIABLETYPE_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('Rain_24h', $this->Translate('Rainfall of today'), VARIABLETYPE_FLOAT, 'Netatmo.Rainfall', $vpos++, true);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), VARIABLETYPE_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), VARIABLETYPE_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            case 'NAModule4':
                // Innenmodul
                $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, true);
                $this->MaintainVariable('CO2', $this->Translate('CO2'), VARIABLETYPE_INTEGER, 'Netatmo.CO2', $vpos++, true);
                $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'Netatmo.Humidity', $vpos++, true);
                $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'Netatmo.absHumidity', $vpos++, $with_absolute_humidity);
                $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
                $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);
                $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
                $this->MaintainVariable('TemperatureTrend', $this->Translate('Trend of temperature'), VARIABLETYPE_INTEGER, 'Netatmo.Trend', $vpos++, $with_trend);
                $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
                $this->MaintainVariable('RfSignal', $this->Translate('Signal-strength'), VARIABLETYPE_INTEGER, 'Netatmo.RfSignal', $vpos++, $with_signal);
                $this->MaintainVariable('Battery', $this->Translate('Battery-Status'), VARIABLETYPE_INTEGER, 'Netatmo.Battery', $vpos++, $with_battery);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown module_type "' . $module_type . '"', 0);
                break;
        }

        $station_id = $this->ReadPropertyString('station_id');
        $module_id = $this->ReadPropertyString('module_id');

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

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        if (IPS_GetKernelRunlevel() == KR_READY) {
            if ($module_type == 'Station') {
                $hook = $this->ReadPropertyString('hook');
                if ($hook != '') {
                    $this->RegisterHook($hook);
                }
            }
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function getConfiguratorValues4Station()
    {
        $entries = [];

        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return $entries;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            return $entries;
        }

        $catID = $this->ReadPropertyInteger('ImportCategoryID');

        $SendData = ['DataID' => '{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}', 'Function' => 'LastData'];
        $data = $this->SendDataToParent(json_encode($SendData));
        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $guid = '{1023DB4A-D491-A0D5-17CD-380D3578D0FA}';
        $instIDs = IPS_GetInstanceListByModuleID($guid);

        $station_id = $this->ReadPropertyString('station_id');

        if (is_array($jdata)) {
            $devices = $jdata['body']['devices'];
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if (is_array($devices)) {
                /*
                   Ausnahme zu Reviewrichtlinien (Feinheiten des Modul-Reviews), wurde per Mail vom 09.10.2020 von Niels genehmigt.
                   Grund: im Konfigurator (NetatmoWeatherConfig) legt man Basisstationen an. Hier legt man die zu der Basisstation
                   gehörenden und nur mit der Basisstation zu betreibenden Netatmo-Module an
                 */

                foreach ($devices as $device) {
                    $_id = $device['_id'];
                    if ($station_id != $_id) {
                        continue;
                    }
                    $station_name = $this->GetArrayElem($device, 'station_name', '');
                    $home_name = $this->GetArrayElem($device, 'home_name', '');
                    if ($station_name == '') {
                        $station_name = $home_name;
                    }

                    $module_type = 'NAMain';
                    $module_id = $device['_id'];
                    $module_desc = $this->Translate('Base module');

                    $instanceID = 0;
                    foreach ($instIDs as $instID) {
                        if (IPS_GetProperty($instID, 'station_id') != $station_id) {
                            continue;
                        }
                        if (IPS_GetProperty($instID, 'module_id') != $module_id) {
                            continue;
                        }
                        $instanceID = $instID;
                        break;
                    }

                    if ($instanceID && IPS_GetInstance($instanceID)['ConnectionID'] != IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                        continue;
                    }

                    $module_name = $this->GetArrayElem($device, 'module_name', '');
                    if ($module_name == '') {
                        $module_name = IPS_GetName($instanceID);
                    }
                    $module_info = $module_desc . ' (' . $station_name . '\\' . $module_name . ')';

                    $entry = [
                        'instanceID'   => $instanceID,
                        'name'         => $module_name,
                        'module_desc'  => $module_desc,
                        'module_id'    => $module_id,
                        'create'       => [
                            'moduleID'       => $guid,
                            'location'       => $this->GetConfiguratorLocation($catID),
                            'info'           => $module_info,
                            'configuration'  => [
                                'module_id'   => $module_id,
                                'module_type' => $module_type,
                                'station_id'  => $station_id,
                            ]
                        ]
                    ];
                    $entries[] = $entry;
                    $this->SendDebug(__FUNCTION__, 'entry=' . print_r($entry, true), 0);

                    $modules = $this->GetArrayElem($device, 'modules', '');
                    if ($modules != '') {
                        foreach (['NAModule4', 'NAModule1', 'NAModule3', 'NAModule2'] as $types) {
                            foreach ($modules as $module) {
                                if ($module['type'] != $types) {
                                    continue;
                                }
                                $module_type = $module['type'];
                                switch ($module_type) {
                                    case 'NAModule1':
                                        $module_id = $module['_id'];
                                        $module_desc = $this->Translate('Outdoor module');
                                        break;
                                    case 'NAModule2':
                                        $module_id = $module['_id'];
                                        $module_desc = $this->Translate('Wind gauge');
                                        break;
                                    case 'NAModule3':
                                        $module_id = $module['_id'];
                                        $module_desc = $this->Translate('Rain gauge');
                                        break;
                                    case 'NAModule4':
                                        $module_id = $module['_id'];
                                        $module_desc = $this->Translate('Indoor module');
                                        break;
                                    default:
                                        $module_id = '';
                                        echo 'unknown module_type ' . $module_type;
                                        $this->SendDebug(__FUNCTION__, 'unknown module_type ' . $module_type, 0);
                                        break;
                                }
                                if ($module_id == '') {
                                    continue;
                                }

                                $instanceID = 0;
                                foreach ($instIDs as $instID) {
                                    if (IPS_GetProperty($instID, 'station_id') != $station_id) {
                                        continue;
                                    }
                                    if (IPS_GetProperty($instID, 'module_id') != $module_id) {
                                        continue;
                                    }
                                    $instanceID = $instID;
                                    break;
                                }

                                if ($instanceID && IPS_GetInstance($instanceID)['ConnectionID'] != IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                                    continue;
                                }
                                $module_name = $this->GetArrayElem($module, 'module_name', '');
                                if ($module_name == '') {
                                    $module_name = IPS_GetName($instanceID);
                                }
                                $module_info = $module_desc . ' (' . $station_name . '\\' . $module_name . ')';

                                $entry = [
                                    'instanceID'   => $instanceID,
                                    'name'         => $module_name,
                                    'module_desc'  => $module_desc,
                                    'module_id'    => $module_id,
                                    'create'       => [
                                        'moduleID'       => $guid,
                                        'location'       => $this->GetConfiguratorLocation($catID),
                                        'info'           => $module_info,
                                        'configuration'  => [
                                            'module_id'   => $module_id,
                                            'module_type' => $module_type,
                                            'station_id'  => $station_id,
                                        ]
                                    ]
                                ];
                                $entries[] = $entry;
                                $this->SendDebug(__FUNCTION__, 'entry=' . print_r($entry, true), 0);
                            }
                        }
                    }
                }
            }
        }

        return $entries;
    }

    private function GetFormElements()
    {
        $title = 'Netatmo Weatherstation';

        $module_type = $this->ReadPropertyString('module_type');
        switch ($module_type) {
            case 'NAMain':
                $title .= ' - base module';
                break;
            case 'NAModule1':
                $title .= ' - outdoor module';
                break;
            case 'NAModule2':
                $title .= ' - wind gauge';
                break;
            case 'NAModule3':
                $title .= ' - rain gauge';
                break;
            case 'NAModule4':
                $title .= ' - indoor module';
                break;
        }

        $formElements = $this->GetCommonFormElements($title);

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        switch ($module_type) {
            case 'Station':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'ValidationTextBox',
                            'enabled' => false,
                            'name'    => 'station_id',
                            'caption' => 'Station-ID'
                        ],
                    ],
                    'caption' => 'Basic configuration (don\'t change)',
                ];
                break;
            default:
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'ValidationTextBox',
                            'enabled' => false,
                            'name'    => 'module_type',
                            'caption' => 'Module-Type'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'enabled' => false,
                            'name'    => 'module_id',
                            'caption' => 'Module-ID'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'enabled' => false,
                            'name'    => 'station_id',
                            'caption' => 'Station-ID'
                        ],
                    ],
                    'caption' => 'Basic configuration (don\'t change)',
                ];
                break;
        }

        switch ($module_type) {
            case 'NAMain':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_absolute_pressure',
                            'caption' => 'absolute Pressure'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_absolute_humidity',
                            'caption' => 'absolute Humidity'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_dewpoint',
                            'caption' => 'Dewpoint'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_heatindex',
                            'caption' => 'Heatindex'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_minmax',
                            'caption' => 'Min/Max of temperature'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_trend',
                            'caption' => 'Trend of temperature and pressure'
                        ],
                    ],
                    'caption' => 'optional weather data',
                ];
                break;
            case 'NAModule1':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_absolute_humidity',
                            'caption' => 'absolute Humidity'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_dewpoint',
                            'caption' => 'Dewpoint'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_windchill',
                            'caption' => 'Windchill'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_heatindex',
                            'caption' => 'Heatindex'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_minmax',
                            'caption' => 'Min/Max of temperature'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_trend',
                            'caption' => 'Trend of temperature'
                        ],
                    ],
                    'caption' => 'optional weather data'
                ];
                break;
            case 'NAModule2':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_windstrength',
                            'caption' => 'Windstrength'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_windangle',
                            'caption' => 'Winddirection in degrees'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_winddirection',
                            'caption' => 'Winddirection with label'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_minmax',
                            'caption' => 'Strongest gust of today'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'optional weather data'
                        ],
                    ],
                    'caption' => 'optional weather data',
                ];
                break;
            case 'NAModule3':
                break;
            case 'NAModule4':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_absolute_humidity',
                            'caption' => 'absolute Humidity'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_dewpoint',
                            'caption' => 'Dewpoint'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_heatindex',
                            'caption' => 'Heatindex'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_minmax',
                            'caption' => 'Min/Max of temperature'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_trend',
                            'caption' => 'Trend of temperature'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'optional weather data'
                        ],
                    ],
                    'caption' => 'optional weather data',
                ];
                break;
        }

        switch ($module_type) {
            case 'Station':
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'NumberSpinner',
                            'name'    => 'station_altitude',
                            'caption' => 'Altitude'
                        ],
                        [
                            'type'    => 'NumberSpinner',
                            'digits'  => 5,
                            'name'    => 'station_longitude',
                            'caption' => 'Longitude'
                        ],
                        [
                            'type'    => 'NumberSpinner',
                            'digits'  => 5,
                            'name'    => 'station_latitude',
                            'caption' => 'Latitude'
                        ],
                    ],
                    'caption' => 'station data'
                ];

                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_last_contact',
                            'caption' => 'last transmission to Netatmo'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_status_box',
                            'caption' => 'html-box with state of station and modules'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'with_signal',
                            'caption' => 'Wifi-Signal'
                        ],
                    ],
                    'caption' => 'optional station data'
                ];

                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'Label',
                            'caption' => 'alternate script to use for ...'
                        ],
                        [
                            'type'    => 'SelectScript',
                            'name'    => 'statusbox_script',
                            'caption' => ' ... "StatusBox"'
                        ],
                        [
                            'type'    => 'SelectScript',
                            'name'    => 'webhook_script',
                            'caption' => ' ... Webhook'
                        ],

                        [
                            'type'    => 'Label',
                            'caption' => 'Duration until the connection to netatmo or between stations is marked disturbed'
                        ],
                        [
                            'type'    => 'NumberSpinner',
                            'minimum' => 0,
                            'suffix'  => 'Minutes',
                            'name'    => 'minutes2fail',
                        ],
                    ],
                    'caption' => 'Processing information'
                ];

                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => [
                        [
                            'type'    => 'Label',
                            'caption' => 'Konfiguration to update Wunderground (only if filled)'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'Wunderground Access-Details from https://www.wunderground.com/personal-weather-station/mypws'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'Wunderground_ID',
                            'caption' => 'Station ID'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'Wunderground_Key',
                            'caption' => 'Station Key'
                        ],
                    ],
                    'caption' => 'Wunderground'
                ];

                $entries = $this->getConfiguratorValues4Station();
                if (count($entries) > 0) {
                    $formElements[] = [
                        'type'    => 'ExpansionPanel',
                        'items'   => [
                            [
                                'name'    => 'ImportCategoryID',
                                'type'    => 'SelectCategory',
                                'caption' => 'category for modules to be created'
                            ],
                            [
                                'type'    => 'Configurator',
                                'name'    => 'Modules',
                                'caption' => 'available modules',

                                'rowCount' => count($entries),

                                'add'     => false,
                                'delete'  => false,
                                'columns' => [
                                    [
                                        'caption' => 'Name',
                                        'name'    => 'name',
                                        'width'   => 'auto'
                                    ],
                                    [
                                        'caption' => 'Type',
                                        'name'    => 'module_desc',
                                        'width'   => '200px'
                                    ],
                                    [
                                        'caption' => 'Id',
                                        'name'    => 'module_id',
                                        'width'   => '200px'
                                    ]
                                ],
                                'values' => $entries,
                            ],
                        ],
                        'caption' => 'Modules'
                    ];
                }
                break;
            case 'NAMain':
                $items = [];
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_last_measure',
                    'caption' => 'Measurement-Timestamp'
                ];
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => $items,
                    'caption' => 'optional module data'
                ];
                break;
            case 'NAModule1':
            case 'NAModule2':
            case 'NAModule3':
            case 'NAModule4':
                $items = [];
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_last_measure',
                    'caption' => 'Measurement-Timestamp'
                ];
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_signal',
                    'caption' => 'RF-Signal'
                ];
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_battery',
                    'caption' => 'Battery (a global battery indicator is always present)'
                ];
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => $items,
                    'caption' => 'optional module data'
                ];
                break;
        }

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
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
        $last_measure = 0;
        $pressure = '';
        $temp = '';
        $humidity = '';
        $rain = '';
        $sum_rain_1 = '';
        $sum_rain_24 = '';
        $winddir = '';
        $windspeed = '';
        $windgustdir = '';
        $windgust = '';

        $dashboard = $this->GetArrayElem($device, 'dashboard_data', '');
        if ($dashboard == '') {
            $this->SendDebug(__FUNCTION__, 'no dashboard_data, device=' . print_r($device, true), 0);
            $this->LogMessage('update wunderground: no dashboard_data', KL_WARNING);
            return;
        }

        if (isset($dashboard['time_utc'])) {
            $last_measure = $dashboard['time_utc'];
        } else {
            $last_measure = time();
            $this->SendDebug(__FUNCTION__, 'missing "time_utc", use current timestamp', 0);
        }

        $pressure = $this->GetArrayElem($dashboard, 'AbsolutePressure', 0);

        $modules = $this->GetArrayElem($device, 'modules', '');
        if ($modules == '') {
            $this->SendDebug(__FUNCTION__, 'no modules, device=' . print_r($device, true), 0);
            $this->LogMessage('update wunderground: no modules', KL_WARNING);
            return;
        }

        foreach ($modules as $i => $value) {
            $module = $modules[$i];
            if (!isset($module['dashboard_data'])) {
                continue;
            }
            $dashboard = $module['dashboard_data'];
            switch ($module['type']) {
                case 'NAModule1':
                    $temp = $this->GetArrayElem($dashboard, 'Temperature', 0);
                    $humidity = $this->GetArrayElem($dashboard, 'Humidity', 0);
                    break;
                case 'NAModule2':
                    $winddir = $this->GetArrayElem($dashboard, 'WindAngle', 0);
                    $windspeed = $this->GetArrayElem($dashboard, 'WindStrength', 0);
                    $windgustdir = $this->GetArrayElem($dashboard, 'GustAngle', 0);
                    $windgust = $this->GetArrayElem($dashboard, 'GustStrength', 0);
                    break;
                case 'NAModule3':
                    $rain = $this->GetArrayElem($dashboard, 'Rain', 0);
                    $sum_rain_1 = $this->GetArrayElem($dashboard, 'sum_rain_1', 0);
                    $sum_rain_24 = $this->GetArrayElem($dashboard, 'sum_rain_24', 0);
                    break;
                case 'NAModule4':
                    break;
                default:
                    break;
            }
        }

        $param = '&dateutc=' . rawurlencode(date('Y-m-d G:i:s', $last_measure));
        if ($temp > 0) {
            $param .= '&tempf=' . rawurlencode($this->celsius2farenheit($temp));
        }
        if ($humidity > 0) {
            $param .= '&humidity=' . rawurlencode((string) $humidity);
        }
        if ($temp > 0 && $humidity > 0) {
            $dewpoint = $this->CalcDewpoint($temp, $humidity);
            $param .= '&dewptf=' . rawurlencode($this->celsius2farenheit($dewpoint));
        }
        if ($pressure > 0) {
            $param .= '&baromin=' . rawurlencode($this->mb2inch($pressure));
        }
        if ($sum_rain_1 > 0) {
            $param .= '&rainin=' . rawurlencode($this->mm2inch($sum_rain_1));
        }
        if ($sum_rain_24 > 0) {
            $param .= '&dailyrainin=' . rawurlencode($this->mm2inch($sum_rain_24));
        }
        if ($windspeed > 0) {
            $param .= '&windspeedmph=' . rawurlencode($this->km2mile($windspeed)) . '&winddir=' . rawurlencode((string) $winddir);
        }
        if ($windgust > 0) {
            $param .= '&windgustmph=' . rawurlencode($this->km2mile($windgust)) . '&windgustdir=' . rawurlencode((string) $windgustdir);
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
        $this->SendDebug(__FUNCTION__, $msg, 0);

        $url = $wunderground_url . '?ID=' . $wunderground_id . '&PASSWORD=' . $wunderground_key . '&action=updateraw' . $param;

        $this->SendDebug(__FUNCTION__, 'http-get: url=' . $url, 0);
        $time_start = microtime(true);

        $retries = 0;
        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $wstatus = curl_exec($ch);
            $cerrno = curl_errno($ch);
            $cerror = $cerrno ? curl_error($ch) : '';
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($cerrno) {
                $this->SendDebug(__FUNCTION__, ' => retry=' . $retries . ', got curl-errno ' . $cerrno . ' (' . $cerror . ')', 0);
            }
        } while ($cerrno && $retries++ < 2);

        $duration = round(microtime(true) - $time_start, 2);

        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);

        $do_abort = false;

        if ($cerrno) {
            $err = ' => got curl-errno ' . $cerrno . ' (' . $cerror . ')';
            if ($cerrno == 6 /* CURLE_COULDNT_RESOLVE_HOST */) {
                if (preg_match('?^.*://([^/]*)|(.*)$?', $url, $r)) {
                    $err .= PHP_EOL;
                    $host = $r[1];
                    $err .= PHP_EOL;
                    $err .= 'host=' . $host . ': dns-lookup ';
                    $dns_records = @dns_get_record($host, DNS_ALL);
                    if ($dns_records == false) {
                        $err .= 'failed' . PHP_EOL;
                    } else {
                        $err .= print_r($dns_records, true) . PHP_EOL;
                    }
                }
            }
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage($err, KL_WARNING);
            $do_abort = true;
        } elseif ($httpcode != 200) {
            $err = ' => got http-code ' . $httpcode . ' from wunderground';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage($err, KL_WARNING);
            $do_abort = true;
        } else {
            $wstatus = trim($wstatus, "\n");
            if ($wstatus != 'success') {
                $err = ' => got from wunderground: ' . $wstatus;
                $this->SendDebug(__FUNCTION__, $err, 0);
                $this->LogMessage($err, KL_WARNING);
                $do_abort = true;
            }
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

        $statuscode = IS_ACTIVE;
        $battery_alarm = false;
        $module_alarm = false;
        $module_data = [];

        $station_status = true;

        $station_name = $this->GetArrayElem($device, 'station_name', '');
        $home_name = $this->GetArrayElem($device, 'home_name', '');
        if ($station_name == '') {
            $station_name = $home_name;
        }
        $module_name = $this->GetArrayElem($device, 'module_name', '');

        $dashboard = $this->GetArrayElem($device, 'dashboard_data', '');
        if ($dashboard == '') {
            $this->SendDebug(__FUNCTION__, 'no dashboard_data, device=' . print_r($device, true), 0);
            $this->LogMessage('module ' . $station_name . '.' . $module_name . ': no dashboard_data', KL_NOTIFY);
            return $statuscode;
        }

        if (isset($dashboard['time_utc'])) {
            $last_measure = $dashboard['time_utc'];
        } else {
            $last_measure = time();
            $this->SendDebug(__FUNCTION__, 'missing "time_utc", use current timestamp', 0);
        }

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
        $this->SendDebug(__FUNCTION__, $msg, 0);

        if ($with_last_contact) {
            $this->SetValue('LastContact', $last_contact);
        }

        if ($with_signal) {
            $this->SetValue('Wifi', $wifi_status);
        }

        $modules = $this->GetArrayElem($device, 'modules', '');
        if ($modules != '') {
            $guid = '{1023DB4A-D491-A0D5-17CD-380D3578D0FA}';
            $instIDs = IPS_GetInstanceListByModuleID($guid);
            $station_id = $this->ReadPropertyString('station_id');
            foreach (['NAModule4', 'NAModule1', 'NAModule3', 'NAModule2'] as $types) {
                foreach ($modules as $module) {
                    if ($module['type'] != $types) {
                        continue;
                    }

                    $module_id = $module['_id'];
                    $instID = 0;
                    foreach ($instIDs as $id) {
                        if (IPS_GetProperty($id, 'station_id') != $station_id) {
                            continue;
                        }
                        if (IPS_GetProperty($id, 'module_id') != $module_id) {
                            continue;
                        }
                        $instID = $id;
                        break;
                    }
                    $module_name = $this->GetArrayElem($module, 'module_name', '');
                    if ($module_name == '') {
                        $module_name = IPS_GetName($instID);
                    }

                    if (isset($module['dashboard_data'])) {
                        $dashboard = $module['dashboard_data'];
                        if (isset($dashboard['time_utc'])) {
                            $last_measure = $dashboard['time_utc'];
                        } else {
                            $last_measure = time();
                            $this->SendDebug(__FUNCTION__, 'missing "time_utc", use current timestamp', 0);
                        }
                    } else {
                        $last_measure = 0;
                    }

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
                    if ($battery_status < 2) {
                        $battery_alarm = true;
                    }

                    $module_data[] = [
                        'module_type'     => $module['type'],
                        'module_name'     => $module_name,
                        'last_measure'    => $last_measure,
                        'last_message'    => $last_message,
                        'rf_status'       => $rf_status,
                        'battery_status'  => $battery_status,
                    ];
                    $this->SendDebug(__FUNCTION__, 'module_data=' . print_r($module_data, true), 0);
                }
            }
        }

        $station_data = [
            'last_query'      => $now,
            'status'          => $netatmo['status'],
            'last_contact'    => $last_contact,
            'station_name'    => $station_name,
            'modules'         => $module_data,
        ];

        $this->SetBuffer('Data', json_encode($station_data));

        $this->SetValue('Status', $station_status);
        $this->SetValue('ModuleAlarm', $module_alarm);
        $this->SetValue('BatteryAlarm', $battery_alarm);

        if ($with_status_box) {
            $statusbox_script = $this->ReadPropertyInteger('statusbox_script');
            if (IPS_ScriptExists($statusbox_script)) {
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
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');
        $with_trend = $this->ReadPropertyBoolean('with_trend');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');

        $now = time();

        $statuscode = IS_ACTIVE;

        $station_name = $this->GetArrayElem($device, 'station_name', '');
        $home_name = $this->GetArrayElem($device, 'home_name', '');
        if ($station_name == '') {
            $station_name = $home_name;
        }
        $module_name = $this->GetArrayElem($device, 'module_name', '');

        $dashboard = $this->GetArrayElem($device, 'dashboard_data', '');
        if ($dashboard == '') {
            $this->SendDebug(__FUNCTION__, 'no dashboard_data, device=' . print_r($device, true), 0);
            $this->LogMessage('module ' . $station_name . '.' . $module_name . ': no dashboard_data', KL_NOTIFY);
            return $statuscode;
        }

        $Temperature = $this->GetArrayElem($dashboard, 'Temperature', 0);
        $CO2 = $this->GetArrayElem($dashboard, 'CO2', 0);
        $Humidity = $this->GetArrayElem($dashboard, 'Humidity', 0);
        $Noise = $this->GetArrayElem($dashboard, 'Noise', 0);
        $Pressure = $this->GetArrayElem($dashboard, 'Pressure', 0);
        $AbsolutePressure = $this->GetArrayElem($dashboard, 'AbsolutePressure', 0);

        $min_temp = $this->GetArrayElem($dashboard, 'min_temp', 0);
        $date_min_temp = $this->GetArrayElem($dashboard, 'date_min_temp', 0);
        $max_temp = $this->GetArrayElem($dashboard, 'max_temp', 0);
        $date_max_temp = $this->GetArrayElem($dashboard, 'date_max_temp', 0);
        $temp_trend = $this->GetArrayElem($dashboard, 'temp_trend', '');
        $pressure_trend = $this->GetArrayElem($dashboard, 'pressure_trend', '');

        if (isset($dashboard['time_utc'])) {
            $last_measure = $dashboard['time_utc'];
        } else {
            $last_measure = time();
            $this->SendDebug(__FUNCTION__, 'missing "time_utc", use current timestamp', 0);
        }

        $msg = "base-module \"$module_name\": Temperature=$Temperature, CO2=$CO2, Humidity=$Humidity, Noise=$Noise, Pressure=$Pressure, AbsolutePressure=$AbsolutePressure";
        $this->SendDebug(__FUNCTION__, $msg, 0);
        $module_type_text = $this->module_type2text($module_type);
        $msg = "module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure";
        $this->SendDebug(__FUNCTION__, $msg, 0);

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

        if ($with_minmax) {
            $this->SetValue('TemperatureMax', $max_temp);
            $this->SetValue('TemperatureMaxTimestamp', $date_max_temp);
            $this->SetValue('TemperatureMin', $min_temp);
            $this->SetValue('TemperatureMinTimestamp', $date_min_temp);
        }
        if ($with_trend) {
            $trend = $this->map_trend($temp_trend);
            if (is_int($trend)) {
                $this->SetValue('TemperatureTrend', $trend);
            }
            $trend = $this->map_trend($pressure_trend);
            if (is_int($trend)) {
                $this->SetValue('PressureTrend', $trend);
            }
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
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');
        $with_trend = $this->ReadPropertyBoolean('with_trend');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_signal = $this->ReadPropertyBoolean('with_signal');
        $with_battery = $this->ReadPropertyBoolean('with_battery');

        $now = time();

        $statuscode = IS_ACTIVE;

        $station_name = $this->GetArrayElem($device, 'station_name', '');
        $home_name = $this->GetArrayElem($device, 'home_name', '');
        if ($station_name == '') {
            $station_name = $home_name;
        }

        $module_found = false;
        $module_nodata = false;
        $modules = $this->GetArrayElem($device, 'modules', '');
        if ($modules != '') {
            foreach ($modules as $module) {
                $id = $module['_id'];
                if ($module_id != $module['_id']) {
                    continue;
                }

                $module_found = true;

                $module_name = $this->GetArrayElem($module, 'module_name', '');

                $last_message = $module['last_message'];

                $rf_status = $this->map_rf_status($module['rf_status']);
                if ($with_signal) {
                    $this->SetValue('RfSignal', $rf_status);
                }

                $battery_status = $this->map_battery_status($module_type, $module['battery_vp']);
                if ($with_battery) {
                    $this->SetValue('Battery', $battery_status);
                }

                $dashboard = $this->GetArrayElem($module, 'dashboard_data', '');
                if ($dashboard == '') {
                    $module_nodata = true;
                    break;
                }

                if (isset($dashboard['time_utc'])) {
                    $last_measure = $dashboard['time_utc'];
                } else {
                    $last_measure = time();
                    $this->SendDebug(__FUNCTION__, 'missing "time_utc", use current timestamp', 0);
                }
                if ($with_last_measure) {
                    $this->SetValue('LastMeasure', $last_measure);
                }

                switch ($module_type) {
                    case 'NAModule1':
                        // Außenmodul
                        $Temperature = $this->GetArrayElem($dashboard, 'Temperature', 0);
                        $Humidity = $this->GetArrayElem($dashboard, 'Humidity', 0);

                        $min_temp = $this->GetArrayElem($dashboard, 'min_temp', 0);
                        $date_min_temp = $this->GetArrayElem($dashboard, 'date_min_temp', 0);
                        $max_temp = $this->GetArrayElem($dashboard, 'max_temp', 0);
                        $date_max_temp = $this->GetArrayElem($dashboard, 'date_max_temp', 0);
                        $temp_trend = $this->GetArrayElem($dashboard, 'temp_trend', '');

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
                        if ($with_minmax) {
                            $this->SetValue('TemperatureMax', $max_temp);
                            $this->SetValue('TemperatureMaxTimestamp', $date_max_temp);
                            $this->SetValue('TemperatureMin', $min_temp);
                            $this->SetValue('TemperatureMinTimestamp', $date_min_temp);
                        }
                        if ($with_trend) {
                            $trend = $this->map_trend($temp_trend);
                            if (is_int($trend)) {
                                $this->SetValue('TemperatureTrend', $trend);
                            }
                        }

                        $msg = "outdoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity";
                        $this->SendDebug(__FUNCTION__, $msg, 0);
                        break;
                    case 'NAModule2':
                        // Windmesser
                        $WindSpeed = $this->GetArrayElem($dashboard, 'WindStrength', 0);
                        $WindAngle = $this->GetArrayElem($dashboard, 'WindAngle', 0);
                        $GustSpeed = $this->GetArrayElem($dashboard, 'GustStrength', 0);
                        $GustAngle = $this->GetArrayElem($dashboard, 'GustAngle', 0);

                        $wind_max = $this->GetArrayElem($dashboard, 'max_wind_str', 0);
                        $wind_max_angle = $this->GetArrayElem($dashboard, 'max_wind_angle', 0);
                        $wind_max_date = $this->GetArrayElem($dashboard, 'date_max_wind_str', 0);

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
                        if ($with_minmax) {
                            $this->SetValue('GustMaxSpeed', $wind_max);
                            if ($with_windangle) {
                                $this->SetValue('GustMaxAngle', $wind_max_angle);
                            }
                            if ($with_windstrength) {
                                $windstrength = $this->ConvertWindSpeed2Strength($wind_max);
                                $this->SetValue('GustMaxStrength', $windstrength);
                            }
                            if ($with_winddirection) {
                                $dir = $this->ConvertWindDirection2Text($wind_max_angle) . ' (' . $wind_max_angle . '°)';
                                $this->SetValue('GustMaxDirection', $dir);
                            }
                            $this->SetValue('GustMaxTimestamp', $wind_max_date);
                        }

                        $msg = "wind gauge \"$module_name\": WindSpeed=$WindSpeed, WindAngle=$WindAngle, GustSpeed=$GustSpeed, GustAngle=$GustAngle";
                        $this->SendDebug(__FUNCTION__, $msg, 0);
                        break;
                    case 'NAModule3':
                        // Regenmesser
                        $Rain = $this->GetArrayElem($dashboard, 'Rain', 0);
                        $sum_rain_1 = $this->GetArrayElem($dashboard, 'sum_rain_1', 0);
                        $sum_rain_24 = $this->GetArrayElem($dashboard, 'sum_rain_24', 0);

                        $this->SetValue('Rain', $Rain);
                        $this->SetValue('Rain_1h', $sum_rain_1);
                        $this->SetValue('Rain_24h', $sum_rain_24);

                        $msg = "rain gauge \"$module_name\": Rain=$Rain, sum_rain_1=$sum_rain_1, sum_rain_24=$sum_rain_24";
                        $this->SendDebug(__FUNCTION__, $msg, 0);
                        break;
                    case 'NAModule4':
                        // Innenmodul
                        $Temperature = $this->GetArrayElem($dashboard, 'Temperature', 0);
                        $Humidity = $this->GetArrayElem($dashboard, 'Humidity', 0);
                        $CO2 = $this->GetArrayElem($dashboard, 'CO2', 0);

                        $min_temp = $this->GetArrayElem($dashboard, 'min_temp', 0);
                        $date_min_temp = $this->GetArrayElem($dashboard, 'date_min_temp', 0);
                        $max_temp = $this->GetArrayElem($dashboard, 'max_temp', 0);
                        $date_max_temp = $this->GetArrayElem($dashboard, 'date_max_temp', 0);
                        $temp_trend = $this->GetArrayElem($dashboard, 'temp_trend', '');

                        $this->SetValue('Temperature', $Temperature);
                        $this->SetValue('CO2', $CO2);
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
                        if ($with_minmax) {
                            $this->SetValue('TemperatureMax', $max_temp);
                            $this->SetValue('TemperatureMaxTimestamp', $date_max_temp);
                            $this->SetValue('TemperatureMin', $min_temp);
                            $this->SetValue('TemperatureMinTimestamp', $date_min_temp);
                        }
                        if ($with_trend) {
                            $trend = $this->map_trend($temp_trend);
                            if (is_int($trend)) {
                                $this->SetValue('TemperatureTrend', $trend);
                            }
                        }

                        $msg = "indoor module \"$module_name\": Temperature=$Temperature, Humidity=$Humidity, CO2=$CO2";
                        $this->SendDebug(__FUNCTION__, $msg, 0);
                        break;
                }

                $module_type_text = $this->module_type2text($module_type);
                $msg = "  module_type=$module_type($module_type_text), module_name=$module_name, last_measure=$last_measure, rf_status=$rf_status, battery_status=$battery_status";
                $this->SendDebug(__FUNCTION__, $msg, 0);
            }
        }

        if ($module_found == false) {
            $instName = IPS_GetName($this->InstanceID);
            $module_type_text = $this->module_type2text($module_type);
            $msg = "instance $this->InstanceID \"$instName\" ($module_type_text) module with id $module_id not found";
            $this->LogMessage($msg, KL_WARNING);
            $this->SendDebug(__FUNCTION__, $msg, 0);
            return $statuscode;
        }

        if ($module_nodata == true) {
            $instName = IPS_GetName($this->InstanceID);
            $module_type_text = $this->module_type2text($module_type);
            $msg = "instance $this->InstanceID \"$instName\" ($module_type_text) module with id $module_id has no data";
            $this->LogMessage($msg, KL_NOTIFY);
            $this->SendDebug(__FUNCTION__, $msg, 0);
            return $statuscode;
        }

        if ($module_type == 'NAModule1') {
            // Außenmodul

            if ($with_windchill) {
                $temp = '';
                $windspeed = '';
                $modules = $device['modules'];
                foreach ($modules as $i => $value) {
                    $module = $modules[$i];
                    if (!isset($module['dashboard_data'])) {
                        continue;
                    }
                    $dashboard = $module['dashboard_data'];
                    switch ($module['type']) {
                        case 'NAModule1':
                            $temp = $this->GetArrayElem($dashboard, 'Temperature', 0);
                            break;
                        case 'NAModule2':
                            $type = 'wind';
                            $windspeed = $this->GetArrayElem($dashboard, 'WindStrength', 0);
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
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

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
                $statuscode = self::$IS_STATIONMISSІNG;
                $do_abort = true;
            }
        } else {
            $err = 'no data';
            $statuscode = self::$IS_NODATA;
            $do_abort = true;
        }

        if ($do_abort) {
            echo "statuscode=$statuscode, err=$err";
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->MaintainStatus($statuscode);

            if ($module_type == 'NAMain') {
                $this->SetValue('Status', false);
                $this->SetValue('BatteryAlarm', true);
                $this->SetValue('ModuleAlarm', true);
                $this->SetValue('Wunderground', false);
            }
            return -1;
        }

        $now = time();

        $this->SendDebug(__FUNCTION__, 'netatmo=' . print_r($netatmo, true), 0);
        $this->SendDebug(__FUNCTION__, 'device=' . print_r($device, true), 0);
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
                $this->SendDebug(__FUNCTION__, 'unknown module_type "' . $module_type . '"', 0);
                $statuscode = IS_ACTIVE;
                break;
        }

        $this->MaintainStatus($statuscode);

        if ($module_type == 'Station') {
            $this->update_Wunderground($netatmo, $device);
        }
    }

    private function Build_StatusBox($station_data)
    {
        $img_path = $this->ReadPropertyString('hook') . '/imgs/';

        $now = time();

        $html = '';

        $html .= "<body>\n";
        $html .= "<style>\n";
        $html .= "body { margin: 1; padding: 0; }\n";
        $html .= "table { border-collapse: collapse; border: 0px solid; margin: 0.5em; width: 100%; }\n";
        $html .= "th, td { padding: 1;}\n";
        $html .= "tbody th { text-align: left; }\n";
        $html .= "tbody td { text-align: left; }\n";
        $html .= "#spalte_caption { width: 350px; }\n";
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
        $html .= "<colgroup><col id='spalte_caption'></colgroup>\n";
        $html .= "<tdata>\n";

        $html .= "<tr>\n";
        $html .= "<td>Zeitpunkt der Datenabfrage:</td>\n";
        $html .= "<td>$dt</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "<td>letzte Übertragung an Netatmo:</td>\n";
        $html .= "<td>$last_contact_pretty</td>\n";
        $html .= "</tr>\n";

        $html .= "</tdata>\n";
        $html .= "</table>\n";
        $html .= "<table>\n";

        $html .= "<colgroup><col id='spalte_type'></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col></colgroup>\n";
        $html .= "<colgroup><col id='spalte_signal'></colgroup>\n";
        $html .= "<colgroup><col id='spalte_battry'></colgroup>\n";

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
            $module_name = $this->GetArrayElem($module, 'module_name', '');

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
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>\n";
        $html .= "<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet'>\n";
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
            $html .= "<colgroup><col id='spalte_type'></colgroup>\n";
            $html .= "<colgroup><col></colgroup>\n";
            $html .= "<colgroup><col></colgroup>\n";
            $html .= "<colgroup><col id='spalte_signal'></colgroup>\n";
            $html .= "<colgroup><col id='spalte_battry'></colgroup>\n";
            $html .= "<tdata>\n";

            $img_path = $this->ReadPropertyString('hook') . '/imgs/';

            $modules = $station_data['modules'];
            foreach ($modules as $module) {
                $module_type = $module['module_type'];
                $module_type_text = $this->module_type2text($module_type);
                $module_type_img = $img_path . $this->module_type2img($module_type);
                $module_name = $this->GetArrayElem($module, 'module_name', '');

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
        $img_path = $this->ReadPropertyString('hook') . '/imgs/';

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
        $this->SendDebug(__FUNCTION__, '_SERVER=' . print_r($_SERVER, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        $hook = $this->ReadPropertyString('hook');
        if ($hook == '') {
            http_response_code(404);
            die('File not found!');
        }
        $basename = substr($uri, strlen($hook . '/'));
        if ($basename == 'status') {
            $webhook_script = $this->ReadPropertyInteger('webhook_script');
            if (IPS_ScriptExists($webhook_script)) {
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

        if (isset($val2txt[$val])) {
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

        if (isset($val2img[$val])) {
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
            $val = self::$WIFI_HIGH;
        } elseif ($status <= 71) {
            $val = self::$WIFI_GOOD;
        } elseif ($status <= 86) {
            $val = self::$WIFI_AVERAGE;
        } else {
            $val = self::$WIFI_BAD;
        }

        return $val;
    }

    private function wifi_status2text($status)
    {
        return $this->CheckVarProfile4Value('Netatmo.Wifi', $status);
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
            $val = self::$SIGNAL_STATUS_FULL;
        } elseif ($status <= 70) {
            $val = self::$SIGNAL_STATUS_HIGH;
        } elseif ($status <= 80) {
            $val = self::$SIGNAL_STATUS_MEDIUM;
        } elseif ($status <= 90) {
            $val = self::$SIGNAL_STATUS_LOW;
        } else {
            $val = self::$SIGNAL_STATUS_VERYLOW;
        }

        return $val;
    }

    private function signal_status2text($status)
    {
        return $this->CheckVarProfile4Value('Netatmo.RfSignal', $status);
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
            $val = self::$BATTERY_EMPTY;
        } elseif ($battery_vp < $vp_map[1]) {
            $val = self::$BATTERY_LOW;
        } elseif ($battery_vp < $vp_map[2]) {
            $val = self::$BATTERY_MEDIUM;
        } elseif ($battery_vp < $vp_map[3]) {
            $val = self::$BATTERY_HIGH;
        } elseif ($battery_vp < $vp_map[4]) {
            $val = self::$BATTERY_FULL;
        } else {
            $val = self::$BATTERY_MAX;
        }

        return $val;
    }

    private function battery_status2text($status)
    {
        return $this->CheckVarProfile4Value('Netatmo.Battery', $status);
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

    private function map_trend($trend)
    {
        switch ($trend) {
            case 'down':
                $t = self::$TREND_DOWN;
                break;
            case 'stable':
                $t = self::$TREND_STABLE;
                break;
            case 'up':
                $t = self::$TREND_UP;
                break;
            default:
                $t = '';
                $this->SendDebug(__FUNCTION__, 'unknown trend "' . $trend . '"', 0);
                break;
        }

        return $t;
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
            $this->SendDebug(__FUNCTION__, 'altitude=' . $altitude, 0);
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
            'NNE',
            'NE',
            'ENE',
            'E',
            'ESE',
            'SE',
            'SSE',
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
            $txt = $this->Translate($dir2txt[$idx]);
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Windgeschwindigkeit in Beaufort umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Beaufortskala
    public function ConvertWindSpeed2Strength(int $speed)
    {
        $kn2bft = [1, 4, 7, 11, 16, 22, 28, 34, 41, 48, 56, 64];

        $kn = $speed / 1.852;
        for ($i = 0; $i < count($kn2bft); $i++) {
            if ($kn < $kn2bft[$i]) {
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
            'Calm',
            'Light air',
            'Light breeze',
            'Gentle breeze',
            'Moderate breeze',
            'Fresh breeze',
            'Strong breeze',
            'High wind',
            'Gale',
            'Strong gale',
            'Storm',
            'Hurricane force',
            'Violent storm'
        ];

        if ($bft >= 0 && $bft < count($bft2txt)) {
            $txt = $this->Translate($bft2txt[$bft]);
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
