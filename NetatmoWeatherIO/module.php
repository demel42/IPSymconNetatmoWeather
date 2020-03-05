<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NetatmoWeatherIO extends IPSModule
{
    use NetatmoWeatherCommon;

    private $oauthIdentifer = 'netatmo';

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('Netatmo_User', '');
        $this->RegisterPropertyString('Netatmo_Password', '');
        $this->RegisterPropertyString('Netatmo_Client', '');
        $this->RegisterPropertyString('Netatmo_Secret', '');

        $this->RegisterPropertyInteger('UpdateDataInterval', '5');
        $this->RegisterPropertyInteger('ignore_http_error', '0');

        $this->RegisterPropertyInteger('OAuth_Type', CONNECTION_UNDEFINED);

        $this->RegisterAttributeString('ApiRefreshToken', '');

        $this->RegisterTimer('UpdateDataWeather', 0, 'NetatmoWeather_UpdateData(' . $this->InstanceID . ');');
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $oauth_type = $this->ReadPropertyInteger('OAuth_Type');
            if ($oauth_type == CONNECTION_OAUTH) {
                $this->RegisterOAuth($this->oauthIdentifer);
            }
            $this->UpdateData();
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateDataWeather', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $oauth_type = $this->ReadPropertyInteger('OAuth_Type');
        switch ($oauth_type) {
            case CONNECTION_DEVELOPER:
                $netatmo_user = $this->ReadPropertyString('Netatmo_User');
                $netatmo_password = $this->ReadPropertyString('Netatmo_Password');
                $netatmo_client = $this->ReadPropertyString('Netatmo_Client');
                $netatmo_secret = $this->ReadPropertyString('Netatmo_Secret');
                if ($netatmo_user == '' || $netatmo_password == '' || $netatmo_client == '' || $netatmo_secret == '') {
                    $this->SetStatus(IS_INACTIVE);
                    return;
                }
                $this->SetStatus(IS_ACTIVE);
                break;
            case CONNECTION_OAUTH:
                if ($this->GetConnectUrl() == false) {
                    $this->SetStatus(IS_NOSYMCONCONNECT);
                    return;
                }
                $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
                $this->SendDebug(__FUNCTION__, 'refresh_token=' . $refresh_token, 0);
                if ($refresh_token == '') {
                    $this->SetStatus(IS_NOLOGIN);
                } else {
                    $this->SetStatus(IS_ACTIVE);
                }
                break;
            default:
                break;
        }

        if (IPS_GetKernelRunlevel() == KR_READY) {
            if ($oauth_type == CONNECTION_OAUTH) {
                $this->RegisterOAuth($this->oauthIdentifer);
            }
            $this->SetUpdateInterval();
            $this->UpdateData();
        }
    }

    private function RegisterOAuth($WebOAuth)
    {
        $ids = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}');
        if (count($ids) > 0) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);
            $found = false;
            foreach ($clientIDs as $index => $clientID) {
                if ($clientID['ClientID'] == $WebOAuth) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $clientIDs[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $clientIDs[] = ['ClientID' => $WebOAuth, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
            IPS_ApplyChanges($ids[0]);
        }
    }

    public function Login()
    {
        $url = 'https://oauth.ipmagic.de/authorize/' . $this->oauthIdentifer . '?username=' . urlencode(IPS_GetLicensee());
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    protected function Call4AccessToken($content)
    {
        $url = 'https://oauth.ipmagic.de/access_token/' . $this->oauthIdentifer;
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        $this->SendDebug(__FUNCTION__, '    content=' . print_r($content, true), 0);

        $statuscode = 0;
        $err = '';
        $jdata = false;

        $time_start = microtime(true);
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($content)
            ]
        ];
        $context = stream_context_create($options);
        $cdata = @file_get_contents($url, false, $context);
        $duration = round(microtime(true) - $time_start, 2);
        $httpcode = 0;
        if ($cdata == false) {
            $this->LogMessage('file_get_contents() failed: url=' . $url . ', context=' . print_r($context, true), KL_WARNING);
            $this->SendDebug(__FUNCTION__, 'file_get_contents() failed: url=' . $url . ', context=' . print_r($context, true), 0);
        } elseif (!isset($http_response_header[0]) && preg_match('/HTTP\/[0-9\.]+\s+([0-9]*)/', $http_response_header[0], $r)) {
            $this->LogMessage('missing http_response_header, cdata=' . $cdata, KL_WARNING);
            $this->SendDebug(__FUNCTION__, 'missing http_response_header, cdata=' . $cdata, 0);
        } else {
            $httpcode = $r[1];
        }
        $this->SendDebug(__FUNCTION__, ' => httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);
        $this->SendDebug(__FUNCTION__, '    cdata=' . $cdata, 0);

        if ($httpcode != 200) {
            if ($httpcode == 401) {
                $statuscode = IS_UNAUTHORIZED;
                $err = 'got http-code ' . $httpcode . ' (unauthorized)';
            } elseif ($httpcode == 403) {
                $statuscode = IS_FORBIDDEN;
                $err = 'got http-code ' . $httpcode . ' (forbidden)';
            } elseif ($httpcode == 409) {
                $data = $cdata;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $statuscode = IS_HTTPERROR;
                $err = 'got http-code ' . $httpcode;
            }
        } elseif ($cdata == '') {
            $statuscode = IS_NODATA;
            $err = 'no data';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = IS_INVALIDDATA;
                $err = 'malformed response';
            } else {
                if (!isset($jdata['refresh_token'])) {
                    $statuscode = IS_INVALIDDATA;
                    $err = 'malformed response';
                }
            }
        }
        if ($statuscode) {
            $this->SendDebug(__FUNCTION__, '    statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->SetStatus($statuscode);
            return false;
        }
        return $jdata;
    }

    private function FetchRefreshToken($code)
    {
        $this->SendDebug(__FUNCTION__, 'code=' . $code, 0);
        $jdata = $this->Call4AccessToken(['code' => $code]);
        if ($jdata == false) {
            $this->SendDebug(__FUNCTION__, 'got no token', 0);
            $this->SetBuffer('ApiAccessToken', '');
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        $access_token = $jdata['access_token'];
        $expiration = time() + $jdata['expires_in'];
        $refresh_token = $jdata['refresh_token'];
        $this->FetchAccessToken($access_token, $expiration);
        return $refresh_token;
    }

    private function FetchAccessToken($access_token = '', $expiration = 0)
    {
        if ($access_token == '' && $expiration == 0) {
            $data = $this->GetBuffer('ApiAccessToken');
            if ($data != '') {
                $jdata = json_decode($data, true);
                $access_token = isset($jtoken['access_token']) ? $jtoken['access_token'] : '';
                $expiration = isset($jtoken['expiration']) ? $jtoken['expiration'] : 0;
                $type = isset($jtoken['type']) ? $jtoken['type'] : CONNECTION_UNDEFINED;
                if ($type == CONNECTION_OAUTH && time() < $expiration) {
                    $this->SendDebug(__FUNCTION__, 'access_token=' . $access_token . ', valid until ' . date('d.m.y H:i:s', $expiration), 0);
                    return $access_token;
                } else {
                    $this->SendDebug(__FUNCTION__, 'access_token expired', 0);
                }
            } else {
                $this->SendDebug(__FUNCTION__, 'access_token not saved', 0);
            }
            $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
            $this->SendDebug(__FUNCTION__, 'refresh_token=' . print_r($refresh_token, true), 0);
            if ($refresh_token == 'False') {
                $this->SendDebug(__FUNCTION__, 'has no refresh_token', 0);
                $this->WriteAttributeString('ApiRefreshToken', '');
                return false;
            }
            if ($refresh_token == '') {
                $this->SendDebug(__FUNCTION__, 'has no refresh_token', 0);
                $this->SetBuffer('ApiAccessToken', '');
                return false;
            }
            $jdata = $this->Call4AccessToken(['refresh_token' => $refresh_token]);
            if ($jdata == false) {
                $this->SendDebug(__FUNCTION__, 'got no access_token', 0);
                $this->SetBuffer('ApiAccessToken', '');
                return false;
            }
            $access_token = $jdata['access_token'];
            $expiration = time() + $jdata['expires_in'];
            if (isset($jdata['refresh_token'])) {
                $refresh_token = $jdata['refresh_token'];
                $this->SendDebug(__FUNCTION__, 'new refresh_token=' . $refresh_token, 0);
                $this->WriteAttributeString('ApiRefreshToken', $refresh_token);
            }
        }
        $this->SendDebug(__FUNCTION__, 'new access_token=' . $access_token . ', valid until ' . date('d.m.y H:i:s', $expiration), 0);
        $jtoken = [
            'access_token' => $access_token,
            'expiration'   => $expiration,
            'type'         => CONNECTION_OAUTH
        ];
        $this->SetBuffer('ApiAccessToken', json_encode($jtoken));
        return $access_token;
    }

    protected function ProcessOAuthData()
    {
        if (!isset($_GET['code'])) {
            $this->SendDebug(__FUNCTION__, 'code missing, _GET=' . print_r($_GET, true), 0);
            $this->SetStatus(IS_NOLOGIN);
            $this->WriteAttributeString('ApiRefreshToken', '');
            return;
        }
        $refresh_token = $this->FetchRefreshToken($_GET['code']);
        $this->SendDebug(__FUNCTION__, 'refresh_token=' . $refresh_token, 0);
        $this->WriteAttributeString('ApiRefreshToken', $refresh_token);
        if ($this->GetStatus() == IS_NOLOGIN) {
            $this->SetStatus(IS_ACTIVE);
        }
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

    protected function GetFormElements()
    {
        $oauth_type = $this->ReadPropertyInteger('OAuth_Type');

        $formElements = [];
        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Instance is disabled'
        ];

        if ($oauth_type == CONNECTION_OAUTH) {
            $instID = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
            if (IPS_GetInstance($instID)['InstanceStatus'] != IS_ACTIVE) {
                $msg = 'Error: Symcon Connect is not active!';
            } else {
                $msg = 'Status: Symcon Connect is OK!';
            }
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $msg
            ];
        }

        $formElements[] = [
            'type'    => 'Select',
            'name'    => 'OAuth_Type',
            'caption' => 'Connection Type',
            'options' => [
                [
                    'caption' => 'Please select a connection type',
                    'value'   => CONNECTION_UNDEFINED
                ],
                [
                    'caption' => 'Netatmo via IP-Symcon Connect',
                    'value'   => CONNECTION_OAUTH
                ],
                [
                    'caption' => 'Netatmo Developer Key',
                    'value'   => CONNECTION_DEVELOPER
                ]
            ]
        ];

        switch ($oauth_type) {
            case CONNECTION_OAUTH:
                $items = [];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'Push "Login at Netatmo" in the action part of this configuration form.'
                ];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'At the webpage from Netatmo log in with your Netatmo username and password.'
                ];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'If the connection to IP-Symcon was successfull you get the message: "Netatmo successfully connected!". Close the browser window.'
                ];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'Return to this configuration form.'
                ];
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => $items,
                    'caption' => 'Netatmo Login'
                ];
                break;
            case CONNECTION_DEVELOPER:
                $items = [];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'Netatmo-Account from https://my.netatmo.com'
                ];
                $items[] = [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Netatmo_User',
                    'caption' => 'Username'
                ];
                $items[] = [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Netatmo_Password',
                    'caption' => 'Password'
                ];
                $items[] = [
                    'type'    => 'Label',
                    'caption' => 'Netatmo-Connect from https://dev.netatmo.com'
                ];
                $items[] = [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Netatmo_Client',
                    'caption' => 'Client ID'
                ];
                $items[] = [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Netatmo_Secret',
                    'caption' => 'Client Secret'
                ];
                $formElements[] = [
                    'type'    => 'ExpansionPanel',
                    'items'   => $items,
                    'caption' => 'Netatmo Access-Details'
                ];
                break;
            default:
                break;
        }

        $items = [];
        $items[] = [
            'type'    => 'Label',
            'caption' => 'Ignore HTTP-Error X times'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'ignore_http_error',
            'caption' => 'Count'
        ];
        $items[] = [
            'type'    => 'Label',
            'caption' => ''
        ];
        $items[] = [
            'type'    => 'Label',
            'caption' => 'Update weatherdata every X minutes'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'UpdateDataInterval',
            'caption' => 'Minutes'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'Call settings'
        ];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $oauth_type = $this->ReadPropertyInteger('OAuth_Type');

        $formActions = [];

        if ($oauth_type == CONNECTION_OAUTH) {
            $formActions[] = [
                'type'    => 'Button',
                'caption' => 'Login at Netatmo',
                'onClick' => 'echo NetatmoWeather_Login($id);'
            ];
        }
        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Clear Token',
            'onClick' => 'NetatmoWeather_ClearToken($id);'
        ];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update weatherdata',
            'onClick' => 'NetatmoWeather_UpdateData($id);'
        ];

        return $formActions;
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('UpdateDataInterval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->SetTimerInterval('UpdateDataWeather', $msec);
    }

    protected function SendData($data)
    {
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($data, true), 0);
        $this->SendDataToChildren(json_encode(['DataID' => '{2D42552F-2545-9145-D3C8-A299E3FDC6EA}', 'Buffer' => $data]));
    }

    public function ForwardData($data)
    {
        if ($this->CheckStatus() == STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $ret = '';
        if (isset($jdata['Function'])) {
            switch ($jdata['Function']) {
                case 'LastData':
                    $ret = $this->GetBuffer('LastData');
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, 'unknown function "' . $jdata['Function'] . '"', 0);
                    break;
                }
        } else {
            $this->SendDebug(__FUNCTION__, 'unknown message-structure', 0);
        }

        $this->SendDebug(__FUNCTION__, 'ret=' . print_r($ret, true), 0);
        return $ret;
    }

    private function GetApiAccessToken()
    {
        $oauth_type = $this->ReadPropertyInteger('OAuth_Type');
        switch ($oauth_type) {
            case CONNECTION_OAUTH:
                $access_token = $this->FetchAccessToken();
                break;
            case CONNECTION_DEVELOPER:
                $url = 'https://api.netatmo.net/oauth2/token';

                $user = $this->ReadPropertyString('Netatmo_User');
                $password = $this->ReadPropertyString('Netatmo_Password');
                $client = $this->ReadPropertyString('Netatmo_Client');
                $secret = $this->ReadPropertyString('Netatmo_Secret');

                $jtoken = json_decode($this->GetBuffer('ApiAccessToken'), true);
                $access_token = isset($jtoken['access_token']) ? $jtoken['access_token'] : '';
                $expiration = isset($jtoken['expiration']) ? $jtoken['expiration'] : 0;
                $type = isset($jtoken['type']) ? $jtoken['type'] : CONNECTION_UNDEFINED;

                if ($type != CONNECTION_DEVELOPER || $expiration < time()) {
                    $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
                    if ($refresh_token == '') {
                        $postdata = [
                            'grant_type'    => 'password',
                            'client_id'     => $client,
                            'client_secret' => $secret,
                            'username'      => $user,
                            'password'      => $password,
                            'scope'         => 'read_station'
                        ];
                    } else {
                        $postdata = [
                            'grant_type'    => 'refresh_token',
                            'client_id'     => $client,
                            'client_secret' => $secret,
                            'refresh_token' => $refresh_token
                        ];
                    }

                    $data = '';
                    $err = '';
                    $statuscode = $this->do_HttpRequest($url, '', $postdata, 'POST', $data, $err);
                    if ($statuscode == 0) {
                        $params = json_decode($data, true);
                        $this->SendDebug(__FUNCTION__, 'params=' . print_r($params, true), 0);
                        if ($params['access_token'] == '') {
                            $statuscode = IS_INVALIDDATA;
                            $err = "no 'access_token' in response";
                        }
                    }

                    if ($statuscode) {
                        $this->LogMessage('url=' . $url . ', statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
                        $this->SendDebug(__FUNCTION__, $err, 0);
                        $this->SetStatus($statuscode);
                        $this->SetMultiBuffer('LastData', '');
                        return false;
                    }

                    $access_token = $params['access_token'];
                    $expires_in = $params['expires_in'];
                    $expiration = time() + $expires_in - 60;
                    $this->SendDebug(__FUNCTION__, 'new access_token=' . $access_token . ', valid until ' . date('d.m.y H:i:s', $expiration), 0);
                    $jtoken = [
                        'access_token' => $access_token,
                        'expiration'   => $expiration,
                        'type'         => CONNECTION_DEVELOPER
                    ];
                    $this->SetBuffer('ApiAccessToken', json_encode($jtoken));

                    if (isset($params['refresh_token'])) {
                        $refresh_token = $params['refresh_token'];
                        $this->SendDebug(__FUNCTION__, 'new refresh_token=' . $refresh_token, 0);
                        $this->WriteAttributeString('ApiRefreshToken', $refresh_token);
                    }

                    $this->SetStatus(IS_ACTIVE);
                }
                break;
            default:
                $access_token = false;
                break;
        }
        return $access_token;
    }

    public function UpdateData()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        if ($inst['InstanceStatus'] == IS_INACTIVE) {
            $this->SetTimerInterval('UpdateDataWeather', 0);
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, '', 0);
        $access_token = $this->GetApiAccessToken();
        if ($access_token == false) {
            $this->SetTimerInterval('UpdateDataWeather', 0);
            return;
        }

        // Anfrage mit Token
        $url = 'https://api.netatmo.net/api/getstationsdata';
        $url .= '?access_token=' . $access_token;

        $data = '';
        $err = '';
        $statuscode = $this->do_HttpRequest($url, '', '', 'GET', $data, $err);
        if ($statuscode == 0) {
            $jdata = json_decode($data, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
            $status = $jdata['status'];
            if ($status != 'ok') {
                $err = 'got status "' . $status . '"';
                $statuscode = IS_INVALIDDATA;
            } else {
                $empty = true;
                if (isset($jdata['body']['devices'])) {
                    $devices = $jdata['body']['devices'];
                    if ($devices != '' && count($devices)) {
                        $empty = false;
                    }
                }
                if ($empty) {
                    $err = 'data contains no station';
                    $statuscode = IS_NOSTATION;
                }
            }
        } elseif ($statuscode == IS_FORBIDDEN) {
            $this->SetBuffer('ApiAccessToken', '');
        }

        if ($statuscode) {
            $this->LogMessage('url=' . $url . ', statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->SetStatus($statuscode);
            $this->SetBuffer('LastData', '');
            return;
        }

        $this->SendData($data);
        $this->SetBuffer('LastData', $data);

        $this->SetUpdateInterval();
        $this->SetStatus(IS_ACTIVE);
    }

    private function do_HttpRequest($url, $header, $postdata, $mode, &$data, &$err)
    {
        $this->SendDebug(__FUNCTION__, 'http-' . $mode . ': url=' . $url, 0);
        $time_start = microtime(true);

        if ($header != '') {
            $this->SendDebug(__FUNCTION__, '    header=' . print_r($header, true), 0);
        }
        if ($postdata != '') {
            $this->SendDebug(__FUNCTION__, '    postdata=' . print_r($postdata, true), 0);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        switch ($mode) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
                break;
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $cdata = curl_exec($ch);
        $cerrno = curl_errno($ch);
        $cerror = $cerrno ? curl_error($ch) : '';
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);
        $this->SendDebug(__FUNCTION__, '    cdata=' . $cdata, 0);

        $statuscode = 0;
        $err = '';
        $data = '';

        if ($cerrno) {
            $statuscode = IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 401) {
                $statuscode = IS_UNAUTHORIZED;
                $err = 'got http-code ' . $httpcode . ' (unauthorized)';
            } elseif ($httpcode == 403) {
                $statuscode = IS_FORBIDDEN;
                $err = 'got http-code ' . $httpcode . ' (forbidden)';
            } elseif ($httpcode == 409) {
                $data = $cdata;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $statuscode = IS_HTTPERROR;
                $err = 'got http-code ' . $httpcode;
            }
        } elseif ($cdata == '') {
            $statuscode = IS_NODATA;
            $err = 'no data';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = IS_INVALIDDATA;
                $err = 'malformed response';
            } else {
                $data = $cdata;
            }
        }

        $this->SendDebug(__FUNCTION__, '    statuscode=' . $statuscode . ', err=' . $err, 0);
        $this->SendDebug(__FUNCTION__, '    data=' . $data, 0);
        return $statuscode;
    }

    public function ClearToken()
    {
        $refresh_token = $this->ReadAttributeString('ApiRefreshToken');
        $this->SendDebug(__FUNCTION__, 'clear refresh_token=' . $refresh_token, 0);
        $this->WriteAttributeString('ApiRefreshToken', '');

        $access_token = $this->GetApiAccessToken();
        $this->SendDebug(__FUNCTION__, 'clear access_token=' . $access_token, 0);
        $this->SetBuffer('ApiAccessToken', '');
    }
}
