# NetatmoWeather

Modul für IP-Symcon ab Version 4.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguartion)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

Es werden die Wetter-Daten von einer Netatmo-Wetterstation ausgelesen und gespechert. Zusätzlich
- werden einige Status-Information ermittelt, unter anderen Status der Kommunikation mit Netatmo und Wunderground, Batterie- und Modul-Alarme
- weitere (im wesentlichen modulbezogene) Daten werden sowohl in einer HTML-Box aufbereitet als auch als JSON-Struktur in einer Variable zur Verfügung gestellt
- optional einige modulbezogene Daten in Variablen zur Verfügung gestellt
- es können zusätzliche Wetter-Kenndaten berechnet werden: absoluter Luftdruck, Taupunkt, absolute Feuchte, Windchill, Heatindex
- die geographіsche Position sowie die Höhe aus der Konfiguration der Netatmo-Wetterstation werden automatisch in die Instanz-Konfiguration als Property übernommen
- steht ein WebHook zur Verfügung, bei dem mit _/hook/NetatmoWeathcer/status_ die Status-Information (analog zur HTML-Box) als Webseite abgerufen werden können.

Die Angabe der 4 Netatmo-Zugangsdaten ist obligatorische, damit die Instanz aktiviert werden kann.

Weiterhin können optional die relevante Wetterdaten eine persöhnliche Wetterstation von Wunderground übertragen. Übertragen wird:
 - Innenmodul: Luftdruck
 - Aussenmodul: Temperatur, Luftfeuchtigkeit und der daraus berechnete Taupunkt
 - Regenmodul: 1h und 24h-Wert
 - Windmesser: Windgeschwindigkeit und -richtung sowie Geschwindigkeit und Richtung von Böen

Hinweis: Wunderground gibt an, das Daten von Netatmo automatisch übernommen werden, meine Erfahrung ist aber, das das sehr unzuverlässig funktioniert (immer wieder lange Phasen ohne übertragung oder die Station taucht plötzlich unter anderem Namen auf) und zudem erfolgt meiner Beobachtung nach die Übertragung nur einmal am Tag.

## 2. Voraussetzungen

 - IPS 4.x
 - Netatmo Wetterstation und ein entsprechenden Account bei Netatmo
   hierzu benötigt man sowohl den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com) als auch einen Account sowie eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com). 
 - optional ein Account bei Wunderground für eine "Personal-Weather-Station"
   hierzu muss man bei Wunderground ein Konto anlegen und eine eine Wettersttaion einrichten. Die von Wunderground angegebene Verknüpfung mit Netatmo übder den Wunderground-Support ist nicht erforderlich

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 4.x) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/NetatmoWeather.git`
    
und mit _OK_ bestätigen.    
        
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _Netatmo_ und als Gerät _NetatmoWeather_ auswählen.

Die modulbezogenen Variablen haben als ID die Prefixe _BASE_ Basismodul), _OUT_ (Aussenmodul), _IN1_, _IN2_, _IN3_ (Innenmodule), _RAIN_ (Regenmesser), _WIND_ (WIndmesser).
Die Namen der Variablen werden bei der Erstanlage auf den Prefix + die Messgröße gesetzt, nach dem ersten Aufruf von _NetatmoWeather_UpdateData_ (z.B. durch Betätigen von _Aktualisiere Wetterdaten_) werden die Namen einmalig geändert in Modulnamen + Messgröße. Dieser Vorgang kann später durch _Variablen-Namen zurücksetzen_ erneut ausgelöst werden, z.B. wenn man im Netatmo Bezeichungen von Modulen geändert hat.

## 4. PHP-Befehlsreferenz

### zentrale Funktion

`UpdateData(integer $InstanzID)`

ruft die Daten der Netatmo-Wetterstation ab und aktualisiert optional dien Wundergrund-PWS. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

### Hilfsfunktionen

`float NetatmoWeather_CalcAbsoluteHumidity(integer $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) die absulte Feuchte (in g/m³)


`float NetatmoWeather_CalcAbsolutePressure(integer $InstanzID, float $Pressure, $Temperatur, integer $Altitude)`

berechnet aus dem relativen Luftdruck (in mbar) und der Temperatur (in °C) und Höhe (in m) der absoluten Luftdruck (in mbar)
ist die Höhe nicht angegeben, wird die Höhe der Netatmo-Wettersttaion verwendet


`float NetatmoWeather_CalcDewpoint(integer $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Taupunkt (in °C)


`float NetatmoWeather_CalcHeatindex(integer $InstanzID, float $Temperatur, float $Humidity)`

berechnet aus der Temperatur (in °C) und der relativen Luftfeuchtigkeit (in %) den Hitzeindex (in °C)


`float NetatmoWeather_CalcWindchill(integer $InstanzID, float $Temperatur, float $WindSpeed)`

berechnet aus der Temperatur (in °C) und der Windgeschwindigkeit (in km/h) den Windchill (Windkühle) (in °C)


`string NetatmoWeather_ConvertWindDirection2Text(integer $InstanzID, integer $WindDirection)`

ermittelt aus der Windrichtung (in °) die korespondierende Bezeichnung


`integer NetatmoWeather_ConvertWindSpeed2Strength(integer $InstanzID, float $WindSpeed)`

berechnet aus der Windgeschindigkeit (in km/h) die Windstärke (in bft)


`string NetatmoWeather_ConvertWindStrength2Text(integer $InstanzID, integer $WindStrength)`

ermittelt aus der Windstärke (in bft) die korespondierende Bezeichnung


## 5. Konfiguration:

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| Netatmo-Zugangsdaten      | string   |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
| Stationsname              | string   |              | muss nur angegeben werden, wenn mehr als eine Station angemeldet ist |
| Aktualiserungsintervall   | integer  | 5            | Angabe in Minuten. Hinweis: die Daten werden nur ca. alle 10m von der Wetterstation an Netatmo übertragen |
| <optionale Zusatzdaten>   | boolean  | false        | wie auf der Konfigurationsseite angegeben |
| Wunderground-Zugangsdaten | string   |              | Station-ID und -Key von https://www.wunderground.com/personal-weather-station/mypws |

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Aktualisiere Wetterdaten     | führt eine sofortige Aktualisierung durch |
| Variablen-Namen zurücksetzen | setz die Variablen-Name auf den Standarwert zurück |

## 6. Anhang

GUID: `{4E453F9B-DEB9-2071-CF32-C0D0F28D8F06}` 
