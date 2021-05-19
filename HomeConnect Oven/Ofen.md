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

Code | Beschreibung | Grund
:--- | :---: | ---:
`102` | Kein Fehler | Es ist kein Fehler aufgetreten.
`201` | Error is unknown | Der Fehler ist dem System noch nicht bekannt. Überprüfe den Log um mehr Herauszufinden.
**`206`** | User not authorized | Der User ist noch nicht angemeldet.
`207` | Client has not token | Das System hat noch kein Zugriffs token (In der `Discovery Instanz`/`Geräte Instanz` auf Refresh drücken)

```HomeConnect Fehler```

Code | Beschreibung | Grund
:--- | :---: | ---:
**`401`** | Device is offline | Das HomeConnect Gerät ist nicht mit dem Internet/HomeConnect system verbunden.
`402` | Program is unknown | Das Programm auf dem Gerät ist dem Modul unbekannt.
`403` | Cant start program | Das Programm konnte nicht gestartet werden.
`404` | Cant stop program | Das Programm konnte nicht gestoppt werden
`405` | Request failed  | Anfrage an HomeConnect ist fehlgeschlagen.
**`406`** | Request limit reached | Anfrage Limit erreicht!
`407` | HomeConnect cloud is offline | HomeConnect ist zur zeit nicht erreichbar
**`408`** | HomeConnect error | Fehler auf Seitens von HomeConnect
`409` | Permission is missing | Dem Modul fehlen Zugriffsrechte auf ihre Geräte. Kontaktieren sie den Entwickler!
`410` | Operation state is unknown  | Der Geräte Zustand ist dem Modul nicht bekannt.
**`411`** | Remote Control not allowed  | Die Kontrolle von dem Gerät ist nicht erlaubt. (Das lässt sich auf dem Gerät ändern)
**`412`** | Remote Start not allowed  | Der Fernstart ist nicht erlaubt. (Das lässt sich auf dem Gerät ändern).
`413` | Device is locked  | Der Zugriff auf das Gerät wird von dem lokalem Gerät verboten.
`414` | Front Panel is open  | Das vordere Panel ist offen.
`415` | Door is open  | Die Tür ist offen (für die Aktion muss sie geschlossen sein)
`416` | Meatprobe is plugged  | Es wird zurzeit die Fleischprobe genutzt.
**`417`** | Battery Level Low | Anfragen werden abgelehnt, da die Batterie unter 10/20% ist.
`418` | Device is lifted | Das Gerät muss zum ausführen auf dem Boden stehen!
`419` | Dust Box not inserted | Die Dreck/Staub Box muss eingesteckt sein!
`420` | Already at Home | Das Gerät befindet sich schon Zuhause.
`421` | Active Program | Es läuft bereits ein Program.