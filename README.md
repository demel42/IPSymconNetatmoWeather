# IPSymconNetatmoWeather

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

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

 - IP-Symcon ab Version 5.3<br>
   Version 4.4 mit Branch _ips_4.4_ (nur noch Fehlerkorrekturen)
 - eine Netatmo Wetterstation
 - den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com)
 - IP-Symcon Connect<br>
   **oder**<br>
 - einen Account sowie eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com)<br>
   Achtung: diese App ist nur für den Zugriff auf Netatmo-Weather-Produkte gedacht; das Modul benutzt den Scope _read_station_.
   Eine gleichzeitige Benutzung der gleichen Netatmo-App für andere Bereiche (z.B. Security) stört sich gegenseitig.

 - optional ein Account bei Wunderground für eine "Personal-Weather-Station"
   hierzu muss man bei Wunderground ein Konto anlegen und eine eine Wettersttaion einrichten.

   Die von Wunderground angegebene Verknüpfung mit Netatmo über den Wunderground-Support ist nicht erforderlich.

## 3. Installation

### a. Laden des Moduls

**Installieren über den Module-Store**

Die Webconsole von IP-Symcon mit http://<IP-Symcon IP>:3777/console/ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

Im Suchfeld nun NetatmoWeather eingeben, das Modul auswählen und auf Installieren drücken.

**Installieren über die Modules-Instanz**

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconNetatmoWeather.git`

und mit _OK_ bestätigen. Ggfs. auf anderen Branch wechseln (Modul-Eintrag editieren, _Zweig_ auswählen).

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

#### NetatmoWeatherIO

In IP-Symcon nun unterhalb von _I/O Instanzen_ die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Netatmo_ und als Gerät _NetatmoWeather I/O_ auswählen.

In dem Konfigurationsformular nun den gewünschten Zugang wählen, entweder als Nutzer über IP-Symcon Connect oder als Entwickler mit eigenem Entwicklerschlüssel.

**Zugriff mit Netatmo-Benutzerdaten über IP-Symcon Connect**

Hierzu _bei Netatmo anmelden_ auswählen. Es öffnet sich ein Browserfenster mit der Anmeldeseite von Netatmo; hier bitte anmelden. Dann erscheint ein weiteres Fenster

![OAUTH1](docs/netatmo_login_1.png?raw=true "oauth 1")

Hier bitte den Zugriff des _IP-Symcon Netatmo Connector_ akzeptieren; es erscheint 

![OAUTH1](docs/netatmo_login_2.png?raw=true "oauth 2")

Das Browserfenster schliessen.

Anmerkung: auch wenn hier alle möglichen Netamo-Produkte aufgelistet sind, bezieht sich das Login nur auf die Produkte dieses Moduls.

**Zugriff als Entwickler mit eigenem Entwicklerschlüssel**

In dem Konfigurationsdialog die Netatmo-Zugangsdaten eintragen.

#### NetatmoWeatherConfig

Dann unter _Konfigurator Instanzen_ analog den Konfigurator _NetatmoWeather Konfigurator_ hinzufügen.

Hier werden alle Stationen, die mit dem, in der I/O-Instanz angegebenen, Netatmo-Konto verknüpft sind, angeboten. Durch _Erstellen_ wird die Wetterstation-Basis in der angegebenen _Kategorie_ angelegt.

Die Module werden aufgrund der internen _ID_ der Module identifiziert, d.h. eine Änderung des Modulnamens muss in IPS nachgeführt werden.
Ein Ersatz eines Moduls wird beim Aufruf des Konfigurators dazuführen, das eine weitere Instanz angelegt wird.

Die im Netatmo eingetragenen Höhe der Station sowie die geographische Position wird als Property zu dem virtuellen Modul _Station_ eingetragen.

#### NetatmoWeatherDevice

Dieses Modul gibt es in verschiedenen Ausprägungen:
_Netatmo Wetterstation_: repräsentiert die Wetterstation als übergeordnete Instanz, sowie _Basismodul_, _Außenmodul_:, _Innenmodul_:, _Windmesser_:, _Regenmesser_;

In der übergeordneten Wetterstation-Instanz werden alle Module zu dieser Wettersttaion unter dem Panel _Module_ angeboten und können hier erzeugt werden.

Je nach Modultyp stehen bestimmte weitere Einstellungen zur Verfügung.

Zu den Geräte-Instanzen werden im Rahmen der Konfiguration Modultyp-abhängig Variablen angelegt. Zusätzlich kann man in den Modultyp-spezifischen Konfigurationsdialog weitere Variablen aktivieren.

Die Instanzen können dann in gewohnter Weise im Objektbaum frei positioniert werden.

## 4. Funktionsreferenz

### zentrale Funktion

`NetatmoWeather_UpdateData(int $InstanzID)`

ruft die Daten der Netatmo-Wetterstation ab und aktualisiert optional die Wundergrund-PWS. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

### Hilfsfunktionen

`float NetatmoWeather_CalcAbsoluteHumidity(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) die absulte Feuchte (in g/m³)


`float NetatmoWeather_CalcAbsolutePressure(int $InstanzID, float $Pressure, $Temperatur, int $Altitude)`

berechnet aus dem relativen Luftdruck (in mbar) und der Temperatur (in °C) und Höhe (in m) der absoluten Luftdruck (in mbar)
ist die Höhe nicht angegeben, wird die Höhe der Netatmo-Wettersttaion verwendet


`float NetatmoWeather_CalcDewpoint(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Taupunkt (in °C)


`float NetatmoWeather_CalcHeatindex(int $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Hitzeindex (in °C)


`float NetatmoWeather_CalcWindchill(int $InstanzID, float $Temperatur, float $WindSpeed)`

berechnet aus der Temperatur (in °C) und der Windgeschwindigkeit (in km/h) den Windchill (Windkühle) (in °C)


`string NetatmoWeather_ConvertWindDirection2Text(int $InstanzID, int $WindDirection)`

ermittelt aus der Windrichtung (in °) die korespondierende Bezeichnung auf der Windrose


`int NetatmoWeather_ConvertWindSpeed2Strength(int $InstanzID, float $WindSpeed)`

berechnet aus der Windgeschwindigkeit (in km/h) die Windstärke (in bft)


`string NetatmoWeather_ConvertWindStrength2Text(int $InstanzID, int $WindStrength)`

ermittelt aus der Windstärke (in bft) die korespondierende Bezeichnung gemäß Beaufortskala

`string NetatmoWeather_GetRawData(int $InstanzID)`

liefert die Zusatzdaten, die nicht in den Variablen gespeichert sind und zu Darstellung der HTML-Box bzw WebHook verwendet werden

Datenstruktur (muss mit json_decode() aufbereitet werden):

- _station_: Informationen zu einer Wetterstation

| Attribut      | Datentyp           | Bedeutung |
| :------------ | :----------------- | :-------- |
| last_query    | UNIX-Timestamp     | Zeitpunkt der Abfrage |
| status        | string             | Status (_ok_ oder ein Fehler) |
| last_contact  | UNIX-Timestamp     | Zeitpunkt der letzten Datenübertragung |
| station_name  | string             | Benutzerbezeichnung der Station |
| modules       | array von _module_ | die Module der Station |

- _module_: Informationen zu einem Modul

| Attribut           | Datentyp        | Bedeutung |
| :----------------- | :-------------- | :-------- |
| module_type        | string          | Typ des Modules (_NAMain_, _NAModule1_, _NAModule2_, _NAModule3_, _NAModule4_) |
| module_type_txt    | string          |  ... als Text |
| module_type_img    | string          |  ... als Pfad zum Icon |
| module_name        | string          | Bezeichnung des Moduls |
| last_measure       | UNIX-Timestamp  | Zeitpunkt der letzten Messung |
| last_message       | UNIX-Timestamp  | Zeitpunkt der letzten Meldung des Moduls bei der Basis |
| wifi_status        | integer         | Wifi (_nur NAMain_) |
| wifi_status_txt    | string          |  ... als Text |
| wifi_status_img    | string          |  ... als Pfad zum Icon |
| rf_status          | integer         | Funk (nicht _NAMain_) |
| rf_status_txt      | string          |  ... als Text |
| rf_status_img      | string          |  ... als Pfad zum Icon |
| battery_status     | integer         | Status der Batterie (nicht _NAMain_) |
| battery_status_txt | string          |  ... als Text |
| battery_status_img | string          |  ... als Pfad zum Icon |

Die gelieferte Struktur ist _station_; kein Array, weil es immer nur um eine bestimmte Station geht.

## 5. Konfiguration

### NetatmoWeatherIO

#### Variablen

| Eigenschaft            | Typ     | Standardwert | Beschreibung |
| :--------------------- | :-----  | :----------- | :----------- |
| Verbindungstyp         | integer | 0            | _Netatmo über IP-Symcon Connect_ oder _Netatmo Entwickler-Schlüssel_ |
|                        |         |              | |
| Netatmo-Zugangsdaten   | string  |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
|                        |         |              | |
| Ignoriere HTTP-Fehler  | integer | 0            | Da Netatmo häufiger HTTP-Fehler meldet, wird erst ab dem X. Fehler in Folge reagiert |
|                        |         |              | |
| Aktualisiere Daten ... | integer | 5            | Aktualisierungsintervall, Angabe in Minuten |

Hinweis zum Intervall: die Daten werden nur ca. alle 10m von der Wetterstation an Netatmo übertragen, ein minütliches Intervall ist zulässig, macht aber nur begrenzt Sinn.
Bei einer Angabe von 5m sind die Werte nicht älter als 15m.

#### Schaltflächen

| Bezeichnung              | Beschreibung |
| :----------------------- | :----------- |
| bei Netatmo anmelden     | durch Anmeldung bei Netatmo via IP-Symcon Connect |
| Aktualisiere Wetterdaten | führt eine sofortige Aktualisierung durch |

### NetatmoWeatherConfig

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Kategorie                 | integer  | 0            | Kategorie im Objektbaum, unter dem die Instanzen angelegt werden |
| Stationen                 | list     |              | Liste der verfügbaren Stationen |

### NetatmoWeatherDevice

#### Properties

werden vom Konfigurator beim Anlegen der Instanz gesetzt.

| Eigenschaft | Typ    | Standardwert | Beschreibung |
| :---------- | :----- | :----------- | :----------- |
| station_id  | string |              | ID der Station |
| module_id   | string |              | ID des Moduls |
| module_type | string |              | Typ des Moduls |

_module_type_: _NAMain_=Basis, _NAModule1_=Außen, _NAModule2_=Wind, _NAModule3_=Regen, _NAModule4_=Innen sowie _Station_, die für die Netatmo-Station als Ganzes steht.

Weiterhin stehen je nach Typ des Moduls zur Verfügung

| Eigenschaft               | Typ     | Standardwert | Beschreibung |
| :------------------------ | :------ | :----------- | :----------- |
| altitude                  | float   |              | Höhe der Station |
| longitude                 | float   |              | Längengrad der Station |
| latitude                  | float   |              | Breitengrad der Station |
|                           |         |              | |
| with_absolute_humidity    | boolean | false        | absolute Luftfeuchtigkeit |
| with_absolute_pressure    | boolean | false        | absoluter Luftdruck |
| with_battery              | boolean | false        | Batterie-Status |
| with_dewpoint             | boolean | false        | Taupunkt |
| with_heatindex            | boolean | false        | Hitzeindex |
| with_last_contact         | boolean | false        | letzte Übertragung an Netatmo |
| with_minmax               | boolean | false        | Ausgabe von Min/Max-Wert (Temperatur, Wind) |
| with_last_measure         | boolean | false        | Messzeitpunkt |
| with_signal               | boolean | false        | Wifi-/RF-Signalstärke |
| with_status_box           | boolean | false        | HTML-Box mit Status der Station und Module |
| with_trend                | boolean | false        | Ausgabe des Trend (Temperatur, Luftdruck) |
| with_windangle            | boolean | true         | Windrichtung in Grad |
| with_windchill            | boolean | false        | Windchill (Windkühle) |
| with_winddirection        | boolean | false        | Windrichtung mit Text |
| with_windstrength         | boolean | false        | Windstärke |
|                           |         |              | |
| statusbox_script          | integer | 0            | Script zum Füllen der Variable _StatusBox_ |
| webhook_script            | integer | 0            | Script zur Verwendung im WebHook |
|                           |         |              | |
| minutes2fail              | integer | 30           | Dauer, bis die Kommunikation als gestört gilt |
|                           |         |              | |
| Wunderground-Zugangsdaten | string  |              | Station-ID und -Key von https://www.wunderground.com/personal-weather-station/mypws |
|                           |         |              | |
| Module                    |         |              | **nur im übergeordneten Modul** |
| Kategorie                 | integer | 0            | Kategorie im Objektbaum, unter dem die Instanzen angelegt werden |
| Module                    | list    |              | Liste der verfügbaren Module zu dieser Station |

Das hier angebbare Minuten-Intervall dient zu Überprüfung der Kommunikation zwischen
 - den Modulen und dem Basismodul
 - dem Basismodul und dem Netatmo-Server
ist die Zeit überschritten, wird die Variable _Status_ des Basismoduls auf Fehler gesetzt.
Anmerkung: die Variable _Status_ wird auch auf Fehler gesetzt wenn das IO-Modul einen Fehler feststellt.

Erläuterung zu _statusbox_script_, _webhook_script_:
mit diesen Scripten kann man eine alternative Darstellung realisieren.

Ein passendes Code-Fragment für ein Script:

```
$data = NetatmoWeather_GetRawData($_IPS['InstanceID']);
if ($data) {
	$station = json_decode($r,true);
	...
	echo $result;
}
```
Die Beschreibung der Struktur siehe _NetatmoWeather_GetRawData()_.

Beispiel in module.php sind _Build_StatusBox()_ und _ProcessHook_Status()_.

### Statusvariablen

folgende Variable werden angelegt, zum Teil optional

| Name                    | Typ            | Beschreibung                                    | Option                           | Module    |
| :---------------------- | :------------- | :---------------------------------------------- | :------------------------------- | :-------- |
| AbsoluteHumidity        | float          | absolute Luftfeuchtigkeit                       | with_absolute_humidity           | B,A,I     |
| AbsolutePressure        | float          | absoluter Luftdruck                             | with_absolute_pressure           | B         |
| BatteryAlarm            | boolean        | Batterie-Zustand eines oder mehrere Module      |                                  | B         |
| Battery                 | integer        | Batterie-Status                                 | with_battery                     | A,W,R,I   |
| CO2                     | integer        | CO2                                             |                                  | B,I       |
| Dewpoint                | float          | Taupunkt                                        | with_dewpoint                    | B,A,I     |
| GustAngle               | integer        | Richtung der Böen der letzten 5m                | with_windangle                   | W         |
| GustDirection           | string         | Richtung der Böen der letzten 5m                | with_winddirection               | W         |
| GustMaxAngle            | integer        | Richtung der stärksten heutigen Böe             | with_minmax + with_windangle     | W         |
| GustMaxDirection        | string         | Richtung der stärksten heutigen Böe             | with_minmax + with_winddirection | W         |
| GustMaxSpeed            | float          | Geschwindigkeit der stärksten heutigen Böe      |                                  | W         |
| GustMaxStrength         | integer        | Stärke der stärksten heutigen Böe               | with_minmax + with_windstrength  | W         |
| GustMaxTimestamp        | UNIX-Timestamp | Zeitpunkt der stärksten heutigen Böe            | with_minmax                      | W         |
| GustSpeed               | float          | Geschwindigkeit der Böen der letzten 5m         |                                  | W         |
| GustStrength            | integer        | Stärke der Böen der letzten 5m                  | with_windstrength                | W         |
| Heatindex               | float          | Hitzeindex                                      | with_heatindex                   | B,A,I     |
| Humidity                | float          | Luftfeuchtigkeit                                |                                  | B,A,I     |
| LastContact             | UNIX-Timestamp | letzte Übertragung                              | with_last_contact                | B         |
| LastMeasure             | UNIX-Timestamp | letzte Messung                                  | with_last_measure                | B,A,W,R,I |
| ModuleAlarm             | boolean        | Station oder Module kommunizieren nicht         |                                  | B         |
| Noise                   | integer        | Lärm                                            |                                  | B         |
| Pressure                | float          | Luftdruck                                       |                                  | B         |
| PressureTrend           | integer        | Trend des Luftdrucks                            | with_trend                       | B         |
| Rain_1h                 | float          | Regenmenge der letzten Stunde                   |                                  | R         |
| Rain_24h                | float          | Regenmenge von heute                            |                                  | R         |
| Rain                    | float          | Regenmenge                                      |                                  | R         |
| RfSignal                | integer        | Signal-Stärke                                   | with_signal                      | A,W,R,I   |
| Status                  | boolean        | Status                                          |                                  | B         |
| StatusBox               | string         | Status der Station und der Module               | with_status_box                  | B         |
| Temperature             | float          | Temperatur                                      |                                  | B,A,I     |
| TemperatureMax          | float          | heutiges Temperatur-Maximum                     | with_minmax                      | B,A,I     |
| TemperatureMaxTimestamp | UNIX-Timestamp | Zeitpunkt des heutigen Temperatur-Maximums      | with_minmax                      | B,A,I     |
| TemperatureMin          | float          | heutiges Temperatur-Minimum                     | with_minmax                      | B,A,I     |
| TemperatureMinTimestamp | UNIX-Timestamp | Zeitpunkt des heutigen Temperatur-Minimums      | with_minmax                      | B,A,I     |
| TemperatureTrend        | integer        | Trend der Temperatur                            | with_trend                       | B,A,I     |
| Wifi                    | integer        | Stärke des Wifi-Signals                         | with_signal                      | B         |
| WindAngle               | integer        | Windrichtung                                    | with_windangle                   | W         |
| Windchill               | float          | Windchill                                       | with_windchill                   | A         |
| WindDirection           | string         | Windrichtung                                    | with_winddirection               | W         |
| WindSpeed               | float          | Windgeschwindigkeit                             |                                  | W         |
| WindStrength            | integer        | Windstärke                                      | with_windstrength                | W         |
| Wunderground            | boolean        | Status der Übertragung an Wunderground          | wunderground_id                  | B         |


_Module_: B=Basis, A=Außen, W=Wind, R=Regen, I=Innen

### Variablenprofile

Es werden folgende Variablenprofile angelegt:
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

## 7. Versions-Historie

- 1.30 @ 13.07.2020 14:56
  - LICENSE.md hinzugefügt
  - bei HTTP-Error 403 (FORBIDDEN), 15m warten
  - CURL-Handling verbessert
    - bei curl-error bis zu 2x wiederholen
	- bei CURLE_COULDNT_RESOLVE_HOST dns-lookup machen

- 1.29 @ 20.06.2020 18:05
  - kleine redaktionelle Korrektur

- 1.28 @ 08.04.2020 12:18
  - define's durch statische Klassen-Variablen ersetzt

- 1.27 @ 06.03.2020 20:14
  - Wechsel des Verbindungstyp wird nun automatisch erkannt
  - Verwendung des OAuth-AccessToken korrigiert

- 1.26 @ 25.02.2020 08:31
  - Bugfix: bei inaktiver Instanz wurde der falsche Timer auf 0 gesetzt

- 1.25 @ 19.02.2020 21:39
  - Bugfix zu 1.23: Problem beim Ablauf des Access-Tokens

- 1.24 @ 14.02.2020 11:26
  - Bugfix zu 1.23: Zugriff mit Entwicklerschlüssel funktionierte nicht
  - Funktion in der IO-Konfiguration, um die Token zu löschen

- 1.23 @ 12.02.2020 16:53
  - Ergänzung um die Möglichkeit per OAuth anzumelden
    Achtung: in der IO-Instanz den Verbindungstyp nach dem Update auf _Developer Key_ setzen!
  - Prefix _NetatmoWeatherIO_, _NetatmoWeatherConfig_ und _NetatmoWeatherDevice_ in _NetatmoWeather_ geändert
    Achtung: wenn man Funktionen von diesem Modul in Scripten benutzt, muss der Prefix angepasst werden!
  - Umbau der Konfiguration auf eine 2-stufige Konfiguration (Konfigurator legt nur die Station an, dort werden dann die zugehörigen Module angeboten)

- 1.22 @ 06.01.2020 11:17
  - Nutzung von RegisterReference() für im Modul genutze Objekte (Scripte, Kategorien etc)

- 1.21 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert

- 1.20 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.19 @ 09.08.2019 14:32
  - Schreibfehler korrigiert

- 1.18 @ 12.07.2019 15:29
  - Klarstellung zur Einrichtung/Bedeutung der Netatmo-App

- 1.17 @ 16.06.2019 17:42
  - HTTP-Error 403 abgefangen (neuen Token erzwingen)
  - Anpassung IPS 5.1: Korrektur der GUID's (_parentRequirements_ in _NetatmoWeatherDevice_)

- 1.16 @ 23.04.2019 17:08
  - Konfigurator um Sicherheitsabfrage ergänzt

- 1.15 @ 31.03.2019 11:20
  - Zugriff auf alle Datenfelder abgesichert

- 1.14 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 1.13 @ 22.03.2019 10:27
  - Anpassungen IPS 5
  - Schalter, um ein Modul (temporär) zu deaktivieren
  - form.json in GetConfigurationForm() abgebildet
  - Konfigurations-Element IntervalBox -> NumberSpinner

- 1.12 @ 10.02.2019 11:16
  - verbesserte Absicherung des Upload zu Wunderground

- 1.11 @ 23.01.2019 18:18
  - curl_errno() abfragen

- 1.10 @ 20.01.2019 14:12
  - Abfangen von fehlenden Daten in der Antwort vom Netatmo-Server<br>
    Hintergrund: wenn ein Modul nicht mehr kommuniziert fehlt das Element _dashboard_data_ in den Daten.

- 1.9 @ 22.12.2018 12:20
  - Fehler in der http-Kommunikation nun nicht mehr mit _echo_ (also als **ERROR**) sondern mit _LogMessage_ als **NOTIFY**

- 1.8 @ 21.12.2018 13:10
  - Standard-Konstanten verwenden

- 1.7 @ 20.11.2018 17:38
  - das Netatmo-API-Interface hat sich anscheinend geändert, die jetzt als _deprecated_ gekennzeichnete Funktion _Devicelist_ liefert seit heute ein anderes Format.<br>
	Das Modul ist nun auf die Funktion 'Getstationsdata' umgestellt.

- 1.6 @ 18.11.2018 12:28
  - da Netatmo häufiger ein Server-Error meldet wird optional erst nach dem X. Fehler reagiert
  - I/O-Fehler werden nicht mehr an die Instanzen weitergeleitet

- 1.5 @ 02.10.2018 18:19 
  - Berechnung in _ConvertWindSpeed2Strength()_ war fehlerhaft
  - Text in _ConvertWindStrength2Text()_ wurde nicht übersetzt

- 1.4 @ 29.09.2018 13:41 
  - Ballterie-Alarm erst ab _low_
  - Schreibfehler in der GUI

- 1.3 @ 10.09.2018 15:43
  - Schreibfehler in der Dokumentation

- 1.2 @ 01.09.2018 15:09
  - Fehlermeldung 'unknown trend' erscheint nicht mehr im Log (ist nur ein Hinweis, das von Netatmo kein Trend geliefert wurde)

- 1.1 @ 22.08.2018 16:59
  - Anpassungen IPS 5, Abspaltung Branch _ips_4.4_
  - Versionshistorie dazu
  - define's der Variablentypen
  - Schaltfläche mit Link zu README.md in den Konfigurationsdialogen

- 1.0 @ 03.04.2018 17:59
  - Initiale Version
