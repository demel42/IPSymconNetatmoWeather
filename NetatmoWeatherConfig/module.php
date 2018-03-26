<?php

class NetatmoWeatherConfig extends IPSModule
{
    private $scriptName = 'NetatmoWeatherConfig';

    public function Create()
    {
        parent::Create();

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');

        $this->RegisterPropertyString('station_name', '');
        $this->RegisterPropertyInteger('minutes2fail', 30);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->SetStatus(102);
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
				echo "unablte to create instance \"" . $module_name . "\"";
				return $instID;
			}
            IPS_SetProperty($instID, 'module_id', $module_id);
        }

        IPS_SetName($instID, $module_name);
        IPS_SetInfo($instID, $module_info);
        IPS_SetPosition($instID, $pos);
        foreach ($properties as $key => $property) {
            IPS_SetProperty($instID, $key, $property);
        }
        IPS_ApplyChanges($instID);

        return $instID;
    }

    public function Doit()
    {
        $station_name = $this->ReadPropertyString('station_name');

        $SendData = ['DataID' => '{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}'];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug($this->scriptName, "data=$data", 0);

        $statuscode = 0;
        $do_abort = false;

        if ($data != '') {
            $netatmo = json_decode($data, true);
            $this->SendDebug($this->scriptName, 'netatmo=' . print_r($netatmo, true), 0);

            $devices = $netatmo['body']['devices'];
            $this->SendDebug($this->scriptName, 'devices=' . print_r($devices, true), 0);
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
                    $statuscode = 202;
                }
            } else {
                switch (count($devices)) {
                    case 1:
                        $device = $devices[0];
                        $station_name = $device['station_name'];
                        break;
                    case 0:
                        $err = 'data contains no station';
                        $statuscode = 203;
                        break;
                    default:
                        $err = 'data contains to many station';
                        $statuscode = 204;
                        break;
                }
            }
            if ($statuscode) {
                echo "statuscode=$statuscode, err=$err";
                $this->SendDebug($this->scriptName, $err, 0);
                $this->SetStatus($statuscode);
                $do_abort = true;
            }
        } else {
            $err = 'no data';
            $statuscode = 201;
            echo "statuscode=$statuscode, err=$err";
            $this->SendDebug($this->scriptName, $err, 0);
            $this->SetStatus($statuscode);
            $do_abort = true;
        }

        if ($do_abort) {
            return -1;
        }

        $this->SetStatus(102);

        $this->SetStatus(102);

        $place = $device['place'];
        $station_altitude = $place['altitude'];
        $station_longitude = $place['location'][0];
        $station_latitude = $place['location'][1];

        /* Basismodul */
        $module_type = 'NAMain';
        $module_id = $device['_id'];
        $module_name = $device['module_name'];
        $module_info = 'Basismodul (' . $station_name . '\\' . $module_name . ')';

        $minutes2fail = $this->ReadPropertyInteger('minutes2fail');

        $properties = [
                'module_type'       => $module_type,
                'station_altitude'  => $station_altitude,
                'station_longitude' => $station_longitude,
                'station_latitude'  => $station_latitude,
                'minutes2fail'      => $minutes2fail
            ];
        $pos = 1000;
        $instID = $this->FindOrCreateInstance($module_id, $module_name, $module_info, $properties, $pos++);

        $station_id = $module_id;
        $modules = $netatmo['body']['modules'];
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
                        $this->SendDebug($this->scriptName, 'unknown module_type ' . $module['type'], 0);
                        continue;
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
