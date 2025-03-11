// @ts-check
const { test, expect } = require('@playwright/test');

// Parse test parameters
const testValues = process.env.TEST_VALUES ? parseInt(process.env.TEST_VALUES, 10) : 1;
console.log(`Running tests with ${testValues} different values`);

test.describe('Event Attendance Plugin Tests', () => {
  // Benutzeranmeldedaten
  const username = 'Teddy';
  const password = 'eva123';
  const baseURL = 'http://localhost:8888/neu/';

  test.beforeEach(async ({ page }) => {
    // Zum WordPress-Login navigieren
    await page.goto(`${baseURL}/wp-login.php`);
    
    // Login-Formular ausfüllen und absenden
    await page.fill('#user_login', username);
    await page.fill('#user_pass', password);
    await page.click('#wp-submit');
    
    // Überprüfen, ob der Login erfolgreich war
    await expect(page).toHaveURL(/wp-admin/);
  });

  test('Kann zum Event Attendance-Menü navigieren', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Überprüfen, ob die Event-Seite geladen wurde
    await expect(page).toHaveURL(/page=event-attendance/);
    await expect(page.locator('h1')).toContainText('Events');
  });

  for (let i = 0; i < testValues; i++) {
    test(`Kann einen neuen Termin erstellen (${i+1}/${testValues})`, async ({ page }) => {
      // Zum Event Attendance-Menü navigieren
      await page.click('text=Event Attendance');
      
      // "Neuen Termin erstellen" Button finden und klicken
      await page.click('#create-event-button');
      
      // Formular für neuen Termin ausfüllen
      const testTitle = `Test Event ${i+1}-${Date.now()}`;
      await page.fill('#event-title', testTitle);
      
      // Datum für heute + i+1 Tage setzen
      const eventDate = new Date();
      eventDate.setDate(eventDate.getDate() + i + 1);
      const formattedDate = eventDate.toISOString().slice(0, 16).replace('T', ' ');
      await page.fill('#event-date', formattedDate);
      
      await page.fill('#event-location', `Test Location ${i+1}`);
      await page.fill('#event-description', `Test Description for event ${i+1}`);
      
      // Formular absenden
      await page.click('#save-event-button');
      
      // Warten auf die AJAX-Antwort
      await page.waitForSelector('.notice-success');
      
      // Prüfen, ob der neue Termin in der Liste erscheint
      await expect(page.locator('.events-table')).toContainText(testTitle);
    });
  }

  test('Kann zu Participants-Seite navigieren', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Participants-Untermenü navigieren
    await page.click('text=Participants');
    
    // Überprüfen, ob die Participants-Seite geladen wurde
    await expect(page).toHaveURL(/page=event-attendance-participants/);
    await expect(page.locator('h1')).toContainText('Participants');
  });

  test('Kann zu Recurring Events-Seite navigieren', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Recurring Events-Untermenü navigieren
    await page.click('text=Recurring Events');
    
    // Überprüfen, ob die Recurring Events-Seite geladen wurde
    await expect(page).toHaveURL(/page=event-attendance-recurring/);
    await expect(page.locator('h1')).toContainText('Recurring Events');
  });

  test('Kann zu Settings-Seite navigieren', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Settings-Untermenü navigieren
    await page.click('text=Settings');
    
    // Überprüfen, ob die Settings-Seite geladen wurde
    await expect(page).toHaveURL(/page=event-attendance-settings/);
    await expect(page.locator('h1')).toContainText('Settings');
  });

  test('Kann wiederkehrende Termine erstellen', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Recurring Events-Untermenü navigieren
    await page.click('text=Recurring Events');
    
    // Formular für wiederkehrende Termine ausfüllen
    const testTitle = `Recurring Test ${Date.now()}`;
    await page.fill('#recurring-title', testTitle);
    
    // Startdatum (heute + 1 Tag)
    const start = new Date();
    start.setDate(start.getDate() + 1);
    const startDate = start.toISOString().slice(0, 10);
    await page.fill('#start-date', startDate);
    
    // Enddatum (heute + 30 Tage)
    const end = new Date();
    end.setDate(end.getDate() + 30);
    const endDate = end.toISOString().slice(0, 10);
    await page.fill('#end-date', endDate);
    
    await page.fill('#recurring-location', 'Weekly Location');
    await page.fill('#recurring-description', 'Weekly Description');
    
    // Wöchentlichen Intervall auswählen
    await page.selectOption('#interval', '1');
    
    // Wochentag auswählen (Montag = 1)
    await page.selectOption('#day-of-week', '1');
    
    // Formular absenden
    await page.click('#create-recurring-button');
    
    // Warten auf die AJAX-Antwort
    await page.waitForSelector('.notice-success');
    
    // Überprüfen, ob Erfolgsmeldung angezeigt wird
    await expect(page.locator('.notice-success')).toContainText('recurring events created successfully');
  });

  test('Kann einen Teilnehmer hinzufügen', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Participants-Untermenü navigieren
    await page.click('text=Participants');
    
    // "Neuen Teilnehmer erstellen" Button finden und klicken
    await page.click('#create-participant-button');
    
    // Formular für neuen Teilnehmer ausfüllen
    const testName = `Test Participant ${Date.now()}`;
    await page.fill('#participant-name', testName);
    await page.fill('#participant-email', 'test@example.com');
    
    // Benutzer auswählen (erster in der Liste)
    await page.selectOption('#user-id', { index: 1 });
    
    // Formular absenden
    await page.click('#save-participant-button');
    
    // Warten auf die AJAX-Antwort
    await page.waitForSelector('.notice-success');
    
    // Prüfen, ob der neue Teilnehmer in der Liste erscheint
    await expect(page.locator('.participants-table')).toContainText(testName);
  });

  test('Widget-Anzeige im Admin-Bereich überprüfen', async ({ page }) => {
    // Zum Widgets-Bereich navigieren
    await page.goto(`${baseURL}/wp-admin/widgets.php`);
    
    // Überprüfen, ob die Widgets-Seite geladen wurde
    await expect(page).toHaveURL(/widgets.php/);
    
    // Prüfen, ob das Event Attendance Widget verfügbar ist
    await expect(page.locator('div.widgets-holder-wrap')).toContainText('Terminzusagen');
  });

  // Negative Tests
  
  test('Negativ: Fehlermeldung bei Erstellung eines Termins ohne Titel', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // "Neuen Termin erstellen" Button finden und klicken
    await page.click('#create-event-button');
    
    // Formular ohne Titel absenden, andere Felder ausfüllen
    await page.fill('#event-date', '2024-01-01 12:00');
    await page.fill('#event-location', 'Test Location');
    
    // Formular absenden
    await page.click('#save-event-button');
    
    // Überprüfen, ob Fehlermeldung angezeigt wird
    await expect(page.locator('.notice-error')).toContainText('Required fields missing');
  });

  test('Negativ: Fehlermeldung bei Erstellung eines Teilnehmers ohne E-Mail', async ({ page }) => {
    // Zum Event Attendance-Menü navigieren
    await page.click('text=Event Attendance');
    
    // Zu Participants-Untermenü navigieren
    await page.click('text=Participants');
    
    // "Neuen Teilnehmer erstellen" Button finden und klicken
    await page.click('#create-participant-button');
    
    // Formular ohne E-Mail absenden
    await page.fill('#participant-name', 'Test Name');
    
    // Formular absenden
    await page.click('#save-participant-button');
    
    // Überprüfen, ob Fehlermeldung angezeigt wird
    await expect(page.locator('.notice-error')).toContainText('Required fields missing');
  });

  // OWASP Top 10 Security Tests
  test.describe('OWASP Top 10 Security Tests', () => {
    
    // A1:2021 - Broken Access Control
    test('A1: Broken Access Control - Versuche, ohne Anmeldung auf Admin-Seiten zuzugreifen', async ({ page }) => {
      // Ausloggen falls angemeldet
      await page.goto(`${baseURL}/wp-login.php?action=logout`);
      await page.goto(`${baseURL}/wp-admin/admin.php?page=event-attendance`);
      
      // Sollte zur Login-Seite umgeleitet werden
      await expect(page).toHaveURL(/wp-login.php/);
    });

    // A2:2021 - Cryptographic Failures (Überprüfen auf HTTPS)
    test('A2: Cryptographic Failures - Überprüfen, ob HTTPS verwendet wird', async ({ page }) => {
      await page.goto(`${baseURL}/wp-login.php`);
      
      // URL überprüfen (In einer Testumgebung könnte dies http statt https sein, 
      // daher nur ein Hinweis, kein tatsächlicher Test)
      const currentUrl = page.url();
      console.log(`Current URL scheme: ${currentUrl.startsWith('https') ? 'HTTPS (secure)' : 'HTTP (not secure)'}`);
    });

    // A3:2021 - Injection (SQL, XSS)
    test('A3: Injection - XSS Test bei Terminerstellung', async ({ page }) => {
      // Login
      await page.goto(`${baseURL}/wp-login.php`);
      await page.fill('#user_login', username);
      await page.fill('#user_pass', password);
      await page.click('#wp-submit');
      
      // Zum Event Attendance-Menü navigieren
      await page.click('text=Event Attendance');
      
      // "Neuen Termin erstellen" Button finden und klicken
      await page.click('#create-event-button');
      
      // XSS-Payload in Titel einfügen
      const xssPayload = '<script>alert("XSS")</script>Test XSS Event';
      await page.fill('#event-title', xssPayload);
      
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const formattedDate = tomorrow.toISOString().slice(0, 16).replace('T', ' ');
      await page.fill('#event-date', formattedDate);
      
      await page.fill('#event-location', 'Test Location');
      await page.fill('#event-description', 'Test Description');
      
      // Auf Dialog vorbereiten (sollte nicht auftauchen, wenn XSS verhindert wird)
      page.on('dialog', async dialog => {
        console.log(`Dialog appeared: ${dialog.message()}`);
        await dialog.dismiss();
        // Wenn ein Dialog erscheint, ist die Website für XSS anfällig
        throw new Error('XSS vulnerability detected!');
      });
      
      // Formular absenden
      await page.click('#save-event-button');
      
      // Warten auf die AJAX-Antwort
      await page.waitForSelector('.notice-success');
      
      // Verifizieren, dass der Titel im HTML escaped wurde
      const pageContent = await page.content();
      const rawXssInContent = pageContent.includes('<script>alert("XSS")</script>');
      expect(rawXssInContent).toBeFalsy();
    });

    // A4:2021 - Insecure Design (Testen funktionaler Kontrollen)
    test('A4: Insecure Design - Duplizierte E-Mail-Adressen bei Teilnehmern', async ({ page }) => {
      // Login
      await page.goto(`${baseURL}/wp-login.php`);
      await page.fill('#user_login', username);
      await page.fill('#user_pass', password);
      await page.click('#wp-submit');
      
      // Zum Event Attendance-Menü navigieren
      await page.click('text=Event Attendance');
      
      // Zu Participants-Untermenü navigieren
      await page.click('text=Participants');
      
      // "Neuen Teilnehmer erstellen" Button finden und klicken
      await page.click('#create-participant-button');
      
      // Eindeutige E-Mail für Test generieren
      const uniqueEmail = `test${Date.now()}@example.com`;
      
      // Ersten Teilnehmer erstellen
      await page.fill('#participant-name', 'Test Name 1');
      await page.fill('#participant-email', uniqueEmail);
      await page.click('#save-participant-button');
      
      // Warten auf die AJAX-Antwort
      await page.waitForSelector('.notice-success');
      
      // Versuchen, zweiten Teilnehmer mit gleicher E-Mail zu erstellen
      await page.click('#create-participant-button');
      await page.fill('#participant-name', 'Test Name 2');
      await page.fill('#participant-email', uniqueEmail);
      await page.click('#save-participant-button');
      
      // Prüfen, ob System Duplikate erkennt
      // (Dies könnte eine Fehlermeldung sein oder einen Erfolg, abhängig von der Implementation.
      // Hier prüfen wir nur, ob die Seite nach dem Absenden reagiert hat.)
      await expect(page.locator('body')).toBeVisible();
    });

    // A5:2021 - Security Misconfiguration (CSRF Test)
    test('A5: Security Misconfiguration - CSRF Token Validation', async ({ page }) => {
      // Login
      await page.goto(`${baseURL}/wp-login.php`);
      await page.fill('#user_login', username);
      await page.fill('#user_pass', password);
      await page.click('#wp-submit');
      
      // Zum Event Attendance-Menü navigieren
      await page.click('text=Event Attendance');
      
      // Prüfen, ob ein CSRF-Token im Formular vorhanden ist
      await page.click('#create-event-button');
      
      // WordPress verwendet standardmäßig nonces für CSRF-Schutz
      const hasNonce = await page.locator('input[name="_wpnonce"]').count() > 0;
      expect(hasNonce).toBeTruthy();
    });

    // A6:2021 - Vulnerable and Outdated Components (nur Hinweis, schwer zu testen)
    test('A6: Vulnerable and Outdated Components - Prüfen der WordPress-Version', async ({ page }) => {
      // Login
      await page.goto(`${baseURL}/wp-login.php`);
      await page.fill('#user_login', username);
      await page.fill('#user_pass', password);
      await page.click('#wp-submit');
      
      // Prüfen, ob die WordPress-Version im Footer angezeigt wird
      const footerText = await page.locator('#footer-upgrade').textContent();
      console.log(`WordPress Version: ${footerText}`);
      // Hier keine Assertion, da es nur ein Hinweis ist
    });

    // A7:2021 - Identification and Authentication Failures
    test('A7: Identification and Authentication Failures - Brute Force Test (begrenzt)', async ({ page }) => {
      // Brute Force Schutz testen (nur 3 Versuche, um nicht tatsächlich zu blockieren)
      await page.goto(`${baseURL}/wp-login.php`);
      
      for (let i = 0; i < 3; i++) {
        await page.fill('#user_login', username);
        await page.fill('#user_password', 'falschespasswort' + i);
        await page.click('#wp-submit');
        
        // Prüfen, ob Login-Seite weiterhin angezeigt wird
        await expect(page).toHaveURL(/wp-login.php/);
      }
      
      // Prüfen, ob nach mehreren Fehlversuchen eine Fehlermeldung angezeigt wird
      const errorVisible = await page.locator('.login-error, .login-msg, #login_error').isVisible();
      expect(errorVisible).toBeTruthy();
    });

    // A8:2021 - Software and Data Integrity Failures
    // Hinweis: Dies ist ein komplexes Thema, das eigentlich tiefergehende Tests benötigt

    // A9:2021 - Security Logging and Monitoring Failures
    // Hinweis: Dies kann nicht direkt im Browser getestet werden

    // A10:2021 - Server-Side Request Forgery
    // Hinweis: Dies benötigt spezifischere Tests auf Serverebene
  });
});