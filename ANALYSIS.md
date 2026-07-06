Komplettanalyse dormed-shop — Stand vor Release 1.0
(Arbeitsstand 04.07.2026 — erledigte Punkte werden entfernt)

## ✅ Bereits erledigt (Kurzprotokoll)
- Testsuite grün (Ursache war nicht-synchroner vendor; `composer install` genügte). Aktuell 139 Tests / 585 Assertions.
- **Stripe zu 100 % entfernt**: Webhook-Controller + Route + CSRF-Ausnahme, Checkout-Zweig, Settings (Controller/UI/verschlüsselte Keys), Share-Middleware (`stripeKey`), Seeder, Config (`services.stripe`, Provider-Block), .env(.example), `stripe/stripe-php`. Migration `2026_07_04_000001` droppt die Order-Spalten, löscht `stripe.%`-Settings und migriert `payment.provider` stripe→paypal. Shop ist jetzt PayPal + Rechnung.
- Kaputte Payment-Endpoint-Validierung gefixt (`UpdateCartPaymentMethodRequest` validiert jetzt gegen `shop.cart.providers`); `submit()` nimmt nur noch Rechnung an und weist andere Zahlarten mit Fehler ab (+ Test).
- Admin-Check-Endpoints (Mail/PayPal) auf POST umgestellt inkl. Frontend-Fetches (Mail-Fetch war nach der Routen-Umstellung kaputt/GET).
- Legacy-Seite `/admin/settings` (Index) entfernt → Redirect auf `/admin/settings/general`; damit auch die `index()`/`loadSettings()`-Duplizierung aufgelöst.
- Tote Dateien entfernt: `routes/manufacturer.php`, `routes/admin/*`, `routes/public/products.php`, leere Ordner, `CheckoutController::ADDRESS_FIELDS`, `config('shop.cart.shipping_methods')`.
- Fehlermeldungs-Leak in `checkMail` beseitigt (Log statt Exception-Text an Client).
- `guzzlehttp/guzzle` + `guzzlehttp/psr7` auf gepatchte Versionen (3 CVEs, composer audit jetzt sauber).
- `OrderItem.php` nach Paste-Unfall wiederhergestellt (doppelter Dateikopf).
- **Ex-Blocker 3 gefixt**: `PayPalController::afterPayment` leitet jetzt mit `paypal_order_id` zur Erfolgsseite (beide Pfade: bereits abgeschlossene Zahlung + frischer Capture). 6 neue Feature-Tests für den Return-Flow (`PayPalReturnFlowTest`). AGENTS.md Stripe-bereinigt, `STRIPE-TODO.md` gelöscht.

---

## 🔴 Release-Blocker

### 1. Zahlartauswahl-UI fehlt weiterhin (Rechnung ↔ PayPal) ✅
Der Endpoint `checkout.payment.update` funktioniert jetzt, aber **kein Frontend ruft ihn auf** — es gibt weder im Warenkorb noch auf der Confirm-Seite eine Auswahl. Default ist immer „Rechnung" (erste Methode), PayPal ist für Kunden nur erreichbar, wenn sie nie wechseln müssen — also faktisch gar nicht wählbar.
**Vorgehen:** Radio-Auswahl analog zur Versandart im Checkout bauen (`cart.payment_methods` liegt schon in den Props), PATCH auf `checkout.payment.update`, Feature-Test für den Wechsel + PayPal-Button-Anzeige auf Confirm.

### 2. Produktvarianten werden beim Kauf ignoriert
`addToCart()` sendet nur `product_id` + `quantity` (Show.svelte); der Warenkorb kennt keine Varianten — Kunde wählt „Variante XY für 99 €", bestellt Basisprodukt zum Basispreis.
**Vorgehen:** Entweder Varianten durchziehen (Cart + OrderItem um `variant_id`/Variantenpreis erweitern) oder für 1.0 komplett entfernen und in 1.1 sauber bauen.

## 🟠 Sicherheitslücken

### 4. Geseedeter Admin mit Passwort „password" ✅
`UserSeeder` legt `mail@dormed.de` mit Factory-Default `password` an, `CustomerSeeder` ebenso. Der `admin:add`-Command existiert bereits — der Seeder muss nur noch aus dem produktiven Pfad raus.
**Vorgehen:** `UserSeeder`/`CustomerSeeder` aus `DatabaseSeeder` nehmen (nur explizit für Dev aufrufen) oder per `app()->isProduction()` guarden.

### 5. env() zur Laufzeit bricht PayPal bei config:cache ✅
`PayPalService::buildConfig()`/`verifyWebhook()` nutzen `env()`-Fallbacks — mit `config:cache` liefern die `null`. Außerdem verifiziert `verifyWebhook` mit `config('paypal')`-Credentials statt der Settings-basierten; sind die Keys nur im Admin gepflegt, ist die Webhook-Verifikation tot.
**Vorgehen:** Fallbacks über `config/paypal.php` deklarieren, `verifyWebhook` denselben Client wie der Rest der Klasse nutzen lassen.

### 6. Erfolgsseite leakt Bestelldaten ohne Login ✅
`checkout.success?paypal_order_id=…` zeigt Adresse/E-Mail/Bestellung ohne Auth/Ownership (nur der `order_id`-Zweig prüft).
**Vorgehen:** Route hinter `auth` + `customer_id`-Check in beiden Zweigen.

### 7. PayPal-Capture ohne Ownership + Fehler-Interna an den Client ✅
`captureOrder` sucht Payments nur per `paypal_order_id` (fremde Captures triggerbar); `debug => $e->getMessage()` leakt Interna.
**Vorgehen:** `whereHas('order', customer_id)`-Scope; `debug`-Felder entfernen.

### 8. Settings-Update ohne Key-Whitelist ✅
`SettingController::update` schreibt jeden übermittelten Key in die DB.
**Vorgehen:** erlaubte Keys als Konstante whitelisten.

### 9. Anonyme Bewertungen ohne Bremse
`POST /products/{product}/ratings` unauthentifiziert, ungedrosselt, ohne Moderation.
**Vorgehen:** mindestens `throttle:3,1` + Honeypot; besser Verified-Buyer-Check + `is_approved`-Flag.

### 10. is_admin-Flag wird nie geprüft ✅ (Flag geprüft; 2FA-Spalten weiterhin ungenutzt, siehe Notiz)
`EnsureAdmin` prüft nur den Guard-Login; `is_admin` und die users-2FA-Spalten sind tot.
**Vorgehen:** Flag in Middleware prüfen — oder Feld + 2FA-Spalten entfernen (Simplifizierung).

## 🟡 Businesslogik

### 11. is_available wird nirgends durchgesetzt ✅
Nicht verfügbare/gelöschte Produkte sind list-, such- und bestellbar (Session-Snapshot).
**Vorgehen:** Scope in Listing/Suche/Add-to-Cart + finaler Recheck in `submit()`/PayPal-`createOrder`.

### 12. Preis-Snapshot im Warenkorb ist sessionlang eingefroren ✅
`CartService::add()` friert Preis/Name ein; Snapshot gehört erst in die Order.
**Vorgehen:** Cart auf `product_id => quantity` reduzieren, Preise live lesen — vereinfacht den Cart-State deutlich.

### 13. Jeder PayPal-Button-Klick erzeugt eine verwaiste Order ✅
**Vorgehen:** pending-Order wiederverwenden/canceln + Cleanup-Command für alte pending-Orders ohne Payment.

### 14. Dashboard-Umsatz zählt Test-/failed-/pending-Orders ✅
**Vorgehen:** `->where('is_test', false)->whereIn('status', ['paid', 'processing', 'completed'])`.

### 15. Kleinere Flow-Punkte ✅
- Race-Conditions: `markPaid()`/`captureOrder` nicht atomar → doppelte Mails möglich (Webhook + Return-URL). Atomarer Status-Übergang, nur bei `affected > 0` mailen.
- Mails synchron im Request → `ShouldQueue` auf die Mailables (Queue existiert).
- PayPal-Capture ohne Betragsabgleich gegen die Order — Defense-in-Depth, eine Zeile.

## 🔵 Simplifizierung & Code-Refinement

### 16. Adressvalidierung dreifach dupliziert ✅ (Backend vereinheitlicht; AddressForm bleibt als separate Client-Validierung bestehen)
`CheckoutController::updateAddress` vs. `AddressController::ADDRESS_RULES` vs. `AddressForm`.
**Vorgehen:** ein gemeinsames `AddressRules`-Objekt/FormRequest.

### 17. Settings- und Cart-Zugriffe cachen — jede Seite macht ~15 unnötige Queries ✅ (teilweise)
`HandleInertiaRequests::share()` ruft pro Request `CartService::cart()` + mehrere einzelne `Setting::get()` + Kategorien auf.
**Erledigt:** `Setting::get()`/`set()` memoisieren jetzt pro Prozess (= pro Request, da kein Octane läuft); `CartService` cached die ShippingMethod-Liste instanzweit, sodass `state()` und `shippingMethods()` sich innerhalb eines `cart()`-Aufrufs eine Query teilen statt zwei zu stellen.
**Bewusst nicht umgesetzt:** „Cart-Props partial" — der Cart wird aktuell app-weit als Shared-Prop für den Header (Artikelzähler) gebraucht; ihn per Inertia `lazy()/defer()` nur bei Bedarf zu laden hätte den Header auf praktisch jeder Seite betroffen und mehr Fläche für Regressionen als die anderen Punkte hier. Sauber nachrüstbar, aber als eigene Aufgabe mit UI-Verifikation, nicht nebenbei.

### 18. PHPStan konfiguriert, aber rot (Level 7, ~200 Fehler) ✅ (Baseline; Cart-DTO offen)
**Vorgehen:** Level senken und grün fixen oder Baseline einfrieren — dann in CI erzwingen. Cart-Array langfristig als DTO.

### 19. Beispiel-Tests entsorgen ✅
`tests/Feature/ExampleTest.php` + `tests/Unit/ExampleTest.php` (Löschen braucht laut Projektregeln explizites OK).

## 🧪 Testing — fehlende Abdeckung
- Zahlartwechsel (sobald UI existiert, Punkt 1)
- Alle PayPal-Endpoints: create/capture/afterPayment/webhook (`Http::fake()`/Service-Mock, Webhook mit gefakter Verifikation)
- Success-Page-Autorisierung (nach Punkt 6)
- Ziel: beide Bezahlwege je ein End-to-End-Feature-Test Cart → `status=paid` + `Mail::fake`-Assertions

## Empfohlene Reihenfolge
1. Blocker 1–3 (Zahlartauswahl-UI, Varianten-Entscheidung, PayPal-Return) — je mit Tests
2. Security-Paket: Seeder, env()→config (PayPal), Success-Auth, Capture-Ownership, Settings-Whitelist, Rating-Throttle
3. Businesslogik: is_available, Live-Preise, Dashboard-Filter, verwaiste Orders
4. Simplifizierung: Adressregeln, Settings-Cache, PHPStan + CI
