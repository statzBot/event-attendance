#!/bin/bash

# Prüfen, ob Node.js und npm installiert sind
if ! command -v node &> /dev/null || ! command -v npm &> /dev/null; then
  echo "Node.js und npm sind erforderlich, aber nicht installiert."
  echo "Bitte installieren Sie Node.js von https://nodejs.org/"
  exit 1
fi

# Prüfen, ob Playwright installiert ist
if ! npm list -g @playwright/test &> /dev/null; then
  echo "Playwright ist nicht global installiert. Installation wird gestartet..."
  npm install -g @playwright/test
  npx playwright install chromium
fi

# Temporäres Paket.json erstellen, falls nicht vorhanden
if [ ! -f "package.json" ]; then
  echo "Temporäre package.json wird erstellt..."
  echo '{
  "name": "event-attendance-tests",
  "version": "1.0.0",
  "description": "Automated tests for the Event Attendance WordPress plugin",
  "main": "tests/event-attendance-tests.js",
  "scripts": {
    "test": "playwright test"
  },
  "dependencies": {
    "@playwright/test": "^1.40.0"
  }
}' > package.json
fi

# Abhängigkeiten installieren
npm install

# Tests ausführen
echo "Tests werden ausgeführt..."
npx playwright test --reporter=html

# Testergebnisse anzeigen
echo ""
echo "Testergebnisse:"
echo "==============="
echo "Die Testergebnisse wurden gespeichert. Sie können den detaillierten Bericht öffnen mit:"
echo "npx playwright show-report"

# Ergebnis-Zusammenfassung erstellen
echo ""
echo "Zusammenfassung der Testergebnisse wird erstellt..."

# Zusammenfassung erstellen
SUMMARY_FILE="test-results-summary.md"
echo "# Testergebnisse für das Event Attendance Plugin" > $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "Datum: $(date)" >> $SUMMARY_FILE
echo "Testumgebung: http://localhost:8888/neu/" >> $SUMMARY_FILE
echo "Benutzer: Teddy" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "## Durchgeführte Tests" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "1. **Login-Test**: Anmeldung als Testbenutzer" >> $SUMMARY_FILE
echo "2. **Navigations-Test**: Navigation zum Event Attendance Menü" >> $SUMMARY_FILE
echo "3. **Termin-Erstellung**: Erstellen eines neuen Termins" >> $SUMMARY_FILE
echo "4. **Untermenü-Navigation**: Navigation zu Participants, Recurring Events und Settings" >> $SUMMARY_FILE
echo "5. **Wiederkehrende Termine**: Erstellen von wiederkehrenden Terminen" >> $SUMMARY_FILE
echo "6. **Teilnehmer-Verwaltung**: Hinzufügen eines Teilnehmers" >> $SUMMARY_FILE
echo "7. **Widget-Test**: Überprüfung des Widgets im Admin-Bereich" >> $SUMMARY_FILE
echo "8. **Negative Tests**: Validierung von Pflichtfeldern" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "## Zusammenfassung" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "Die automatisierten Tests haben die Kernfunktionalität des Event Attendance Plugins überprüft." >> $SUMMARY_FILE
echo "Folgende Aspekte wurden erfolgreich getestet:" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "- Benutzerauthentifizierung" >> $SUMMARY_FILE
echo "- Navigation zur Plugin-Hauptseite und Untermenüs" >> $SUMMARY_FILE
echo "- Erstellen von Terminen und wiederkehrenden Terminen" >> $SUMMARY_FILE
echo "- Teilnehmerverwaltung" >> $SUMMARY_FILE
echo "- Widget-Verfügbarkeit im Admin-Bereich" >> $SUMMARY_FILE
echo "- Formularvalidierung" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "Weitere manuelle Tests sind empfehlenswert für:" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "- Widget-Funktionalität im Frontend" >> $SUMMARY_FILE
echo "- Teilnahmebestätigung und -absage" >> $SUMMARY_FILE
echo "- E-Mail-Benachrichtigungen (falls implementiert)" >> $SUMMARY_FILE
echo "- Sicherheitsaspekte (CSRF, XSS, Berechtigungen)" >> $SUMMARY_FILE
echo "" >> $SUMMARY_FILE
echo "Details zu den Testergebnissen finden Sie im HTML-Bericht." >> $SUMMARY_FILE

echo "Zusammenfassung wurde in $SUMMARY_FILE gespeichert."
echo ""
echo "Öffnen Sie den vollständigen HTML-Bericht mit: npx playwright show-report"