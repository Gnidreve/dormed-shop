# PayPal Webhook-Registrierung

## PayPal Developer Console

Für den Produktivbetrieb müssen in der [PayPal Developer Console](https://developer.paypal.com/dashboard/applications) folgende Webhooks registriert werden:

### 1. App auswählen oder erstellen

1. Gehe zu **My Apps & Credentials** → **REST API apps**
2. Wähle die entsprechende App für den Shop aus (oder erstelle eine neue)
3. Scrolle zum Abschnitt **Webhooks**

### 2. Webhook-URL eintragen

**Endpoint:** `https://dormed-shop.everding.solutions/paypal/webhook`

> **Hinweis:** Der Endpunkt benötigt **kein** CSRF-Token und **keine** Authentifizierung – die Verifikation erfolgt über die PayPal-Signatur (siehe `verifyWebhook()` in `PayPalService`).

### 3. Zu registrierende Ereignisse

| Event-Typ | Beschreibung | Verarbeitung |
|-----------|-------------|-------------|
| `CHECKOUT.ORDER.APPROVED` | Kunde hat die Zahlung im PayPal-Dialog bestätigt | Setzt Payment-Status auf `APPROVED` |
| `PAYMENT.CAPTURE.COMPLETED` | Zahlung wurde erfolgreich eingezogen | Setzt Payment + Order auf `COMPLETED` / `paid` |
| `PAYMENT.CAPTURE.REFUNDED` | Zahlung wurde (teil-)rückerstattet | Setzt Payment auf `REFUNDED` |
| `PAYMENT.CAPTURE.DENIED` | Zahlung wurde abgelehnt | Setzt Payment auf `FAILED` |

### 4. Webhook-ID speichern

Nach der Registrierung zeigt PayPal eine **Webhook-ID** an (beginnt mit `WH-`).
Diese muss in die Umgebungsvariablen eingetragen werden:

```
PAYPAL_WEBHOOK_ID=WH-xxxxxxxxxxxxx
```

In Coolify: **Environment-Variablen** → `PAYPAL_WEBHOOK_ID` eintragen.

### 5. Sandbox vs. Live

| Umgebung | App | Webhook-URL |
|----------|-----|-------------|
| **Sandbox** | Sandbox-App im Developer Dashboard | `https://dormed-shop.everding.solutions/paypal/webhook` |
| **Live** | Live-App | `https://dormed-shop.everding.solutions/paypal/webhook` |

Jede Umgebung benötigt eine eigene Webhook-Registrierung.

### 6. Webhook-Sicherheit

Der `verifyWebhook()`-Service überprüft jede eingehende Webhook-Notification anhand von:
- `PAYPAL-AUTH-ALGO`
- `PAYPAL-CERT-URL`
- `PAYPAL-TRANSMISSION-ID`
- `PAYPAL-TRANSMISSION-SIG`
- `PAYPAL-TRANSMISSION-TIME`
- Dem gesamten Request-Body

Die Prüfung erfolgt gegen die konfigurierte `PAYPAL_WEBHOOK_ID`.

### 7. Testen im Sandbox

1. Erstelle eine Sandbox-App im Developer Dashboard
2. Registriere die Webhook-URL mit Sandbox-Ereignissen
3. Verwende Sandbox-Testaccounts (Business + Buyer) unter [Sandbox-Testing](https://developer.paypal.com/dashboard/accounts)
4. Führe einen Testkauf im Shop durch (PayPal-Zahlungsmethode auswählen)
5. Prüfe die Logs: `storage/logs/laravel.log` nach `PayPal webhook received`

## Weblinks

- [PayPal Webhook-API Dokumentation](https://developer.paypal.com/docs/api/webhooks/v1/)
- [PayPal Webhook-Übersicht](https://developer.paypal.com/docs/api-basics/notifications/webhooks/)
- [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/applications)
