# IP-Symcon Modul für die HomeConnect Geräte
Mit diesem Modul lassen sich Daten lesen und senden. Bisher unterstützt das Modul nur den Geschirrspüler (weitere Geräte kommen).

## Dokumentation
In der Dokumentation lässt sich zu jedem unterstützten Gerät eine Anleitung und Code beispiele finden.

**Inhaltsverzeichnis**

1.[Geschirrspüler](#geschirrspüler) 


# Geräte

## Geschirrspüler
Der Geschirrspüler kann gestartet, gestoppt, an und aus geschaltet werden. 
Zudem lassen sich Informationen wie die Verbleibende Zeit oder die Programme geben lassen.

### Instanz Einstellungen

<p align="center">
  <img width="447" height="416" src="https://github.com/LegendDragon11/img/blob/main/instanceGeschirrspüler.png">
</p>

In den Instanz Einstellungen kann unter `Refresh` eine Zeitspanne gesetzt werden ( von 12 Uhr bis 18 Uhr )in der Automatisch geupdated wird (5min abstand) außerhalb dieser Zeit updated das Programm alle 15min.

### Variablen
Name | Type | Werte | Funktionen
:--- | :---: | :---  | :---:
`Last Refresh` | UnixTimeStamp | time | Zeigt die Zeit vom letzen aktualisieren
`Remote Control` | Boolean | true, false | Zeigt ob die Permission Control gegeben ist
`Geräte Zustand`| Integer | 0 Aus; 1 An; 2 Programm läuft | Zeigt dem Nutzer den Zustand
`Programm` | Integer | 0,1,2... | Zeigt den Aktuellen Modus, auch zum auswählen
`Remote start`| Boolean | true, false | Zeigt ob Remote Start an ist
`Tür Zustand` | Boolean | true, false | Zeigt ob die Tür offen/geschlossen ist (wenn das Gerät an ist)
`Verbleibende Zeit`| UnixTimeStampTime | time | Zeigt verbleibende Zeit vom Programm
`Fortschritt` | % | time | Fortschritt im Programm (einzelne Stufen "Reinigung"-"Trocknen"-uws.)
`Programm start/stop`| Boolean | true, false | Hiermit lässt sich das ausgewählte Programm starten 

### Gerät Zustand steuern
Der Geräte Zustand kann im Webfront geändert werden oder im Code mit diesem Befehl (siehe Beispiel)
Dabei wird zwischen true/false unterschieden, also An oder Aus.
Beispiel:
```php
HomeConnectDishwasher_SetActive( InstaceID, false/true );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
### Programm starten
Ein Programm kann über das Webfront gestartet werden oder im Code (siehe Beispiel). Hierbei ist der Modus ein string. Die Namen lassen sich im `Programm` integer finden.
Beispiel:
```php
HomeConnectDishwasher_start( InstanceID, "Auto2");
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl und RemoteStart benötigt!
```
### Programm stoppen
Ein Programm kann über das Webfront gestoppt werden oder im Code (siehe Beispiel).
```php
HomeConnectDishwasher_stop( InstanceID );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
### Manuell refreshen
Das kann mit:
```php
HomeConnectDishwasher_refresh( 46747 );
```
gemacht werden.
```diff
+Für diese Aktion wird nur die Authorizierung gebraucht.
```
