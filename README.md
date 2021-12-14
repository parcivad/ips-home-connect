[![IP-Symcon](https://img.shields.io/badge/IP--Symcon->6.0-blue.svg?style=flat-square)](https://www.symcon.de)
[![Symcon-php-module](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)

# IP-Symcon HomeConnect
Dieses IP-Symcon Modul ist eine komfortable Schnittstelle zwischen der Bosch/Siemens Cloud, HomeConnect, und der IP-Symcon Oberfläche. Das Modul ist für die 6.0 Beta geschrieben und ist somit der Nachfolger von der 5.0 Version.

## Inhaltsverzeichnis

- [Installation](#installation)
- [Einrichten](#einrichten)
- [Geräte](#inhaltsverzeichnis)
	- [Geschirrspüler](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Dishwasher/Geschirrspüler.md)
	<!-- [Ofen](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Oven/Ofen.md)-->
- [Im Webfront](#im-webfront)
- [Fehlercodes](#fehlercodes)

## Installation 
Das Modul kann über die Kerninstanz `Modules` installiert werden. Dafür auf den `Hinzufügen/Plus` Knopf drücken und dann die Url von 
diesem Modul eingeben `https://github.com/parcivad/ips-home-connect`;

Achten sie darauf den Branch von `main` auf `v6.0-symcon` zu wechseln.

Nach der Installation kann das Modul über die ``Discovery Instance`` eingestellt werden.

## Einrichten
Nach dem Hinzufügen der ``Discovery Instance`` können die Geräte gefunden werden, nachdem sie sich eingeloggt haben.

<p align="center">
  <img width="auto" height="auto" src="https://github.com/parcivad/img/blob/main/Home%20Connect%20Login.png">
</p>

Nach dem Kopieren der Url **muss** der Login Knopf gedrückt werden. Anschließen können sie das Modul aktualisieren um all ihre Geräte zu sehen.

## Im Webfront
Durch verschiedene Einstellungen in der Geräte Instanz (`Variablen ein-/ausblenden`) wird das direkte Einbinden in das
IP-Symcon Webfront ermöglicht, durch Beispielsweise einen *`Link`*.

Hierbei Empfielt es sich die gesamte Geräte Instanz als einen Link in das Webfront einzubinden!

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
