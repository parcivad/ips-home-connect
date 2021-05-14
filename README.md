# IP-Symcon HomeConnect
Das ist ein IP-Symcon HomeConnect Modul. Es dient dazu mit verschieden Gerät von Siemens und Bosch zu kommunizieren. Dafür wird die Cloud Basis, HomeConnect benötigt.
Diese wird von Bosch und Siemens bereitgestellt.
Für das nutzen dieses Moduls wird ein HomeConnect Account benötigt.

## Inhaltsverzeichnis

- [Geräte](#inhaltsverzeichnis)
	- [Geschirrspüler](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Dishwasher/Geschirrspüler.md)
	- [Ofen](https://github.com/LegendDragon11/ips-home-connect/blob/main/HomeConnect%20Oven/Ofen.md)
- [Installation](#installation)
- [Einrichten](#einrichten)
- [Limits](#rate-limits)

## Installation 
Das Modul kann über die Kerninstanz `Modul` installiert werden. Dafür auf den `Hinzufügen/Plus` Knopf drücken und dann die Url 
diese von diesem Modul eingeben `https://github.com/LegendDragon11/ips-home-connect`;

Nach der Installation kann das Modul über die ``Discovery Instance`` eingestellt werden.

## Einrichten
Nach dem Hinzufügen der ``Discovery Instance`` können die Gerät gefunden werden nachdem sie sich eingeloggt haben.

<p align="center">
  <img width="auto" height="auto" src="https://github.com/LegendDragon11/img/blob/main/Home%20Connect%20Login.png">
</p>

Nach dem kopieren der Url muss der Login Knopf gedrückt werden. Danach die Instanz ``refreshen`` und die Gerät auswählen die
hinzugefügt werden sollen.
## Rate-Limits

Auch dieses Modul muss die HomeConnect Api limits einhalten, deshalb ist es jedem Benutzer dieses Moduls erlaubt max. 1.000 Anfragen [Requests] zu senden.
Wie viele Requests allein durch das automatische Aktualiesieren entstehen kann in den Instanz Einstellungen eingesehen werden.
