# TODO — dormed-shop

## IDEEN

- DPD Tracking in App per API einbauen

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
_(Stand 07/2026: weiterhin offen, Risiko aktuell gering — es gibt keinen Endpoint, der Request-Daten in User schreibt, und `EnsureAdmin` prüft das Flag inzwischen. Trotzdem sinnvoll.)_

### ✅ Hardcoded E-Mail-Empfänger im StripeWebhookController — erledigt

Empfänger kommen jetzt aus Setting `shop.notification_emails` (kommagetrennt, Fallback `mail.admin_address` → `mail.from.address`), zentral in `App\Support\Orders\OrderManager`. Im Admin unter Einstellungen → Mailversand pflegbar.

### 🟡 Ratings ohne Auth + ohne Kaufverifizierung

**Datei:** `app/Http/Controllers/RatingController.php`, `routes/public/rating.php`
Jeder kann ohne Login beliebig viele Bewertungen erstellen. Route mit `auth`-Middleware absichern, optional Kaufnachweis prüfen.
_(= ANALYSE-V2 S-C / ANALYSE-V1 Punkt 9 — auf Wiedervorlage bei Linus.)_

### 🟢 FormRequest authorize() gibt überall true zurück

**Dateien:** `UpdateProductRequest`, `UpdateCategoryRequest`, `StoreRatingRequest`, etc.
Nur Route-Ebene schützt. Für feinere Permissions später Laravel Policies einführen.

---

## Zahlungen (Backlog aus Payment-Audit 2026-06-28)

> ✅ **Komplett hinfällig (07/2026):** Stripe wurde vollständig entfernt (siehe ANALYSE-V1 Kurzprotokoll). Alle drei Punkte betrafen Stripe:
>
> - ~~#2 Stripe-Success-Seite verifiziert Zahlung nicht serverseitig~~ — Stripe weg; die PayPal-Success-Seite prüft inzwischen Auth + Ownership, Rechnung bleibt bewusst `pending`.
> - ~~#8 Stripe legt keinen `Payment`-Record an~~ — Stripe weg; PayPal schreibt `payments`, Rechnung hat systembedingt keinen Gateway-Record.
> - ~~#12 Nur ein Gateway gleichzeitig~~ — durch die Stripe-Entfernung gegenstandslos (Invoice + PayPal, gesteuert über Setting `payment.provider`). Die toten env-Flags `PAYMENT_STRIPE_ENABLED`/`PAYMENT_PAYPAL_ENABLED` wurden aus der .env entfernt.

---

## UI/UX — Kundenbereich

### 🔴 Kritisch

- ~~**Stripe-Checkout unvollständig**~~ — nur PayPal benötigt, kein Handlungsbedarf

- [✓] **Rechnungs-Download-Button entfernt** — `resources/js/pages/Checkout/Success.svelte`: auskommentierter Block + ungenutzte Imports entfernt

- [✓] **Produktverfügbarkeit** — `is_available` Boolean in DB (`migration`), Model, Admin-Toggle in `Edit.svelte`, dynamische Anzeige + Button-Sperre in `Products/Show.svelte`

### 🟡 Moderat

- [ ] **Produktfilter fehlen** — `resources/js/pages/Products/Index.svelte`: Nur Sortierung vorhanden. Keine Filter nach Preis, Hersteller, Kategorie oder Rating.

- [✓] **Bestelldetails** — Detailseite implementiert (`CustomerOrderController::show` + `settings/Orders/Show.svelte`, aus der Liste verlinkt). Keine Pagination — wie gewollt.

- [✓] **Produkt-Bilder im Warenkorb** — `CartService` liefert `image_url`, Warenkorb-Seite und CartSheet rendern echte Produktbilder (mit Platzhalter-Fallback).

- [ ] **Registrierung** — `resources/js/pages/auth/Register.svelte`: Kein Passwort-Stärke-Indikator, keine AGB-Akzeptanz-Checkbox.

- [✓] **Kontaktseite gelöscht** — `Kontakt.svelte` entfernt, Footer verlinkt bereits auf `dormed.de/kontakt` (extern)

- ~~**Ähnliche Produkte**~~ — kein Schema vorhanden, kein Handlungsbedarf

### 🟢 Minor (UX)

- [✓] **Labels eingedeutscht** — `settings/Profile.svelte` + `settings/Security.svelte`: alle sichtbaren Texte (Titel, Labels, Platzhalter, Buttons) auf Deutsch

- [✓] **Such-Dropdown: "Keine Ergebnisse"** — leerer Zustand existiert („Keine Ergebnisse für …").

- [ ] **Breadcrumb auf Shop-Seiten** — _teilweise:_ Produkt-Detailseite hat Breadcrumb (Alle Produkte → Kategorie → Produkt); Kategorie-Seite noch nicht.

- [ ] **Bestellzusammenfassung im Checkout** — `resources/js/pages/Checkout/Confirm.svelte`: Produktliste fehlt in der finalen Zusammenfassung (nur Preise sichtbar).

- [ ] **AGB/Datenschutz Versionierung** — `resources/js/pages/AGB.svelte`, `Datenschutz.svelte`: Kein Datum / keine Versionsnummer sichtbar.

---

## UI/UX — Admin-Bereich (Mitarbeiter)

### 🔴 Kritisch

- [✓] **Bestelldetail-Seite** — `Admin/Orders/Show.svelte` existiert, aus der Liste verlinkt, inkl. Status-Änderung (mit Kunden-Benachrichtigung) und PayPal-Refund.

- [ ] **Bulk-Aktionen ohne Funktion** — `resources/js/pages/Admin/Products/Index.svelte`: Checkboxes für Mehrfachauswahl vorhanden, aber keine Aktion dahinter (kein Bulk-Delete, kein Bulk-Update).

### 🟡 Moderat

- [ ] **Bestellungen: kein Status-Filter** — `resources/js/pages/Admin/Orders/Index.svelte`: Kein Filter nach Status (Offen / Bezahlt / Storniert), kein Datum-Filter.

- [ ] **Dashboard: nur Basis-Stats** — `resources/js/pages/Admin/Dashboard.svelte`: Nur 2 Charts (Orders + Revenue). Keine Top-Produkte, keine Top-Kunden, keine Conversion-Rate.

- [✓] **Produkt anlegen** — „Neues Produkt"-Button existiert in `Admin/Products/Index.svelte` (→ Create-Seite).

- [ ] **Kategorie-Slug nicht editierbar** — `resources/js/pages/Admin/Categories/Index.svelte`: Slug wird angezeigt aber kann nicht inline bearbeitet werden.

- [ ] **Kunden-Filter** — `resources/js/pages/Admin/Customers/Index.svelte`: Nur Name-Suche. Kein Filter nach Verifizierungs-Status oder Registrierungsdatum.

### 🟢 Minor (UX)

- [ ] **Dashboard: Custom-Date ohne Validierung** — `resources/js/pages/Admin/Dashboard.svelte`: Leere From/To-Felder führen zu keinem Fehler.

- [ ] **Upload-Fehler nicht angezeigt** — `resources/js/pages/Admin/Products/Edit.svelte`: Wenn Bild-Upload fehlschlägt, gibt es kein visuelles Feedback.

---
