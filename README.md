# IP-Symcon Modul für die HomeConnect Geräte
Mit diesem Modul lassen sich Daten lesen und senden. Bisher unterstützt das Modul nur den Geschirrspüler (weitere Geräte kommen).

## Dokumentation
In der Dokumentation lässt sich zu jedem unterstützten Gerät eine Anleitung und Code beispiele finden.

**Inhaltsverzeichnis**

- [Geräte](#geräte)
	- [Geschirrspüler](#geschirrspüler)
- [Info](#Info)


# Geräte

## Geschirrspüler
Der Geschirrspüler kann gestartet, gestoppt, an und aus geschaltet werden. 
Zudem lassen sich Informationen wie die Verbleibende Zeit oder die Programme geben lassen.

### Instanz Einstellungen

In den Instanz Einstellungen kann unter `Refresh` eine Zeitspanne gesetzt werden ( von 12 Uhr bis 18 Uhr )in der Automatisch geupdated wird (5min abstand) außerhalb dieser Zeit updated das Programm alle 15min.

Zudem kann eine Benachrichtigung für ein Mobiles Gerät und das Webfront eingstellt werden. Es kann auch das gleiche Webfront wir beide Geräte genutzt werden.
```diff
-Die Berechtigung RemoteControl lässt sich in der Home Connect App einstellen
-Die Berechtigung RemoteStart muss jedes mal auf dem Gerät gedrückt werden
```
### Variablen
Name | Type | Werte | Funktionen
:--- | :---: | :---  | :---:
`Last Refresh` | UnixTimeStamp | time | Zeigt die Zeit vom letzen aktualisieren
`Remote Control` | Boolean | true, false | Zeigt ob die Permission Control gegeben ist
`Geräte Zustand`| Integer | 0 Aus; 1 An; 2 Programm läuft | Zeigt dem Nutzer den Zustand vom Gerät
`Programm` | Integer | 0,1,2... | Zeigt den Aktuellen Modus, auch zum auswählen
`Remote start`| Boolean | true, false | Zeigt ob Remote Start an ist
`Tür Zustand` | Boolean | true Offen; false Geschlossen | Zeigt ob die Tür offen/geschlossen ist (wenn das Gerät an ist)
`Verbleibende Zeit`| UnixTimeStampTime | time | Zeigt verbleibende Zeit vom Programm
`Fortschritt` | % | Integer | Fortschritt im Programm (einzelne Stufen "Reinigung"-"Trocknen"-uws.)
`Programm start/stop`| Boolean | true, false | Hiermit lässt sich das ausgewählte Programm starten 

### Geschirrspüler Set
Alle Eigenschaften die gesetzt werden können.
#### Gerät Zustand steuern
Im Webfront lässt sich das Gerät An und Aus schalten. Die Zustände "Startet in" und "Programm läuft" können nicht gesetzt werden, da sie über die Funktion Start/Stop gesteuert werden.


Im Code kann das Gerät an oder aus geschaltet werden mit (bei dem starten von dem Gerät muss es NICHT extra angeschaltet werden).
```php
HCDishwasher_SetActive( InstaceID, false/true );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
#### Programm starten [4 REQUESTS]
Ein Programm kann im Webfront gestartet werden, beim drücken auf dem start knopf wird der aktuelle Modus ausgewählt und gestartet

Im Code kann das Programm auch noch verzögert gestartet werden mit.
```php
HCDishwasher_start( InstanceID, "<Modus als string>", "<Verzögerter start in sekunden>");
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl und RemoteStart benötigt!
```
#### Programm stoppen [3 REQUESTS]
Ein Programm kann über das Webfront gestoppt werden, dass geht auch wenn sich das Gerät im Zustand "Start in" befindet.

Im Code kann das Programm mit ... gestoppt werden.
```php
HCDishwasher_stop( InstanceID );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
#### Manuell refreshen [1-2 REQUESTS]
Das kann mit diesem Befehl gemacht werden.
```php
HCDishwasher_refresh( InstanceID );
```
```diff
+Für diese Aktion wird nur die Authorizierung gebraucht.
```

## Info
Für alle Befehle und abfragen gilt:
- Die Anfrage braucht 0,5 - 3sec
- Die Befehle brauchen ebenfalls etwas Zeit
```diff
-Max 1.000 Request für alles (refresh, start/stop, an/aus, alle geräte)
````
- Die Befehle brauchen ebenfalls etwas Zei
