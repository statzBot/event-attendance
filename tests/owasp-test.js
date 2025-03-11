// @ts-check
const { test, expect } = require('@playwright/test');

// OWASP Top 10 Security Tests
test.describe('OWASP Top 10 Security Tests', () => {
  // Benutzeranmeldedaten
  const username = 'Teddy';
  const password = 'eva123';
  const baseURL = 'http://localhost:8888/neu/';
  
  // A1:2021 - Broken Access Control
  test('A1: Broken Access Control - Versuche, ohne Anmeldung auf Admin-Seiten zuzugreifen', async ({ page }) => {
    // Ausloggen falls angemeldet
    await page.goto(`${baseURL}/wp-login.php?action=logout`);
    await page.goto(`${baseURL}/wp-admin/admin.php?page=event-attendance`);
    
    // Sollte zur Login-Seite umgeleitet werden
    await expect(page).toHaveURL(/wp-login.php/);
  });

  // A3:2021 - Injection (SQL, XSS)
  test('A3: Injection - XSS Test bei Kontaktformular', async ({ page }) => {
    // XSS versuch in der Suche
    await page.goto(`${baseURL}`);
    
    // Suche mit XSS Payload
    const xssPayload = '<script>alert("XSS")</script>';
    await page.fill('input[name="s"]', xssPayload);
    await page.press('input[name="s"]', 'Enter');
    
    // Auf Dialog vorbereiten (sollte nicht auftauchen, wenn XSS verhindert wird)
    page.on('dialog', async dialog => {
      console.log(`Dialog appeared: ${dialog.message()}`);
      await dialog.dismiss();
      // Wenn ein Dialog erscheint, ist die Website für XSS anfällig
      throw new Error('XSS vulnerability detected!');
    });
    
    // Verifizieren, dass der Payload im HTML escaped wurde
    const pageContent = await page.content();
    const rawXssInContent = pageContent.includes('<script>alert("XSS")</script>');
    expect(rawXssInContent).toBeFalsy();
  });

  // A7:2021 - Identification and Authentication Failures
  test('A7: Identification and Authentication Failures - Brute Force Test (begrenzt)', async ({ page }) => {
    // Brute Force Schutz testen (nur 3 Versuche, um nicht tatsächlich zu blockieren)
    await page.goto(`${baseURL}/wp-login.php`);
    
    for (let i = 0; i < 3; i++) {
      await page.fill('#user_login', username);
      await page.fill('#user_pass', 'falschespasswort' + i);
      await page.click('#wp-submit');
      
      // Prüfen, ob Login-Seite weiterhin angezeigt wird
      await expect(page).toHaveURL(/wp-login.php/);
    }
    
    // Prüfen, ob nach mehreren Fehlversuchen eine Fehlermeldung angezeigt wird
    const errorVisible = await page.locator('.login-error, .login-msg, #login_error').isVisible();
    expect(errorVisible).toBeTruthy();
  });
});