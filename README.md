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

Es werden die von einer Netatmo-Wetterstatio zur Verfügung gestellten Wetterdaten an eine persöhnliche Wetterstation von Wunderground übertragen.

Es wird übertragen von
 - Innenmodul: Luftdruck
 - Aussenmodul: Temperatur, Luftfeuchtigkeit und der daraus berechnete Taupunkt
 - Regenmodul: 1h und 24h-Wert
 - Windmesser: Windstärke und -richtung sowie Stärke und Richtung von Böen

Wunderground schreibt, das Daten von Netatmo automatisch übernommen werden, meine Erfahrung ist aber, das das sehr unzuverlässig funktioniert (immer wieder lange Phasen ohne übertragung oder die Station taucht plötzlich unter anderem Namen auf) und zudem erfolgt meiner Beobachtung nach die Übertragung nur einmal am Tag.

### Funktionen:  

 - zyklische Übertragung der Daten

## 2. Voraussetzungen

 - IPS 4.x
 - Netatmo Wetter-Station, sinnvoll mit Regen- und Windmesser und ein entsprechenden Account bei Netatmo
   hierzu benötigt man sowohl den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com) als auch einen Account soe eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com). 
 - ein Account bei Wunderground für eine "Personal-Weather-Station"
   hierzu muss man bei Wunderground ein Konto anlegen und eine 

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 4.x) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

	
    `https://github.com/demel42/NetatmoWeather.git`  
    
und mit _OK_ bestätigen.    
        
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    


### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und _xxx_ auswählen.


## 4. Funktionsreferenz

### Überschrift:

Beschreibungstext
	


## 5. Konfiguration:

### Überschrift:

| Eigenschaft | Typ     | Standardwert | Funktion                                  |
| :---------: | :-----: | :----------: | :---------------------------------------: |
| Wert 1      | string  |              | Beschreibung                              |
| Wert 2      | integer |    20        | Beschreibung                              |






## 6. Anhang

###  a. Funktionen:

#### Überschrift:

`N2W_Function(integer $InstanceID)`

Beschreibung Funktion


###  b. GUIDs und Datenaustausch:

#### Überschrift:

GUID: `{0F22B057-3434-680A-E760-596A12F4BD99}` 
