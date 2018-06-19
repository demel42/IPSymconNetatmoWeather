# IPSymconNetatmoWeather

[![Version](https://img.shields.io/badge/Symcon_Version-4.4+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Version](https://img.shields.io/badge/Modul_Version-1.0-blue.svg)
![Version](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/123791865/shield?branch=master)](https://github.styleci.io/repos/123791865)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)

## 1. Funktionsumfang

Es werden die Wetter-Daten von einer Netatmo-Wetterstation ausgelesen und gespeichert. Es werden alle Module unterstützt in der maximalen Ausbaustufe: Basis-, Außen- und 3 Innenmodule sowie Wind- und Regenmesser.

Jedes Netatmo-Modul ist eine eigene Instanz, zusätzlich gibt es eine Instanz, die die Station an sich repräsentiert.

Zusätzlich
 - werden einige Status-Information ermittelt, unter anderen Status der Kommunikation mit Netatmo und Wunderground, Batterie- und Modul-Alarme
 - weitere (im wesentlichen modulbezogene) Daten werden sowohl in einer HTML-Box aufbereitet als auch als JSON-Struktur in einer Variable zur Verfügung gestellt
 - optional einige modulbezogene Daten in Variablen zur Verfügung gestellt
 - es können zusätzliche Wetter-Kenndaten berechnet werden: absoluter Luftdruck, Taupunkt, absolute Feuchte, Windchill, Heatindex, Windstärke ...
 - werden die geographіsche Position sowie die Höhe der Wetterstation von Netatmo übernommen und in die Instanz-Konfiguration als Property eingetragen
 - steht ein WebHook zur Verfügung, bei dem mit _/hook/NetatmoWeather/status_ die Status-Information (analog zur HTML-Box) als Webseite abgerufen werden können.

Die Angabe der Netatmo-Zugangsdaten ist obligatorisch damit die Instanz aktiviert werden kann.

Weiterhin können die relevanten Wetterdaten in eine persönliche Wetterstation von Wunderground übertragen werden.
Übertragen wird:
 - Innenmodul: Luftdruck
 - Aussenmodut: Temperatur, Luftfeuchtigkeit und der daraus berechnete Taupunkt
 - Regenmodul: 1h-Wert und Gesamtmenge von heute (ab Mitternacht)
 - Windmesser: Windgeschwindigkeit und -richtung sowie Geschwindigkeit und Richtung von Böen

Hinweis: Wunderground gibt an, das Daten von Netatmo automatisch übernommen werden, meine Erfahrung ist aber, das das sehr unzuverlässig funktioniert (immer wieder lange Phasen ohne übertragung oder die Station taucht plötzlich unter anderem Namen auf) und zudem erfolgt meiner Beobachtung nach die Übertragung nur einmal am Tag.

## 2. Voraussetzungen

 - IP-Symcon ab Version 4.4
 - Netatmo Wetterstation und ein entsprechenden Account bei Netatmo.

   Es wird sowohl der "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com) als auch einen Account sowie eine "App" bei Netatmo Connect benötigt, um die Werte abrufen zu können (https://dev.netatmo.com).

 - optional ein Account bei Wunderground für eine "Personal-Weather-Station"
   hierzu muss man bei Wunderground ein Konto anlegen und eine eine Wettersttaion einrichten.

   Die von Wunderground angegebene Verknüpfung mit Netatmo über den Wunderground-Support ist nicht erforderlich.

## 3. Installation

### a. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconNetatmoWeather.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun unterhalb von _I/O Instanzen_ die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Netatmo_ und als Gerät _NetatmoWeather I/O_ auswählen.

In dem Konfigurationsdialog die Netatmo-Zugangsdaten eintragen.

Dann unter _Konfigurator Instanzen_ analog den Konfigurator _NetatmoWeather Konfigurator_ hinzufügen.

Hier werden alle Stationen, die mit dem, in der I/O-Instanz angegebenen, Netatmo-Konto verknüpft sind, angeboten; aus denen wählt man eine aus.

Mit Betätigen der Schaltfläche _Importieren der Station_ werden für jedes Modul, das zu dieser Station im Netatmo registriert ist, eine Geräte-Instanzen unterhalb von _IP-Symcon_ angelegt.
Der Namen der Instanzen ist der der Netatmo-Module, in derm Feld _Beschreibung_ der Instanzen ist der Modultyp sowie der Namen der Station und des Moduls eingetragen.

Der Aufruf des Konfigurators kann jederzeit wiederholt werden, es werden dann fehlende Module angelegt.

Die Module werden aufgrund der internen _ID_ der Module identifiziert, d.h. eine Änderung des Modulnamens muss in IPS nachgeführt werden.
Ein Ersatz eines Moduls wird beim Aufruf des Konfigurators dazuführen, das eine weitere Instanz angelegt wird.

Die im Netatmo eingetragenen Höhe der Station sowie die geographische Position wird als Property zu dem virtuellen Modul _Station_ eingetragen.

Zu den Geräte-Instanzen werden im Rahmen der Konfiguration Modultyp-abhängig Variablen angelegt. Zusätzlich kann man in den Modultyp-spezifischen Konfigurationsdialog weitere Variablen aktivieren.

Die Instanzen können dann in gewohnter Weise im Objektbaum frei positioniert werden.

## 4. Funktionsreferenz

### zentrale Funktion

`UpdateData(int $InstanzID)`

ruft die Daten der Netatmo-Wetterstation ab und aktualisiert optional dien Wundergrund-PWS. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

### Hilfsfunktionen

`float NetatmoWeatherDevice_CalcAbsoluteHumidity(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) die absulte Feuchte (in g/m³)


`float NetatmoWeatherDevice_CalcAbsolutePressure(int $InstanzID, float $Pressure, $Temperatur, int $Altitude)`

berechnet aus dem relativen Luftdruck (in mbar) und der Temperatur (in °C) und Höhe (in m) der absoluten Luftdruck (in mbar)
ist die Höhe nicht angegeben, wird die Höhe der Netatmo-Wettersttaion verwendet


`float NetatmoWeatherDevice_CalcDewpoint(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Taupunkt (in °C)


`float NetatmoWeatherDevice_CalcHeatindex(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Hitzeindex (in °C)


`float NetatmoWeatherDevice_CalcWindchill(int $InstanzID, float $Temperatur, float $WindSpeed)`

berechnet aus der Temperatur (in °C) und der Windgeschwindigkeit (in km/h) den Windchill (Windkühle) (in °C)


`string NetatmoWeatherDevice_ConvertWindDirection2Text(int $InstanzID, int $WindDirection)`

ermittelt aus der Windrichtung (in °) die korespondierende Bezeichnung auf der Windrose


`int NetatmoWeatherDevice_ConvertWindSpeed2Strength(int $InstanzID, float $WindSpeed)`

berechnet aus der Windgeschwindigkeit (in km/h) die Windstärke (in bft)


`string NetatmoWeatherDevice_ConvertWindStrength2Text(int $InstanzID, int $WindStrength)`

ermittelt aus der Windstärke (in bft) die korespondierende Bezeichnung gemäß Beaufortskala

`string NetatmoWeatherDevice_GetRawData(int $InstanzID)`

liefert die Zusatzdaten, die nicht in den Variablen gespeichert sind und zu Darstellung der HTML-Box bzw WebHook verwendet werden

Datenstruktur (muss mit json_decode() aufbereitet werden):

- _station_: Informationen zu einer Wetterstation

| Attribut        | Datentyp                | Bedeutung                               |
| :-------------: | :---------------------: | :-------------------------------------: |
| last_query      | UNIX-Timestamp          | Zeitpunkt der Abfrage                   |
| status          | string                  | Status (_ok_ oder ein Fehler)           |
| last_contact    | UNIX-Timestamp          | Zeitpunkt der letzten Datenübertragung  |
| station_name    | string                  | Benutzerbezeichnung der Station         |
| modules         | array von _module_      | die Module der Station                  |

- _module_: Informationen zu einem Modul

| Attribut           | Datentyp                | Bedeutung                               |
| :----------------: | :---------------------: | :-------------------------------------: |
| module_type        | string                  | Typ des Modules (_NAMain_, _NAModule1_, _NAModule2_, _NAModule3_, _NAModule4_) |
| module_type_txt    | string                  |  ... als Text                           |
| module_type_img    | string                  |  ... als Pfad zum Icon                  |
| module_name        | string                  | Bezeichnung des Moduls                  |
| last_measure       | UNIX-Timestamp          | Zeitpunkt der letzten Messung           |
| last_message       | UNIX-Timestamp          | Zeitpunkt der letzten Meldung des Moduls bei der Basis |
| wifi_status        | integer                 | Wifi (_nur NAMain_)                     |
| wifi_status_txt    | string                  |  ... als Text                           |
| wifi_status_img    | string                  |  ... als Pfad zum Icon                  |
| rf_status          | integer                 | Funk (nicht _NAMain_)                   |
| rf_status_txt      | string                  |  ... als Text                           |
| rf_status_img      | string                  |  ... als Pfad zum Icon                  |
| battery_status     | integer                 | Status der Batterie (nicht _NAMain_)    |
| battery_status_txt | string                  |  ... als Text                           |
| battery_status_img | string                  |  ... als Pfad zum Icon                  |

Die gelieferte Struktur ist _station_; kein Array, weil es immer nur um eine bestimmte Station geht.

## 5. Konfiguration

### I/O-Modul

#### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| Netatmo-Zugangsdaten      | string   |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
|                           |          |              |                                            |
| UpdateDataInterval        | integer  | 5            | Angabe in Minuten                          |

Hinweis zum Intervall: die Daten werden nur ca. alle 10m von der Wetterstation an Netatmo übertragen, ein minütliches Intervall ist zulässig, macht aber nur begrenzt Sinn.
Bei einer Angabe von 5m sind die Werte nicht älter als 15m.

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Aktualisiere Wetterdaten     | führt eine sofortige Aktualisierung durch |

### Konfigurator

#### Auswahl

Es werden alle Stationen zu dem konfigurierten Account zur Auswahl angeboten. Es muss allerdings nur eine Auswahl getroffen werden, wenn es mehr als eine Station gibt.

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Import der Station           | richtet die Geräte-Instanzen ein |

### Geräte

#### Properties

werden vom Konfigurator beim Anlegen der Instanz gesetzt.

| Eigenschaft            | Typ     | Standardwert | Beschreibung                               |
| :--------------------: | :-----: | :----------: | :----------------------------------------: |
| station_id             | string  |              | ID der Station                             |
| module_id              | string  |              | ID des Moduls                              |
| module_type            | string  |              | Typ des Moduls                             |

_module_type_: _NAMain_=Basis, _NAModule1_=Außen, _NAModule2_=Wind, _NAModule3_=Regen, _NAModule4_=Innen sowie _Station_, die für die Netatmo-Station als Ganzes steht.

#### Variablen

stehen je nach Typ des Moduls zur Verfügung

| Eigenschaft               | Typ     | Standardwert | Beschreibung                               |
| :-----------------------: | :-----: | :----------: | :----------------------------------------: |
| altitude                  | float   |              | Höhe der Station                           |
| longitude                 | float   |              | Längengrad der Station                     |
| latitude                  | float   |              | Breitengrad der Station                    |
|                           |         |              |                                            |
| with_absolute_humidity    | boolean | false        | absolute Luftfeuchtigkeit                  |
| with_absolute_pressure    | boolean | false        | absoluter Luftdruck                        |
| with_battery              | boolean | false        | Batterie-Status                            |
| with_dewpoint             | boolean | false        | Taupunkt                                   |
| with_heatindex            | boolean | false        | Hitzeindex                                 |
| with_last_contact         | boolean | false        | letzte Übertragung an Netatmo              |
| with_minmax               | boolean | false        | Ausgabe von Min/Max-Wert (Temperatur, Wind) |
| with_last_measure         | boolean | false        | Messzeitpunkt                              |
| with_signal               | boolean | false        | Wifi-/RF-Signalstärke                      |
| with_status_box           | boolean | false        | HTML-Box mit Status der Station und Module |
| with_trend                | boolean | false        | Ausgabe des Trend (Temperatur, Luftdruck)  |
| with_windangle            | boolean | true         | Windrichtung in Grad                       |
| with_windchill            | boolean | false        | Windchill (Windkühle)                      |
| with_winddirection        | boolean | false        | Windrichtung mit Text                      |
| with_windstrength         | boolean | false        | Windstärke                                 |
|                           |         |              |                                            |
| statusbox_script          | integer | 0            | Script zum Füllen der Variable _StatusBox_ |
| webhook_script            | integer | 0            | Script zur Verwendung im WebHook           |
|                           |         |              |                                            |
| minutes2fail              | integer | 30           | Dauer, bis die Kommunikation als gestört gilt |
|                           |         |              |                                            |
| Wunderground-Zugangsdaten | string  |              | Station-ID und -Key von https://www.wunderground.com/personal-weather-station/mypws |

Das hier angebbare Minuten-Intervall dient zu Überprüfung der Kommunikation zwischen
 - den Modulen und dem Basismodul
 - dem Basismodul und dem Netatmo-Server
  ist die Zeit überschritten, wird die Variable _Status_ des Basismoduls auf Fehler gesetzt.
  Anmerkung: die Variable _Status_ wird auch auf Fehler gesetzt wenn das IO-Modul einen Fehler feststellt.

Erläuterung zu _statusbox_script_, _webhook_script_:
mit diesen Scripten kann man eine alternative Darstellung realisieren.

Ein passendes Code-Fragment für ein Script:

```
$data = NetatmoWeatherDevice_GetRawData($_IPS['InstanceID']);
if ($data) {
	$station = json_decode($r,true);
	...
	echo $result;
}
```
Die Beschreibung der Struktur siehe _NetatmoWeatherDevice_GetRawData()_.

Beispiel in module.php sind _Build_StatusBox()_ und _ProcessHook_Status()_.

### Statusvariablen

folgende Variable werden angelegt, zum Teil optional

| Name                    | Typ            | Beschreibung                                    | Option                 | Module    |
| :---------------------: | :------------: | :---------------------------------------------: | :--------------------: | :-------: |
| AbsoluteHumidity        | float          | absolute Luftfeuchtigkeit                       | with_absolute_humidity | B,A,I     |
| AbsolutePressure        | float          | absoluter Luftdruck                             | with_absolute_pressure | B         |
| BatteryAlarm            | boolean        | Batterie-Zustand eines oder mehrere Module      |                        | B         |
| Battery                 | integer        | Batterie-Status                                 | with_battery           | A,W,R,I   |
| CO2                     | integer        | CO2                                             |                        | B,I       |
| Dewpoint                | float          | Taupunkt                                        | with_dewpoint          | B,A,I     |
| GustAngle               | integer        | Richtung der Böen der letzten 5m                | with_windangle         | W         |
| GustDirection           | string         | Richtung der Böen der letzten 5m                | with_winddirection     | W         |
| GustMaxAngle            | integer        | Richtung der stärksten heutigen Böe             | with_minmax + with_windangle    | W         |
| GustMaxDirection        | string         | Richtung der stärksten heutigen Böe             | with_minmax + with_winddirection | W         |
| GustMaxSpeed            | float          | Geschwindigkeit der stärksten heutigen Böe      |                        | W         |
| GustMaxStrength         | integer        | Stärke der stärksten heutigen Böe               | with_minmax + with_windstrength | W         |
| GustMaxTimestamp        | UNIX-Timestamp | Zeitpunkt der stärksten heutigen Böe            | with_minmax            | W         |
| GustSpeed               | float          | Geschwindigkeit der Böen der letzten 5m         |                        | W         |
| GustStrength            | integer        | Stärke der Böen der letzten 5m                  | with_windstrength      | W         |
| Heatindex               | float          | Hitzeindex                                      | with_heatindex         | B,A,I     |
| Humidity                | float          | Luftfeuchtigkeit                                |                        | B,A,I     |
| LastContact             | UNIX-Timestamp | letzte Übertragung                              | with_last_contact      | B         |
| LastMeasure             | UNIX-Timestamp | letzte Messung                                  | with_last_measure      | B,A,W,R,I |
| ModuleAlarm             | boolean        | Station oder Module kommunizieren nicht         |                        | B         |
| Noise                   | integer        | Lärm                                            |                        | B         |
| Pressure                | float          | Luftdruck                                       |                        | B         |
| PressureTrend           | integer        | Trend des Luftdrucks                            | with_trend             | B         |
| Rain_1h                 | float          | Regenmenge der letzten Stunde                   |                        | R         |
| Rain_24h                | float          | Regenmenge von heute                            |                        | R         |
| Rain                    | float          | Regenmenge                                      |                        | R         |
| RfSignal                | integer        | Signal-Stärke                                   | with_signal            | A,W,R,I   |
| Status                  | boolean        | Status                                          |                        | B         |
| StatusBox               | string         | Status der Station und der Module               | with_status_box        | B         |
| Temperature             | float          | Temperatur                                      |                        | B,A,I     |
| TemperatureMax          | float          | heutiges Temperatur-Maximum                     | with_minmax            | B,A,I     |
| TemperatureMaxTimestamp | UNIX-Timestamp | Zeitpunkt des heutigen Temperatur-Maximums      | with_minmax            | B,A,I     |
| TemperatureMin          | float          | heutiges Temperatur-Minimum                     | with_minmax            | B,A,I     |
| TemperatureMinTimestamp | UNIX-Timestamp | Zeitpunkt des heutigen Temperatur-Minimums      | with_minmax            | B,A,I     |
| TemperatureTrend        | integer        | Trend der Temperatur                            | with_trend             | B,A,I     |
| Wifi                    | integer        | Stärke des Wifi-Signals                         | with_signal            | B         |
| WindAngle               | integer        | Windrichtung                                    | with_windangle         | W         |
| Windchill               | float          | Windchill                                       | with_windchill         | A         |
| WindDirection           | string         | Windrichtung                                    | with_winddirection     | W         |
| WindSpeed               | float          | Windgeschwindigkeit                             |                        | W         |
| WindStrength            | integer        | Windstärke                                      | with_windstrength      | W         |
| Wunderground            | boolean        | Status der Übertragung an Wunderground          | wunderground_id        | B         |


_Module_: B=Basis, A=Außen, W=Wind, R=Regen, I=Innen

### Variablenprofile

Es werden folgende Variableprofile angelegt:
* Boolean<br>
Netatmo.Alarm

* Integer<br>
Netatmo.Battery, Netatmo.CO2, Netatmo.Noise, Netatmo.RfSignal, Netatmo.Trend, Netatmo.Wifi, Netatmo.WindAngle, Netatmo.WindStrength

* Float<br>
Netatmo.absHumidity, Netatmo.Dewpoint, Netatmo.Heatindex, Netatmo.Humidity, Netatmo.Pressure, Netatmo.Rainfall, Netatmo.Temperatur, Netatmo.WindSpeed

* String<br>
Netatmo.WindDirection

## 6. Anhang

GUIDs
- Modul: `{0F675628-33AE-88E8-D9C4-9A2D1C7FE394}`
- Instanzen:
  - NetatmoWeatherIO: `{26A55798-5CBC-88F6-5C7B-370B043B24F9}`
  - NetatmoWeatherConfig: `{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}`
  - NetatmoWeatherDevice: `{1023DB4A-D491-A0D5-17CD-380D3578D0FA}`
- Nachrichten:
  - `{DC5A0AD3-88A5-CAED-3CA9-44C20CC20610}`: an NetatmoWeatherIO
  - `{2D42552F-2545-9145-D3C8-A299E3FDC6EA}`: an NetatmoWeatherConfig, NetatmoWeatherDevice
