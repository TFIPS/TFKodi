# TFKodi

Kodi-Modul für IPS (IP-Symcon)

Funktionsumfang:
- "Was läuft aktuell" (PVR-Plugin) | Anzeige von Kanal und Titel
- Fortschrittsanzeige
- Statusanzeige des Players (Play / Pause / Stopp / Screensaver)
- Schalten von Aktoren beim gewünschten Status

Funktionen in Planung:
- Fernbedienung per WebFront


Installation:
- Modul TFKodi installieren von: https://github.com/TFIPS/TFKodi.git
- Instanz TFKodi hinzufügen
- IP-Adresse von Kodi in der übergeordneten Instanz eintragen (TFKodi JSON-RPC-Socket) und aktivieren
- ggf. Links zum WebFront erstellen

Schalten von Aktoren:
In die Kategorien Play / Pause und Stop können Links zu Aktoren gesetzt werden.
- Links müssen auf die Variablen der zu schaltenden  Instanz gelegt werden (z.B. STATE einer Schaltsteckdose)
- Ein Ausrufungszeichen negiert den Schaltbefehl (z.B. !STATE aus Schaltsteckdose zum Ausschalten)