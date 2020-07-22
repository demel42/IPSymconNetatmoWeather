<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NetatmoWeatherConfig extends IPSModule
{
    use NetatmoWeatherCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $refs = $this->GetReferenceList();
        foreach ($refs as $ref) {
            $this->UnregisterReference($ref);
        }
        $propertyNames = ['ImportCategoryID'];
        foreach ($propertyNames as $name) {
            $oid = $this->ReadPropertyInteger($name);
            if ($oid > 0) {
                $this->RegisterReference($oid);
            }
        }

        $this->SetStatus(IS_ACTIVE);
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    private function SetLocation()
    {
        $category = $this->ReadPropertyInteger('ImportCategoryID');
        $tree_position = [];
        if ($category > 0 && IPS_ObjectExists($category)) {
            $tree_position[] = IPS_GetName($category);
            $parent = IPS_GetObject($category)['ParentID'];
            while ($parent > 0) {
                if ($parent > 0) {
                    $tree_position[] = IPS_GetName($parent);
                }
                $parent = IPS_GetObject($parent)['ParentID'];
            }
            $tree_position = array_reverse($tree_position);
        }
        return $tree_position;
    }

    private function getConfiguratorValues()
    {
        $entries = [];

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            $this->LogMessage('has no active parent instance', KL_WARNING);
            return $entries;
        }

        $SendData = ['DataID' => '{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}', 'Function' => 'LastData'];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);

        if ($data != '') {
            $netatmo = json_decode($data, true);
            $this->SendDebug(__FUNCTION__, 'netatmo=' . print_r($netatmo, true), 0);

            $devices = $netatmo['body']['devices'];
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if ($devices != '') {
                $guid = '{1023DB4A-D491-A0D5-17CD-380D3578D0FA}';
                $instIDs = IPS_GetInstanceListByModuleID($guid);

                foreach ($devices as $device) {
                    $module_type = 'Station';
                    $station_name = $device['station_name'];
                    $station_id = $device['_id'];
                    $module_id = '';
                    $place = $device['place'];
                    $city = $place['city'];
                    $altitude = $place['altitude'];
                    $longitude = $place['location'][0];
                    $latitude = $place['location'][1];

                    $info = 'Station (' . $station_name . ')';

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

                    $entry = [
                        'name'       => $station_name,
                        'city'       => $city,
                        'station_id' => $station_id,
                        'instanceID' => $instID,
                        'create'     => [
                            'moduleID'       => $guid,
                            'location'       => $this->SetLocation(),
                            'info'           => $info,
                            'configuration'  => [
                                'module_id'       	 => $module_id,
                                'module_type'       => $module_type,
                                'station_id'        => $station_id,
                                'station_altitude'  => $altitude,
                                'station_longitude' => $longitude,
                                'station_latitude'  => $latitude,
                            ]
                        ]
                    ];

                    $entries[] = $entry;
                }
            }
        }
        return $entries;
    }

    protected function GetFormElements()
    {
        $formElements = [];

        if ($this->HasActiveParent() == false) {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => 'Instance has no active parent instance',
            ];
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'category for weatherstations to be created:',
        ];
        $formElements[] = [
            'type'    => 'SelectCategory',
            'name'    => 'ImportCategoryID',
            'caption' => 'category'
        ];

        $entries = $this->getConfiguratorValues();
        $formElements[] = [
            'type'    => 'Configurator',
            'name'    => 'stations',
            'caption' => 'available weatherstations',

            'rowCount' => count($entries),

            'add'    => false,
            'delete' => false,
            'sort'   => [
                'column'    => 'name',
                'direction' => 'ascending'
            ],
            'columns' => [
                [
                    'caption' => 'Name',
                    'name'    => 'name',
                    'width'   => 'auto'
                ],
                [
                    'caption' => 'City',
                    'name'    => 'city',
                    'width'   => '200px'
                ],
                [
                    'caption' => 'Id',
                    'name'    => 'station_id',
                    'width'   => '200px'
                ]
            ],
            'values' => $entries,
        ];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];

        return $formActions;
    }
}
