<?php

declare(strict_types=1);

trait NetatmoWeatherLocalLib
{
    public static $IS_NODATA = IS_EBASE + 10;
    public static $IS_UNAUTHORIZED = IS_EBASE + 11;
    public static $IS_FORBIDDEN = IS_EBASE + 12;
    public static $IS_SERVERERROR = IS_EBASE + 13;
    public static $IS_HTTPERROR = IS_EBASE + 14;
    public static $IS_INVALIDDATA = IS_EBASE + 15;
    public static $IS_NOSTATION = IS_EBASE + 16;
    public static $IS_STATIONMISSІNG = IS_EBASE + 17;
    public static $IS_NOLOGIN = IS_EBASE + 18;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_NODATA, 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => self::$IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (unauthorized)'];
        $formStatus[] = ['code' => self::$IS_FORBIDDEN, 'icon' => 'error', 'caption' => 'Instance is inactive (forbidden)'];
        $formStatus[] = ['code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => self::$IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => self::$IS_NOSTATION, 'icon' => 'error', 'caption' => 'Instance is inactive (no station)'];
        $formStatus[] = ['code' => self::$IS_STATIONMISSІNG, 'icon' => 'error', 'caption' => 'Instance is inactive (station missing)'];
        $formStatus[] = ['code' => self::$IS_NOLOGIN, 'icon' => 'error', 'caption' => 'Instance is inactive (not logged in)'];

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            case self::$IS_NODATA:
            case self::$IS_UNAUTHORIZED:
            case self::$IS_FORBIDDEN:
            case self::$IS_SERVERERROR:
            case self::$IS_HTTPERROR:
            case self::$IS_INVALIDDATA:
                $class = self::$STATUS_RETRYABLE;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    public static $CONNECTION_UNDEFINED = 0;
    public static $CONNECTION_OAUTH = 1;
    public static $CONNECTION_DEVELOPER = 2;

    public static $WIFI_BAD = 0;
    public static $WIFI_AVERAGE = 1;
    public static $WIFI_GOOD = 2;
    public static $WIFI_HIGH = 3;

    public static $SIGNAL_STATUS_VERYLOW = 0;
    public static $SIGNAL_STATUS_LOW = 1;
    public static $SIGNAL_STATUS_MEDIUM = 2;
    public static $SIGNAL_STATUS_HIGH = 3;
    public static $SIGNAL_STATUS_FULL = 4;

    public static $BATTERY_EMPTY = 0;
    public static $BATTERY_LOW = 1;
    public static $BATTERY_MEDIUM = 2;
    public static $BATTERY_HIGH = 3;
    public static $BATTERY_FULL = 4;
    public static $BATTERY_MAX = 5;

    public static $TREND_DOWN = -1;
    public static $TREND_STABLE = 0;
    public static $TREND_UP = 1;

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $this->CreateVarProfile('Netatmo.Temperatur', VARIABLETYPE_FLOAT, ' °C', -25, 45, 0, 1, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Netatmo.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 100, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Netatmo.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 0, 80, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Netatmo.Dewpoint', VARIABLETYPE_FLOAT, ' °C', -10, 40, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Netatmo.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 60, 0, 0, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Netatmo.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Netatmo.WindSpeed', VARIABLETYPE_FLOAT, ' km/h', 0, 150, 0, 0, 'WindSpeed', [], $reInstall);
        $this->CreateVarProfile('Netatmo.WindStrength', VARIABLETYPE_INTEGER, ' bft', 0, 13, 0, 0, 'WindSpeed', [], $reInstall);
        $this->CreateVarProfile('Netatmo.WindAngle', VARIABLETYPE_INTEGER, ' °', 0, 360, 0, 0, 'WindDirection', [], $reInstall);
        $this->CreateVarProfile('Netatmo.WindDirection', VARIABLETYPE_STRING, '', 0, 0, 0, 0, 'WindDirection', [], $reInstall);
        $this->CreateVarProfile('Netatmo.Rainfall', VARIABLETYPE_FLOAT, ' mm', 0, 60, 0, 1, 'Rainfall', [], $reInstall);

        $associations = [
            ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x008040],
            ['Wert' => 40, 'Name' => '%d', 'Farbe' => 0xFFFF31],
            ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xFF8000],
            ['Wert' => 95, 'Name' => '%d', 'Farbe' => 0xFF0000],
        ];
        $this->CreateVarProfile('Netatmo.Noise', VARIABLETYPE_INTEGER, ' dB', 0, 130, 0, 1, 'Speaker', $associations, $reInstall);

        $associations = [
            ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000],
            ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00],
            ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000],
            ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000],
        ];
        $this->CreateVarProfile('Netatmo.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations, $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('No'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('Yes'), 'Farbe' => 0xEE0000],
        ];
        $this->CreateVarProfile('Netatmo.Alarm', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 1, 'Alert', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$WIFI_BAD, 'Name' => $this->Translate('bad'), 'Farbe' => 0xEE0000],
            ['Wert' => self::$WIFI_AVERAGE, 'Name' => $this->Translate('average'), 'Farbe' => 0xFFFF00],
            ['Wert' => self::$WIFI_GOOD, 'Name' => $this->Translate('good'), 'Farbe' => 0x32CD32],
            ['Wert' => self::$WIFI_HIGH, 'Name' => $this->Translate('high'), 'Farbe' => 0x228B22],
        ];
        $this->CreateVarProfile('Netatmo.Wifi', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$SIGNAL_STATUS_VERYLOW, 'Name' => $this->Translate('very low'), 'Farbe' => 0xEE0000],
            ['Wert' => self::$SIGNAL_STATUS_LOW, 'Name' => $this->Translate('low'), 'Farbe' => 0xFFA500],
            ['Wert' => self::$SIGNAL_STATUS_MEDIUM, 'Name' => $this->Translate('medium'), 'Farbe' => 0xFFFF00],
            ['Wert' => self::$SIGNAL_STATUS_HIGH, 'Name' => $this->Translate('high'), 'Farbe' => 0x32CD32],
            ['Wert' => self::$SIGNAL_STATUS_FULL, 'Name' => $this->Translate('full'), 'Farbe' => 0x228B22],
        ];
        $this->CreateVarProfile('Netatmo.RfSignal', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$BATTERY_EMPTY, 'Name' => $this->Translate('empty'), 'Farbe' => 0xEE0000],
            ['Wert' => self::$BATTERY_LOW, 'Name' => $this->Translate('low'), 'Farbe' => 0xFFA500],
            ['Wert' => self::$BATTERY_MEDIUM, 'Name' => $this->Translate('medium'), 'Farbe' => 0xFFFF00],
            ['Wert' => self::$BATTERY_HIGH, 'Name' => $this->Translate('high'), 'Farbe' => 0x32CD32],
            ['Wert' => self::$BATTERY_FULL, 'Name' => $this->Translate('full'), 'Farbe' => 0x228B22],
            ['Wert' => self::$BATTERY_MAX, 'Name' => $this->Translate('max'), 'Farbe' => 0x228B22],
        ];
        $this->CreateVarProfile('Netatmo.Battery', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$TREND_DOWN, 'Name' => $this->Translate('down'), 'Farbe' => -1],
            ['Wert' => self::$TREND_STABLE, 'Name' => $this->Translate('stable'), 'Farbe' => -1],
            ['Wert' => self::$TREND_UP, 'Name' => $this->Translate('up'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('Netatmo.Trend', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);
    }
}
