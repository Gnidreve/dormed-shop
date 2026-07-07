Vollanalyse V2 dormed-shop — zweite Schleife nach Abarbeitung von ANALYSIS.md
(Arbeitsstand 07.07.2026 — erledigte Punkte werden abgehakt)

Basis: Alle Punkte aus ANALYSIS.md gelten als erledigt bzw. liegen bei Linus auf
Wiedervorlage (Zahlartauswahl-UI ist inzwischen umgesetzt, siehe Businesslogik 6;
Varianten und Rating-Bremse weiterhin offen auf Wiedervorlage).
Teststatus bei Analyse: 174 Tests / 712 Assertions grün, PHPStan grün (Baseline),
composer audit + npm audit sauber.

## ✅ Bereits erledigt (Kurzprotokoll)

- **B1 + B2 + S1 gefixt** (07.07.2026): Gemeinsamer `fetchJson()`-Helper in
  `resources/js/lib/http.ts` (liest `XSRF-TOKEN`-Cookie, sendet `X-XSRF-TOKEN` —
  derselbe Mechanismus wie Inertias XHR-Client). PayPalButton auf Helper +
  Wayfinder-Routen umgestellt (`getCsrfToken()`/Meta-Tag entfernt), Confirm-
  `saveAddress` auf idiomatisches `router.patch` umgebaut (toter 302-Zweig weg),
  Admin Mail/Payment/Orders-Show nutzen ebenfalls den Helper statt dreier
  Cookie-Parsing-Kopien. Nebenbei: Fehler-Key-Mismatch behoben (Server liefert
  `shipping_address.first_name`, AddressForm liest `shipping.first_name` —
  Validierungsfehler wären nie an den Feldern erschienen). Verifiziert per
  ESLint, svelte-check (kein neuer Fehler), `npm run build`, Checkout-Tests
  30/30. **Manueller Browser-Test gegen PayPal-Sandbox steht noch aus** (CSRF
  ist in PHPUnit deaktiviert, serverseitig nicht testbar).
- **Businesslogik 1 + JSON-LD-Upgrade erledigt** (07.07.2026): Entscheidung —
  Detailseiten nicht verfügbarer Produkte bleiben online (SEO/Bookmarks), das
  UI zeigte den „nicht verfügbar"-Zustand bereits korrekt; Sitemap behält
  bewusst alle Produkte. JSON-LD in `Products/Show.svelte` jetzt vollständig:
  `availability` dynamisch (InStock/OutOfStock), `aggregateRating` (Sterne-
  Snippets), `sku`, absolute `url`, plus `<`-Escaping gegen Script-Breakout
  (S-C erster Punkt). Feature-Test dokumentiert die Entscheidung
  (`test_show_keeps_unavailable_products_reachable`). Lint/Build grün,
  Shop-Tests 15/15.
- **B3 revidiert statt gefixt** (07.07.2026, Entscheidung Linus): Die
  Orphan-Cleanup-Mechanik (Empfehlung aus ANALYSIS.md Punkt 13) für 1.0
  vollständig entfernt — `CleanupOrphanedOrders`-Command, Scheduler-Eintrag
  und Testdatei gelöscht. Der klick-basierte
  `cancelStalePendingPayPalOrders()` bleibt (kunden-/paypal-scoped, unkritisch).
  Suite danach 171/171 grün, `schedule:list` leer.
- **B4 + S-A gefixt** (07.07.2026): `Product::orderItems()`-Relation ergänzt
  (Fehler war in der PHPStan-Baseline versteckt — Eintrag entfernt) + 2
  Destroy-Tests. `orders.customer_id` per Migration auf nullable +
  `nullOnDelete` umgestellt: Kontolöschung entkoppelt Orders nur noch, statt
  Historie zu kaskadieren (GoBD; Snapshots liegen auf der Order). Suite
  174/174, PHPStan grün, Migration beidseitig auf SQLite verifiziert.
- **Businesslogik 2 gefixt** (07.07.2026): Refund-/Denied-Webhooks setzen
  jetzt Payment + Order konsistent; dabei entdeckt und mitgefixt, dass der
  Refund-Handler wegen Refund-ID ≠ Capture-ID nie gematcht hatte (Capture-ID
  kommt aus dem `up`-Link). Neue `PayPalWebhookTest`-Datei, Suite 178/178.
- **Businesslogik 3 + 5 erledigt** (07.07.2026): `createFromCart` in
  `DB::transaction` (+ Rollback-Test). Varianten voll durchgezogen
  (Entscheidung Linus) — Details unter Businesslogik 5. Suite 187/187,
  PHPStan grün, Build grün. Damit ist auch der letzte ANALYSIS.md-Blocker
  (Punkt 2, Varianten) vom Tisch; offen auf Wiedervorlage bleibt nur noch
  die Rating-Bremse (Punkt 9 / S-C).

---

## 🔴 Release-Blocker

### B1. PayPal-Buttons senden leeren CSRF-Token → 419 ✅

`PayPalButton.svelte` las `meta[name="csrf-token"]` — das Meta-Tag existiert in
`resources/app.blade.php` nicht. `getCsrfToken()` lieferte `''`, die fetch-POSTs
auf `/paypal/order/create` + `/capture` liefen in Laravels CSRF-Prüfung (419).
Feature-Tests merkten es nicht (CSRF-Middleware in Tests deaktiviert).
**Erledigt:** `fetchJson()`-Helper mit XSRF-Cookie (siehe Kurzprotokoll).

### B2. „Adresse speichern" auf Confirm sendet gar keinen CSRF-Token ✅

`Confirm.svelte::saveAddress` machte einen rohen fetch-PATCH ohne Token → 419,
nur generische Fehlermeldung. Zusätzlich toter `resp.status === 302`-Zweig.
**Erledigt:** `router.patch` mit `onError`-Mapping der Fehler-Keys (siehe
Kurzprotokoll).

### B3. Täglicher Cleanup storniert alle Rechnungsbestellungen ✅

(revidiert: Mechanik entfernt)
`orders:cleanup-orphaned` (täglich via Scheduler) cancelte jede `pending`-Order
älter als 24 h ohne COMPLETED-Payment — ohne Filter auf
`payment_method = 'paypal'`. Rechnungskäufe bleiben bewusst tagelang `pending`
bis die Überweisung eintrifft → jeder Rechnungskauf wäre nach 24 h automatisch
storniert worden.
**Revidiert (07.07.2026, Entscheidung Linus):** Die Cleanup-Mechanik aus
ANALYSIS.md Punkt 13 wird für 1.0 vollständig entfernt statt gefixt —
Command, Scheduler-Eintrag und Tests gelöscht (`schedule:list` ist jetzt leer).
**Bewusst behalten:** der klick-basierte
`PayPalController::cancelStalePendingPayPalOrders()` — der ist kunden- und
paypal-scoped, verhindert die Orphan-Anhäufung an der Quelle und war nie das
Problem. Verwaiste pending-PayPal-Orders anderer Kunden bleiben bis auf
Weiteres einfach stehen (im Admin sichtbar); ein Auto-Cleanup kann in 1.1
sauber mit `payment_method`-Filter zurückkommen.

### B4. Produkt-Löschen im Admin wirft immer 500 ✅

`Admin\ProductController::destroy` rief `$product->orderItems()` auf — die
Relation existierte im Product-Model nicht → `BadMethodCallException` bei jedem
Löschversuch. Pikant: der Fehler war in der PHPStan-Baseline als Ignore
eingefroren statt aufzufallen.
**Erledigt (07.07.2026):** `orderItems(): HasMany` am Product ergänzt (inkl.
Generics-PHPDoc), Baseline-Eintrag entfernt, zwei Feature-Tests
(`test_destroy_deletes_product_without_orders` /
`test_destroy_refuses_product_that_was_ordered`).

## 🟠 Sicherheitslücken

### S-A. Kontolöschung vernichtet die Bestellhistorie (GoBD/§147 AO) ✅

`orders.customer_id` war `cascadeOnDelete`, und jeder Kunde kann sein Konto
über `settings/profile` selbst löschen → Orders, OrderItems und Payments
wurden mitgelöscht, inkl. bezahlter Rechnungen.
**Erledigt (07.07.2026):** Migration
`keep_orders_when_customer_is_deleted` — FK per dropForeign neu aufgebaut:
`customer_id` jetzt nullable mit `nullOnDelete`. Beim Konto-Löschen wird die
Order nur entkoppelt (customer_id = null); Rechnungsdaten (Adresse, Beträge,
Positionen, Payments) bleiben als Snapshot auf der Order erhalten — DSGVO-
Löschung des Kontos und Aufbewahrungspflicht schließen sich so nicht aus
(Art. 17 Abs. 3 lit. b). Admin-UI war bereits null-sicher
(`customer?.name`, `{#if order.customer}`), `OrderManager::sendConfirmations`
loggt bei fehlendem Kunden statt zu crashen. Migration auf SQLite in beide
Richtungen verifiziert; Test:
`test_deleting_account_keeps_the_order_history`.

### S-B. E-Mail-Verifikation ist ein No-op

`Features::emailVerification()` aktiv, zwei Routen tragen `verified`-Middleware —
aber `Customer` implementiert `MustVerifyEmail` nicht (Import auskommentiert).
Es wird nie eine Verifikationsmail verschickt, die Middleware lässt jeden durch.
**Vorgehen:** Entweder Interface implementieren (dann auch für Checkout erwägen)
oder Feature + Middleware entfernen (Simplifizierung). Jetziger Zustand täuscht
eine Schutzschicht vor.

### S-C. Kleinere Härtungen

- **JSON-LD-Injection (niedrig): ✅** `Products/Show.svelte` renderte
  `JSON.stringify(schema)` per `{@html}` ohne Escaping — mit Businesslogik 1
  erledigt (`<` wird als `\u003c` escaped).
- **Settings-Werte unvalidiert:** Key-Whitelist steht, aber z. B. `payment.mode`
  akzeptiert jeden String und fällt still auf den env-Modus zurück.
  `Rule::in(['sandbox','live'])` für die Enum-Keys.
- **Kein Throttle auf `paypal/order/create`:** jeder Klick = PayPal-API-Call +
  DB-Order. Auth-pflichtig, aber `throttle:10,1` kostet nichts.
- **`afterPayment` ohne Auth/Ownership:** Capture durch Dritte mit bekanntem
  Token triggerbar (Geld fließt an den Shop, Success-Seite prüft Ownership —
  Risiko gering). `auth`-Middleware wäre unschädlich, PayPal leitet den
  eingeloggten Käufer zurück.
- Bekannt, auf Wiedervorlage (ANALYSIS.md Punkt 9): **Ratings ohne
  Throttle/Moderation** — `POST /products/{product}/ratings` weiter anonym und
  ungedrosselt.

## 🟡 Businesslogik

### 1. is_available gilt nicht für Detailseite und Sitemap ✅ (bewusste Strategie + JSON-LD-Fix)

`ProductController::show` hat keinen `available()`-Scope und der
`SitemapController` listet _alle_ Produkte; JSON-LD sagte hart `InStock`.
**Entscheidung (07.07.2026):** Detailseiten bleiben bewusst online — das UI
zeigt „Derzeit nicht verfügbar" + disabled Button (war schon korrekt), Kauf
scheitert serverseitig an der Cart-Validierung. Sitemap behält bewusst alle
Produkte. JSON-LD meldet jetzt dynamisch InStock/OutOfStock (+ aggregateRating,
sku, url, Escaping). Test: `test_show_keeps_unavailable_products_reachable`.

### 2. PayPal-seitige Refunds/Denials aktualisieren die Order nicht ✅

Webhook-Handler `handleCaptureRefunded/Denied` setzten nur den Payment-Status;
die Order blieb `paid`. Beim Fix kam ein tieferer Bug zutage:
`PAYMENT.CAPTURE.REFUNDED` liefert eine *Refund*-Resource — `resource.id` ist
die Refund-ID, nicht die Capture-ID. Der alte Handler matchte also **nie**,
Dashboard-Refunds kamen gar nicht erst an.
**Erledigt (07.07.2026):** Capture-ID wird jetzt aus dem `up`-Link der
Refund-Resource extrahiert (Fallback: `resource.id`); gemeinsamer
`markPaymentAndOrder()`-Pfad setzt Payment **und** Order konsistent
(REFUNDED/refunded bzw. FAILED/failed) — identisch zum Admin-Refund-Button.
Neue Testdatei `PayPalWebhookTest` (Verifikation abgelehnt, Refund, Denied,
unbekannte Capture-ID).

### 3. OrderManager::createFromCart läuft ohne Transaktion ✅

Schlug ein Item-Insert fehl, blieb eine Order ohne Items stehen.
**Erledigt (07.07.2026):** `DB::transaction()` um Order- + Items-Erstellung;
Rollback-Test `test_create_from_cart_rolls_back_order_when_an_item_fails`.

### 4. Kein Bestandskonzept

Nur `is_available`-Flag, Menge bis 99 pro Produkt frei wählbar. Wenn das für
1.0 die bewusste Entscheidung ist: ok — hier nur festgehalten.

### 5. Varianten werden beim Kauf ignoriert ✅ (voll durchgezogen)

Show.svelte zeigte Variantenpreis an, `addToCart` sendete nur `product_id` —
Kunde wählte „Variante XY für 99 €", bestellte Basisprodukt zum Basispreis.
**Erledigt (07.07.2026, Entscheidung Linus: voll durchziehen statt ausblenden):**

- Cart-Lines sind jetzt `productId` bzw. `productId:variantId` — dasselbe
  Produkt kann pro Variante als eigene Zeile im Warenkorb liegen. Alte
  Sessions bleiben kompatibel (Normalisierung in `state()`).
- `CartService::items()` löst Variantenpreis + Label auf; das Label wird Teil
  des Zeilennamens („Produkt – Label"), wodurch Cart-UI, Checkout, Order-
  Snapshot und Mails ohne weitere Anpassung konsistent sind. Keine Migration
  nötig: `order_items.product_name`/`unit_price` snapshotten wie gehabt.
- Validierung: `variant_id` muss zum Produkt gehören; Produkte **mit**
  Varianten sind ohne Variantenwahl nicht bestellbar (after-Hook im
  `AddCartItemRequest`). Gelöschte Varianten machen die Zeile unbuyable
  (`hasUnavailableItems` läuft jetzt über die Zeilen-Logik).
- Routes: `PATCH/DELETE /cart/items/{product}/{variant?}` mit
  Belongs-To-Check; Frontend (Show, CartSheet, Checkout-Index) sendet
  `variant_id` bzw. adressiert Zeilen über `line_key`.
- 8 neue Feature-Tests in `CartFlowTest` (Variantenpreis, Pflicht-Auswahl,
  Fremd-Variante, getrennte Zeilen, Update/Remove, Order-Snapshot,
  gelöschte Variante blockiert Checkout).

### 6. Zahlartauswahl-UI ✅ (war ANALYSIS.md Blocker 1)

Radio-Auswahl existiert in `Confirm.svelte` inkl. PATCH auf
`checkout.payment.update`; durch B1/B2-Fix jetzt auch im Browser durchgängig
testbar.

## 🔵 Simplifizierung & Code-Refinement

### S1. Vier verschiedene Fetch/CSRF-Implementierungen ✅

PayPalButton (Meta-Tag, kaputt), Confirm (keiner, kaputt), Admin
Mail/Payment/Orders (XSRF-Cookie, 3× copy-paste).
**Erledigt:** ein gemeinsamer `fetchJson()`-Helper in `resources/js/lib/http.ts`,
alle fünf Call-Sites umgestellt (Confirm idiomatisch via `router.patch`).

### S2. Ungenutzte ShopHeader-Varianten

`ShopHeader-left-align.svelte` und `ShopHeader-with-hover.svelte` werden nirgends
importiert. **Entscheidung Linus: die Hover-Variante bleibt bewusst erhalten
und darf nicht gelöscht werden.** Left-align bei Gelegenheit klären.

### S3. Capture-Logik doppelt im PayPalController

`captureOrder()` und `afterPayment()` duplizieren Betragsabgleich +
COMPLETED-Handling fast zeilengleich → private Methode
`completeCapture(Payment $payment, array $response): bool` extrahieren.

### S4. StoreProductRequest == UpdateProductRequest

Identische Rules — eine Klasse reicht.

### S5. Default-Adress-Queries dupliziert

`CheckoutController::prefillAddressFromProfile` und `AddressController` bauen
dieselben „default shipping/billing"-Queries → Relation/Helper am Customer
(z. B. `defaultShippingAddress()`).

### S6. Kleinkram

- Ungenutzte `total`-Prop in PayPalButton (`_total`).
- `checkout.payment.update` als einzige Checkout-Route ohne `auth`-Middleware
  (harmlos, Session-scoped — aber inkonsistent).
- `sessions`-Tabelle hat `user_id`-Spalte, Auth läuft über `customers` —
  kosmetisch.

## 🚀 Launch-Checkliste (Deployment)

1. **Queue-Worker ist Pflicht:** beide Mails sind `ShouldQueue`,
   `QUEUE_CONNECTION=database` — ohne laufenden `php artisan queue:work`
   (Supervisor/systemd) geht keine einzige Bestellmail raus.
2. ~~Scheduler-Cron für den Cleanup~~ — obsolet, B3-Mechanik für 1.0 entfernt;
   aktuell existieren keine Scheduled Tasks (Cron erst nötig, wenn wieder
   welche dazukommen).
3. `php artisan storage:link` (Produktbilder auf public-Disk).
4. `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`;
   `config:cache` ist seit dem env()-Fix aus Schleife 1 sicher.
5. PayPal-Webhook-ID im Admin pflegen — ohne sie lehnt `verifyWebhook()` alle
   Webhooks ab (nur im Log sichtbar).
6. Backup-Strategie für die Datenbank klären (aktuell SQLite).

## 🧪 Testing — fehlende Abdeckung

- Manueller Browser-Test PayPal-Sandbox + „Adresse speichern" (nach B1/B2-Fix)
- ~~B3: Cleanup verschont invoice-Orders~~ (obsolet — Mechanik entfernt)
- ~~B4: Produkt-Löschen mit/ohne Bestellungen~~ ✅ (2 Tests in ProductEditTest)
- ~~Webhook Refund/Denied → Order-Status~~ ✅ (PayPalWebhookTest, 4 Tests)

## Empfohlene Reihenfolge

1. ~~B1 + B2 via S1 (gemeinsamer Fetch-Helper)~~ ✅
2. ~~Businesslogik 1 (is_available-Strategie + korrektes JSON-LD inkl.
   S-C-Escaping)~~ ✅
3. ~~B3~~ ✅ (revidiert: Mechanik entfernt) → ~~B4 (Relation + Test)~~ ✅
4. ~~S-A (FK-Migration)~~ ✅ → S-B (Entscheidung Verifikation)
5. Rest (S-C, Businesslogik 2–3, Simplifizierung) nach Gelegenheit
