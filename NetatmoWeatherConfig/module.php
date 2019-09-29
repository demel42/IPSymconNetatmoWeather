<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NetatmoWeatherConfig extends IPSModule
{
    use NetatmoWeatherCommon;

    public function Create()
    {
        parent::Create();

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->SetStatus(IS_ACTIVE);
    }

    public function GetConfigurationForm()
    {
        $SendData = ['DataID' => '{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}'];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, "data=$data", 0);

        $options = [];
        if ($data != '') {
            $netatmo = json_decode($data, true);
            $devices = $netatmo['body']['devices'];
            foreach ($devices as $device) {
                $station_name = $device['station_name'];
                $options[] = ['label' => $station_name, 'value' => $station_name];
            }
        }

        $formActions = [];
        $formActions[] = ['type' => 'Select', 'name' => 'station_name', 'caption' => 'Station-Name', 'options' => $options];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Import of station',
                            'confirm' => 'Triggering the function creates the missing instances for all Netatmo modules of the selected station. Are you sure?',
                            'onClick' => 'NetatmoWeatherConfig_Doit($id, $station_name);'
                        ];
		if (IPS_GetKernelVersion() < 5.2) {
			$formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
			$formActions[] = [
								'type'    => 'Button',
								'caption' => 'Module description',
								'onClick' => 'echo "https://github.com/demel42/IPSymconNetatmoWeather/blob/master/README.md";'
							];
		}

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_NODATA, 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (unauthorized)'];
        $formStatus[] = ['code' => IS_FORBIDDEN, 'icon' => 'error', 'caption' => 'Instance is inactive (forbidden)'];
        $formStatus[] = ['code' => IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => IS_NOSTATION, 'icon' => 'error', 'caption' => 'Instance is inactive (no station)'];
        $formStatus[] = ['code' => IS_STATIONMISSІNG, 'icon' => 'error', 'caption' => 'Instance is inactive (station missing)'];

        return json_encode(['actions' => $formActions, 'status' => $formStatus]);
    }

    private function FindOrCreateInstance($module_id, $module_name, $module_info, $properties, $pos)
    {
        $instID = '';

        $instIDs = IPS_GetInstanceListByModuleID('{1023DB4A-D491-A0D5-17CD-380D3578D0FA}');
        foreach ($instIDs as $id) {
            $cfg = IPS_GetConfiguration($id);
            $jcfg = json_decode($cfg, true);
            if (!isset($jcfg['module_id'])) {
                continue;
            }
            if ($jcfg['module_id'] == $module_id) {
                $instID = $id;
                break;
            }
        }

        if ($instID == '') {
            $instID = IPS_CreateInstance('{1023DB4A-D491-A0D5-17CD-380D3578D0FA}');
            if ($instID == '') {
                echo 'unable to create instance "' . $module_name . '"';
                return $instID;
            }
            IPS_SetProperty($instID, 'module_id', $module_id);
            foreach ($properties as $key => $property) {
                IPS_SetProperty($instID, $key, $property);
            }
            IPS_SetName($instID, $module_name);
            IPS_SetInfo($instID, $module_info);
            IPS_SetPosition($instID, $pos);
        }

        IPS_ApplyChanges($instID);

        return $instID;
    }

    public function Doit(?string $station_name)
    {
        $SendData = ['DataID' => '{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}'];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, "data=$data", 0);

        $err = '';
        $statuscode = 0;
        $do_abort = false;

        if ($data != '') {
            $netatmo = json_decode($data, true);
            $this->SendDebug(__FUNCTION__, 'netatmo=' . print_r($netatmo, true), 0);

            $devices = $netatmo['body']['devices'];
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if ($station_name != '') {
                $station_found = false;
                foreach ($devices as $device) {
                    if ($station_name == $device['station_name']) {
                        $station_found = true;
                        break;
                    }
                }
                if (!$station_found) {
                    $err = "station \"$station_name\" don't exists";
                    $statuscode = IS_STATIONMISSІNG;
                    $do_abort = true;
                }
            } else {
                $err = 'no station selected';
                $statuscode = IS_NOSTATION;
                $do_abort = true;
            }
        } else {
            $err = 'no data';
            $statuscode = IS_NODATA;
            $do_abort = true;
        }

        if ($do_abort) {
            echo "statuscode=$statuscode, err=$err";
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->SetStatus($statuscode);
            return -1;
        }

        $this->SetStatus(IS_ACTIVE);

        $place = $device['place'];
        $station_id = $device['_id'];
        $station_altitude = $place['altitude'];
        $station_longitude = $place['location'][0];
        $station_latitude = $place['location'][1];

        /* Station */
        $module_type = 'Station';
        $module_info = 'Station (' . $station_name . ')';

        $properties = [
                'module_type'       => $module_type,
                'station_id'        => $station_id,
                'station_altitude'  => $station_altitude,
                'station_longitude' => $station_longitude,
                'station_latitude'  => $station_latitude,
            ];
        $pos = 1000;
        $instID = $this->FindOrCreateInstance('', $station_name, $module_info, $properties, $pos++);

        /* Basismodul */
        $module_type = 'NAMain';
        $module_name = $device['module_name'];
        $module_info = 'Basismodul (' . $station_name . '\\' . $module_name . ')';

        $properties = [
                'module_type' => $module_type,
                'station_id'  => $station_id,
            ];
        $instID = $this->FindOrCreateInstance($station_id, $module_name, $module_info, $properties, $pos++);

        $modules = $device['modules'];
        foreach (['NAModule4', 'NAModule1', 'NAModule3', 'NAModule2'] as $types) {
            foreach ($modules as $module) {
                if ($module['type'] != $types) {
                    continue;
                }
                $module_type = $module['type'];
                switch ($module_type) {
                    case 'NAModule1':
                        $module_id = $module['_id'];
                        $module_name = $module['module_name'];
                        $module_info = 'Außenmodul (' . $station_name . '\\' . $module_name . ')';
                        break;
                    case 'NAModule2':
                        $module_id = $module['_id'];
                        $module_name = $module['module_name'];
                        $module_info = 'Windmesser (' . $station_name . '\\' . $module_name . ')';
                        break;
                    case 'NAModule3':
                        $module_id = $module['_id'];
                        $module_name = $module['module_name'];
                        $module_info = 'Regenmesser (' . $station_name . '\\' . $module_name . ')';
                        break;
                    case 'NAModule4':
                        $module_id = $module['_id'];
                        $module_name = $module['module_name'];
                        $module_info = 'Innenmodul (' . $station_name . '\\' . $module_name . ')';
                        break;
                    default:
                        echo 'unknown module_type ' . $module['type'];
                        $this->SendDebug(__FUNCTION__, 'unknown module_type ' . $module['type'], 0);
                        break;
                }
                $properties = [
                        'module_type' => $module_type,
                        'station_id'  => $station_id,
                    ];
                $instID = $this->FindOrCreateInstance($module_id, $module_name, $module_info, $properties, $pos++);
            }
        }
    }
}
