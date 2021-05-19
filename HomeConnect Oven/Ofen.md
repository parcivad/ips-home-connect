# Ofen
Der Ofen kann gestartet, gestoppt, an und aus geschaltet werden. 
Zudem lassen sich Informationen wie die verbleibende Zeit oder die Temperatur geben lassen.

- [Instanz Einstellungen](#instanz-einstellungen)
  - [Variablen](#variablen)
  - [Variablen Webfront](#variablen-webfront)
  - [Ofen set()](#ofen-set)
    - [Zustand steuern](#zustand-steuern)
    - [Programm starten](#programm-starten-4-requests)
    - [Programm stoppen](#programm-stoppen-3-requests)
    - [Manuell refreshen](#manuell-refreshen-1-2-requests)
- [Fehlercodes](#fehlercodes)

## Instanz Einstellungen

<p align="center">
  <img width="auto" height="auto" src="https://github.com/LegendDragon11/img/blob/main/InstanzOfen.png">
</p>


In den instanz Einstellungen kann unter `Refresh` eine Zeitspanne gesetzt werden ( von 12 Uhr bis 18 Uhr )in der Automatisch geupdated wird (5min abstand) außerhalb dieser Zeit updated das Programm alle 15min.

Zudem kann eine Benachrichtigung für ein Mobile Gerät und das Webfront eingestellt werden. Es kann auch das gleiche Webfront für beide Geräte genutzt werden, solange dieses auf beide Eingestellt ist.
```diff
-Die Berechtigung RemoteControl lässt sich in der Home Connect App einstellen
-Die Berechtigung RemoteStart muss jedes mal auf dem Gerät gedrückt werden
```
## Variablen
Name | Type | Werte | Funktionen
:--- | :---: | :---  | :---:
`Last Refresh` | UnixTimeStamp | time | Zeigt die Zeit vom letzten aktualisieren
`Remote Control` | Boolean | true, false | Zeigt ob die Permission Control gegeben ist
`Geräte Zustand`| Integer | 0 Aus; 1 An; 2 Verzögerter Start; 3 Programm läuft | Zeigt dem Nutzer den Zustand vom Gerät
`Start in`| Date string | Date("H:i:s") | Zeigt verbleibende Zeit bis das ausgewählte Programm startet
`Programm` | Integer | 0 => Mode, 1 => Mode... | Zeigt den Aktuellen Modus, auch zum auswählen
`Gesetzte Temperatur` | °C | Integer | Temperatur zum setzen
`Gesetzte Laufzeit` | min. | Integer | Laufzeit zum setzen
`Remote start`| Boolean | true, false | Zeigt ob Remote Start an ist (Permission)
`Tür Zustand` | Boolean | true Offen; false Geschlossen | Zeigt ob die Tür offen/geschlossen ist (wenn das Gerät an ist)
`Temperatur` | °C | Integer | Ofen Temperature ( 60> wird eingeblendet)
`Verbleibende Zeit`| Date string | Date("H:i:s") | Zeigt verbleibende Zeit vom laufenden Programm
`Fortschritt` | % | Integer | Fortschritt im Programm
`Programm start/stop`| Boolean | true, false | Hiermit lässt sich das ausgewählte Programm starten 

### Variablen Webfront

<p align="center">
  <img width="auto" height="auto" src="https://github.com/LegendDragon11/img/blob/main/Ofen%20im%20Webfront.png">
</p>

## Ofen Set
Alle Eigenschaften die gesetzt werden können.
### Zustand steuern
Im Webfront lässt sich das Gerät an und aus schalten. Die Zustände "Startet in" und "Programm läuft" können nicht gesetzt werden, da sie über die Funktion Start/Stop gesteuert werden.


Im Code kann das Gerät an oder aus geschaltet werden mit (bei dem start von dem Gerät muss es NICHT extra angeschaltet werden).
```php
HCOven_SetActive( InstaceID, false/true );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
### Programm starten [4 REQUESTS]
Ein Programm kann im Webfront gestartet werden, beim Drücken auf dem start knopf wird der aktuelle Modus ausgewählt und gestartet

Im Code kann das Programm auch noch verzögert gestartet werden mit.
```php
HCOven_start( "<Programm als String zb. Auto2>",  Temperatur in °C als integer, Dauer wie lange das Programm laufen soll in Sekunden );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl und RemoteStart benötigt!
```
### Programm stoppen [3 REQUESTS]
Ein Programm kann über das Webfront gestoppt werden, das ist auch möglich, wenn sich das Gerät im Zustand "Start in" befindet.

Im Code kann das Programm mit ... gestoppt werden.
```php
HCOven_stop( InstanceID );
```
```diff
-Für diese Aktion wird die Berechtigung RemoteControl benötigt!
```
### Manuell refreshen [1-2 REQUESTS]
Das kann mit diesem Befehl gemacht werden.
```php
HCOven_refresh( InstanceID );
```
```diff
+Für diese Aktion wird nur die Authorizierung gebraucht.
```

## Fehlercodes
Für den Fall eines Fehlers besitzt die Geräte Instanz eine Variable namens `error`.

```Modul```

Integer | Beschreibung | Grund
:--- | :---: | ---:
`0` | Kein Fehler | Es ist kein Fehler aufgetreten, die Variable wird versteckt.
`201` | Unbekannt | Der Fehler ist dem System noch nicht bekannt. Überprüfe den Log um mehr Herauszufinden.
`206` | Authorize | Der User ist noch nicht angemeldet.
`207` | Token | Das System hat noch kein Zugriffs token (In der `Discovery Instanz`/`Geräte Instanz` auf Refresh drücken)

```HomeConnect Fehler```

Integer | Beschreibung | Grund
:--- | :---: | ---:
`401` | Nicht Verbunden | Das HomeConnect Gerät ist nicht mit dem Internet/HomeConnect system verbunden.
`402` | Unbekanntes Programm | Das Programm auf dem Gerät ist dem Modul unbekannt.
`403` | Programm start | Das Programm konnte nicht gestartet werden.
`404` | Programm stop | Das Programm konnte nicht gestoppt werden
`405` | Request | Anfrage konnte nicht bearbeitet werden.
`406` | Limit | Anfrage Limit erreicht!
`407` | HomeConnect cloud is offline | HomeConnect ist zur zeit nicht erreichbar
`408` | HomeConnect error | Fehler auf Seitens von HomeConnect