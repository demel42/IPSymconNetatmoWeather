<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class NetatmoWeatherConfig extends IPSModule
{
    use NetatmoWeather\StubsCommonLib;
    use NetatmoWeatherLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{26A55798-5CBC-88F6-5C7B-370B043B24F9}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $propertyNames = ['ImportCategoryID'];
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

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function getConfiguratorValues()
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

        if (is_array($jdata)) {
            $devices = $jdata['body']['devices'];
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if (is_array($devices)) {
                foreach ($devices as $device) {
                    $module_type = 'Station';
                    $station_name = $this->GetArrayElem($device, 'station_name', '');
                    $home_name = $this->GetArrayElem($device, 'home_name', '');
                    if ($station_name == '') {
                        $station_name = $home_name;
                    }
                    $station_id = $device['_id'];
                    $module_id = '';
                    $place = $device['place'];
                    $city = isset($place['city']) ? $place['city'] : '';
                    $altitude = $place['altitude'];
                    $longitude = $place['location'][0];
                    $latitude = $place['location'][1];

                    $info = 'Station (' . $station_name . ')';

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

                    $entry = [
                        'instanceID' => $instanceID,
                        'name'       => $station_name,
                        'city'       => $city,
                        'station_id' => $station_id,
                        'create'     => [
                            'moduleID'       => $guid,
                            'location'       => $this->GetConfiguratorLocation($catID),
                            'info'           => $info,
                            'configuration'  => [
                                'module_id'         => $module_id,
                                'module_type'       => $module_type,
                                'station_id'        => $station_id,
                                'station_altitude'  => $altitude,
                                'station_longitude' => $longitude,
                                'station_latitude'  => $latitude,
                            ]
                        ]
                    ];

                    $entries[] = $entry;
                    $this->SendDebug(__FUNCTION__, 'entry=' . print_r($entry, true), 0);
                }
            }
        }

        foreach ($instIDs as $instID) {
            $fnd = false;
            foreach ($entries as $entry) {
                if ($entry['instanceID'] == $instID) {
                    $fnd = true;
                    break;
                }
            }
            if ($fnd) {
                continue;
            }

            if (IPS_GetInstance($instID)['ConnectionID'] != IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                continue;
            }

            $module_type = IPS_GetProperty($instID, 'module_type');
            if ($module_type != 'Station') {
                continue;
            }

            $name = IPS_GetName($instID);
            $city = '';
            $station_id = IPS_GetProperty($instID, 'station_id');

            $entry = [
                'instanceID' => $instID,
                'name'       => $name,
                'city'       => $city,
                'station_id' => $station_id,
            ];
            $entries[] = $entry;
            $this->SendDebug(__FUNCTION__, 'missing entry=' . print_r($entry, true), 0);
        }

        return $entries;
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Netatmo Weather Configurator');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'SelectCategory',
            'name'    => 'ImportCategoryID',
            'caption' => 'category for weatherstations to be created'
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

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

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
}
