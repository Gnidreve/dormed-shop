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
-> Nur PayPal kein Stripe. ich sorge dafür das nicht beides aktiviert ist in env und später Datenbank

---

## UI/UX — Kundenbereich

### 🔴 Kritisch

- ~~**Stripe-Checkout unvollständig**~~ — nur PayPal benötigt, kein Handlungsbedarf

- [✓] **Rechnungs-Download-Button entfernt** — `resources/js/pages/Checkout/Success.svelte`: auskommentierter Block + ungenutzte Imports entfernt

- [✓] **Produktverfügbarkeit** — `is_available` Boolean in DB (`migration`), Model, Admin-Toggle in `Edit.svelte`, dynamische Anzeige + Button-Sperre in `Products/Show.svelte`

### 🟡 Moderat

- [ ] **Produktfilter fehlen** — `resources/js/pages/Products/Index.svelte`: Nur Sortierung vorhanden. Keine Filter nach Preis, Hersteller, Kategorie oder Rating.

- [ ] **Bestelldetails fehlen** — `resources/js/pages/settings/Orders.svelte`: Liste zeigt Bestellungen, aber kein Link zu einer Detailseite. Keine Paginierung.
-> Keine Pagination. Aber die Detailsseite muss noch implementiert werden

- [ ] **Produkt-Bilder im Warenkorb** — `resources/js/pages/Checkout/Index.svelte`: Bilder sind leere Placeholder-Divs, keine echten Produktbilder.

- [ ] **Registrierung** — `resources/js/pages/auth/Register.svelte`: Kein Passwort-Stärke-Indikator, keine AGB-Akzeptanz-Checkbox.

- [✓] **Kontaktseite gelöscht** — `Kontakt.svelte` entfernt, Footer verlinkt bereits auf `dormed.de/kontakt` (extern)

- ~~**Ähnliche Produkte**~~ — kein Schema vorhanden, kein Handlungsbedarf

### 🟢 Minor (UX)

- [✓] **Labels eingedeutscht** — `settings/Profile.svelte` + `settings/Security.svelte`: alle sichtbaren Texte (Titel, Labels, Platzhalter, Buttons) auf Deutsch

- [ ] **Such-Dropdown: "Keine Ergebnisse"** — `resources/js/components/ShopHeader.svelte`: Kein leerer Zustand wenn Suche nichts findet.

- [ ] **Breadcrumb auf Shop-Seiten** — Keine Orientierungshilfe auf Produkt-Detail- und Kategorie-Seiten.

- [ ] **Bestellzusammenfassung im Checkout** — `resources/js/pages/Checkout/Confirm.svelte`: Produktliste fehlt in der finalen Zusammenfassung (nur Preise sichtbar).

- [ ] **AGB/Datenschutz Versionierung** — `resources/js/pages/AGB.svelte`, `Datenschutz.svelte`: Kein Datum / keine Versionsnummer sichtbar.

---

## UI/UX — Admin-Bereich (Mitarbeiter)

### 🔴 Kritisch

- [ ] **Bestelldetail-Seite fehlt** — `resources/js/pages/Admin/Orders/`: Keine Show-Seite (oder nicht verlinkt). Admin kann Bestellungen nicht im Detail einsehen.

- [ ] **Bulk-Aktionen ohne Funktion** — `resources/js/pages/Admin/Products/Index.svelte`: Checkboxes für Mehrfachauswahl vorhanden, aber keine Aktion dahinter (kein Bulk-Delete, kein Bulk-Update).

### 🟡 Moderat

- [ ] **Bestellungen: kein Status-Filter** — `resources/js/pages/Admin/Orders/Index.svelte`: Kein Filter nach Status (Offen / Bezahlt / Storniert), kein Datum-Filter.

- [ ] **Dashboard: nur Basis-Stats** — `resources/js/pages/Admin/Dashboard.svelte`: Nur 2 Charts (Orders + Revenue). Keine Top-Produkte, keine Top-Kunden, keine Conversion-Rate.

- [ ] **Produkt anlegen fehlt** — `resources/js/pages/Admin/Products/Index.svelte`: Kein "Neues Produkt"-Button sichtbar.

- [ ] **Kategorie-Slug nicht editierbar** — `resources/js/pages/Admin/Categories/Index.svelte`: Slug wird angezeigt aber kann nicht inline bearbeitet werden.

- [ ] **Kunden-Filter** — `resources/js/pages/Admin/Customers/Index.svelte`: Nur Name-Suche. Kein Filter nach Verifizierungs-Status oder Registrierungsdatum.

### 🟢 Minor (UX)

- [ ] **Dashboard: Custom-Date ohne Validierung** — `resources/js/pages/Admin/Dashboard.svelte`: Leere From/To-Felder führen zu keinem Fehler.

- [ ] **Upload-Fehler nicht angezeigt** — `resources/js/pages/Admin/Products/Edit.svelte`: Wenn Bild-Upload fehlschlägt, gibt es kein visuelles Feedback.

---
