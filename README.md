# IP-Symcon HomeConnect
Das ist ein IP-Symcon HomeConnect Modul. Es dient dazu mit verschieden Gerät von Siemens und Bosch zu kommunizieren. Dafür wird die Cloud Basis, HomeConnect benötigt.
Diese wird von Bosch und Siemens bereitgestellt.
Für das Nutzen dieses Moduls wird ein HomeConnect Account benötigt.

## Inhaltsverzeichnis

- [Installation](#installation)
- [Einrichten](#einrichten)
- [Geräte](#inhaltsverzeichnis)
	- [Geschirrspüler](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Dishwasher/Geschirrspüler.md)
	- [Ofen](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Oven/Ofen.md)
- [Im Webfront](#im-webfront)
- [Refreshen](#refreshen)
- [Limits](#rate-limits)

## Installation 
Das Modul kann über die Kerninstanz `Modules` installiert werden. Dafür auf den `Hinzufügen/Plus` Knopf drücken und dann die Url von 
diesem Modul eingeben `https://github.com/LegendDragon11/ips-home-connect`;

Nach der Installation kann das Modul über die ``Discovery Instance`` eingestellt werden.

## Einrichten
Nach dem Hinzufügen der ``Discovery Instance`` können die Geräte gefunden werden, nachdem sie sich eingeloggt haben.

<p align="center">
  <img width="auto" height="auto" src="https://github.com/LegendDragon11/img/blob/main/Home%20Connect%20Login.png">
</p>

Nach dem Kopieren der Url muss der Login Knopf gedrückt werden. Danach die Instanz ``refreshen`` und die Geräte auswählen die
hinzugefügt werden sollen.

## Im Webfront
Durch verschiedene Einstellungen in der Geräte Instanz (`Variablen ein-/ausblenden`) wird das direkte Einbinden in das
IP-Symcon Webfront ermöglicht, durch Beispielsweise einen *`Link`*.

## Refreshen
Zur Erinnerung, eine Gerät-Instanz updated max. alle 5min automatisch. Das heißt das sie nach einem ``Start/Stopp`` 5min
warten müssen bis sie aktuelle Informationen sehen.

Bei Programmen die eine feste Laufzeit haben wird die ``aktuelle`` Zeit angezeit (Modul eingener Timer). Falls keine feste
Laufzeit feststeht, *zb. beim Vorheizen von einem Ofen*, wird in der Variable `Verbleibende Zeit` *--:--:--* angezeigt.

Ebenfalls kann natürlich auch manuell per ``refresh()`` aktualisiert werden. Diese Funktion ist aber eher dafür gedacht
vor einem ``start()`` die Berechtigung oder Türzustand abzufragen. Denn falls einer dieser Berechtigungen fehlt, wird ein Fehler
geworfen.

## Rate-Limits
Auch dieses Modul muss die [HomeConnect Rate-Limits](https://api-docs.home-connect.com/general?#rate-limiting) einhalten, deshalb ist es jedem Benutzer dieses Moduls erlaubt max. 1.000 Anfragen [Requests] zu senden. 
