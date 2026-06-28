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

### ✅ Hardcoded E-Mail-Empfänger im StripeWebhookController — erledigt
Empfänger kommen jetzt aus Setting `shop.notification_emails` (kommagetrennt, Fallback `mail.admin_address` → `mail.from.address`), zentral in `App\Support\Orders\OrderManager`. Im Admin unter Einstellungen → Mailversand pflegbar.

### 🟡 Ratings ohne Auth + ohne Kaufverifizierung
**Datei:** `app/Http/Controllers/RatingController.php`, `routes/public/rating.php`
Jeder kann ohne Login beliebig viele Bewertungen erstellen. Route mit `auth`-Middleware absichern, optional Kaufnachweis prüfen.

### 🟢 FormRequest authorize() gibt überall true zurück
**Dateien:** `UpdateProductRequest`, `UpdateCategoryRequest`, `StoreRatingRequest`, etc.
Nur Route-Ebene schützt. Für feinere Permissions später Laravel Policies einführen.

---

## Zahlungen (Backlog aus Payment-Audit 2026-06-28)

> Stripe ist aktuell nicht kriegsentscheidend — diese Punkte sind bewusst zurückgestellt.

### 🟡 Stripe-Success-Seite verifiziert Zahlung nicht serverseitig (#2)
**Datei:** `app/Http/Controllers/CheckoutController.php::success`
Die Erfolgsseite rendert anhand der `session_id`, ohne den realen Zahlungs-/Sessionstatus bei Stripe abzufragen. Order wird ausschließlich vom Webhook auf `paid` gesetzt. Fällt der Webhook aus, sieht der Kunde „Erfolg", die Order bleibt aber `pending` und es geht keine Bestätigung raus. Lösung: in `success()` bei Stripe-Orders die Checkout-Session via API laden und Status prüfen (oder als bezahlt markieren, falls `payment_status === 'paid'`).

### 🟡 Stripe legt keinen `Payment`-Record an (#8)
PayPal schreibt in die `payments`-Tabelle, Stripe nur `stripe_*`-Spalten auf `orders`, Invoice gar nichts. Folge: Admin-Bestelldetail zeigt für Stripe-Zahlungen keine Transaktionszeile. Lösung: einheitliches `Payment`-Modell für alle Gateways (PaymentIntent-ID, Betrag, Status), in `OrderManager` zentralisiert.

### 🟢 Nur ein Gateway gleichzeitig (#12) — bewusst so
Per Design ist immer Invoice + genau **ein** Gateway (PayPal *oder* Stripe) aktiv (Setting `payment.provider`). Falls beide gleichzeitig angeboten werden sollen, müsste `CartService::paymentMethods()` mehrere aktive Provider zulassen und das Admin-Setting auf Mehrfachauswahl umgestellt werden.

---
