<?php

declare(strict_types=1);

if (!defined('CONNECTION_UNDEFINED')) {
    define('CONNECTION_UNDEFINED', 0);
    define('CONNECTION_OAUTH', 1);
    define('CONNECTION_DEVELOPER', 2);
}

if (!defined('STATUS_INVALID')) {
    define('STATUS_INVALID', 0);
    define('STATUS_VALID', 1);
    define('STATUS_RETRYABLE', 2);
}

trait NetatmoWeatherLocalLib
{
    public static $IS_NODATA = IS_EBASE + 1;
    public static $IS_UNAUTHORIZED = IS_EBASE + 2;
    public static $IS_FORBIDDEN = IS_EBASE + 3;
    public static $IS_SERVERERROR = IS_EBASE + 4;
    public static $IS_HTTPERROR = IS_EBASE + 5;
    public static $IS_INVALIDDATA = IS_EBASE + 6;
    public static $IS_NOSTATION = IS_EBASE + 7;
    public static $IS_STATIONMISSІNG = IS_EBASE + 8;
    public static $IS_INVALIDCONFIG = IS_EBASE + 9;
    public static $IS_NOSYMCONCONNECT = IS_EBASE + 10;
    public static $IS_NOLOGIN = IS_EBASE + 11;

    private function GetFormStatus()
    {
        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => self::$IS_NODATA, 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => self::$IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (unauthorized)'];
        $formStatus[] = ['code' => self::$IS_FORBIDDEN, 'icon' => 'error', 'caption' => 'Instance is inactive (forbidden)'];
        $formStatus[] = ['code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => self::$IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => self::$IS_NOSTATION, 'icon' => 'error', 'caption' => 'Instance is inactive (no station)'];
        $formStatus[] = ['code' => self::$IS_STATIONMISSІNG, 'icon' => 'error', 'caption' => 'Instance is inactive (station missing)'];
        $formStatus[] = ['code' => self::$IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid config)'];
        $formStatus[] = ['code' => self::$IS_NOSYMCONCONNECT, 'icon' => 'error', 'caption' => 'Instance is inactive (no Symcon-Connect)'];
        $formStatus[] = ['code' => self::$IS_NOLOGIN, 'icon' => 'error', 'caption' => 'Instance is inactive (not logged in)'];

        return $formStatus;
    }

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = STATUS_VALID;
                break;
            case self::$IS_NODATA:
            case self::$IS_UNAUTHORIZED:
            case self::$IS_FORBIDDEN:
            case self::$IS_SERVERERROR:
            case self::$IS_HTTPERROR:
            case self::$IS_INVALIDDATA:
                $class = STATUS_RETRYABLE;
                break;
            default:
                $class = STATUS_INVALID;
                break;
        }

        return $class;
    }
}