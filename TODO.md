# TODO — dormed-shop

## Wartungsmodus (Maintenance Mode)

**Ziel:** Wenn die Seite noch nicht live ist oder gewartet wird, soll das Frontend für normale Besucher gesperrt sein. Der Admin-Bereich bleibt erreichbar.

**Möglicher Ansatz:**
- Laravel `php artisan down --except=/admin` als Quick-Option (eingebaut)
- Oder eigene Middleware `MaintenanceMode` mit DB-Setting `shop.maintenance = true/false`
  - Leitet alle nicht-Admin-Routen auf eine statische Wartungsseite um
  - Admin-Panel bleibt normal erreichbar
  - Wartungsseite: kein Layout, kein Header, nur Nachricht + Logo

**Noch offen:**
- Wie wird der Modus geschaltet? (Admin-Setting oder Artisan-Command?)
- Soll die Wartungsseite per APP_ENV/.env steuerbar sein oder nur im Admin?

---

## Sicherheit (Backlog aus Code-Audit 2026-06-25)

### 🔴 is_admin aus User Fillable entfernen
**Datei:** `app/Models/User.php:12`
`is_admin` ist fillable → Mass Assignment Risiko. Aus Fillable entfernen, Änderung nur über separaten Admin-Command/Seeder erlauben.

### 🔴 Hardcoded E-Mail-Empfänger im StripeWebhookController
**Datei:** `app/Http/Controllers/StripeWebhookController.php`
`l.everding@dormed.de` und `l.everding@web.de` sind hardcoded. Aus Setting `shop.notification_emails` lesen (kommagetrennt).

### 🟡 Ratings ohne Auth + ohne Kaufverifizierung
**Datei:** `app/Http/Controllers/RatingController.php`, `routes/public/rating.php`
Jeder kann ohne Login beliebig viele Bewertungen erstellen. Route mit `auth`-Middleware absichern, optional Kaufnachweis prüfen.

### 🟢 FormRequest authorize() gibt überall true zurück
**Dateien:** `UpdateProductRequest`, `UpdateCategoryRequest`, `StoreRatingRequest`, etc.
Nur Route-Ebene schützt. Für feinere Permissions später Laravel Policies einführen.

---
