# Event Attendance

Ein WordPress-Plugin zur Verwaltung von Terminen und Teilnehmern mit Widget zur Zu- und Absage.

## Beschreibung

Mit diesem Plugin k�nnen Benutzer die Teilnahme an Terminen best�tigen oder absagen. Es bietet ein Widget zur schnellen Zu- oder Absage sowie eine detaillierte Ansicht f�r die Terminverwaltung.

### Funktionen

- Terminerstellung und -verwaltung im Admin-Bereich
- Verwaltung von Teilnehmern
- Wiederkehrende Termine erstellen (z.B. jede Woche, alle 2 Wochen)
- Zu- und Absagen mit verschiedenen Absagegr�nden (krank, Urlaub, Dienstreise)
- Kommentare zu Zu- und Absagen
- Widget f�r die Seitenleiste
- Shortcode f�r Einbindung in Seiten und Beitr�ge
- Sichtbar nur f�r angemeldete Benutzer

## Installation

1. Laden Sie den Ordner `event-attendance` in das Verzeichnis `/wp-content/plugins/` hoch
2. Aktivieren Sie das Plugin �ber das Men� 'Plugins' in WordPress
3. Gehen Sie zu 'Event Attendance' im Admin-Men�, um Termine und Teilnehmer zu verwalten
4. F�gen Sie das Widget 'Event Attendance' �ber 'Design > Widgets' zu einer Seitenleiste hinzu

## Verwendung

### Shortcode

Verwenden Sie den Shortcode `[event_attendance]` auf beliebigen Seiten oder Beitr�gen, um die Terminverwaltung einzubinden.

Optionale Parameter:
- `event_id="123"` - Zeigt die Details eines bestimmten Termins an
- `limit="10"` - Anzahl der anzuzeigenden Termine (Standard: 5)
- `show_past="yes"` - Vergangene Termine anzeigen (Standard: nein)

Beispiele:
```
[event_attendance]
[event_attendance event_id="123"]
[event_attendance limit="10" show_past="yes"]
```

### Widget

Das Plugin stellt ein Widget bereit, das in der Seitenleiste angezeigt werden kann. Das Widget zeigt kommende Termine an und erm�glicht Benutzern, ihre Teilnahme zu best�tigen oder abzusagen.

## SQL-Tabellen

Das Plugin erstellt bei der Aktivierung folgende Tabellen:

### Termine (wp_event_attendance_events)
```sql
CREATE TABLE wp_event_attendance_events (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    date datetime NOT NULL,
    location varchar(255) NOT NULL,
    description text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
);
```

### Teilnehmer (wp_event_attendance_participants)
```sql
CREATE TABLE wp_event_attendance_participants (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY user_id (user_id)
);
```

### Teilnahmestatus (wp_event_attendance_status)
```sql
CREATE TABLE wp_event_attendance_status (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    event_id mediumint(9) NOT NULL,
    participant_id mediumint(9) NOT NULL,
    status varchar(20) NOT NULL, /* attending, declined_sick, declined_vacation, declined_business */
    comment text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY event_participant (event_id, participant_id)
);
```

## Anforderungen

- WordPress 5.0 oder h�her
- PHP 8.0 oder h�her
- MySQL 5.6 oder h�her

## Datenschutz

Das Plugin speichert:
- Termine mit Titel, Datum, Ort und Beschreibung
- Teilnehmerinformationen (Name, E-Mail, Benutzer-ID)
- Teilnahmestatus und Kommentare zu Terminen

Alle Daten werden in der WordPress-Datenbank gespeichert und sind nur f�r angemeldete Benutzer sichtbar.

## Lizenz

GPLv2 oder h�her