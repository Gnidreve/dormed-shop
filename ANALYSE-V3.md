Vollanalyse V3 dormed-shop — dritte Schleife, kompletter 1.0-Readiness-Check
(Arbeitsstand 09.07.2026 — erledigte Punkte werden abgehakt)

Basis: ANALYSE-V2 gilt als abgearbeitet bis auf die dort explizit offen
gelassenen Punkte (Rating-Bremse, S-C-Resthärtungen, Simplifizierungen S2–S6).
Seit V2 sind ~40 Commits dazugekommen: synchroner Mailversand + fester
Betreiber-Empfänger, PaymentMode nur noch aus APP_ENV, Status-Vereinfachung
(`processing`/`completed` entfernt, einzige Admin-Aktion „Zahlungseingang
bestätigen"), eigenes `PaymentConfirmationMail`, PayPal-Button-Overlay,
Cookie-Banner, Dashboard-Umbau (ein Chart, Zeiträume, Umsatz ohne
`is_test`-Filter), Reviews-Redesign + Bewertungsformular deaktiviert,
SEO-Arbeiten (statische Seiten unter `/informationen` + `/unternehmen`,
AppHead überall, Favicon-Umstellung auf SVG), Vite-Watcher-Fix, Seeder-Ausbau
(Ratings, Mehrfachbilder).

**Teststatus bei Analyse:** 200 Tests / 826 Assertions grün · PHPStan grün
(Baseline: 180 Einträge, gewachsen — siehe Q2) · composer audit + npm audit
sauber · `npm run build` grün (nur mit Node ≥ 20) · **ESLint 2 Fehler,
svelte-check 219 Fehler** (siehe B2) · Routen: 82, Middleware-Zuordnung
stichprobenartig verifiziert.

## ✅ Seit V2 erledigt / verifiziert (Kurzprotokoll)

- **B2 umgesetzt — Frontend-Qualitäts-Gate grün (09.07.2026):** svelte-check
  **219 → 0 Fehler**, ESLint sauber, Build grün, Suite 200/200. Im Detail:
  (1) **lucide-Paket vereinheitlicht** — das deprecatete `lucide-svelte` komplett
  raus, alles auf `@lucide/svelte` (75 Dateien, Import-Pfade global umgestellt).
  (2) **UI-Kit zentral kompatibel gemacht statt gelöscht** (shadcn `add --all`
  bleibt): fehlende Helper-Typen (`WithElementRef`, `WithoutChild(ren)(OrChild)`)
  in `lib/utils.ts`, `buttonVariants`/`ButtonVariant`/`ButtonSize`/`ButtonProps`
  am lokalen Button ergänzt (neue `button/variants.ts`), vier fehlende Kit-
  Peer-Deps installiert (vaul-svelte, formsnap, sveltekit-superforms, paneforge).
  (3) **Alle App-Typfehler gefixt**: `AddressForm` von einem
  `document.getElementById`+CustomEvent-Hack auf eine typsichere `onUpdate`-
  Callback-Prop umgestellt (Confirm + UserSettingsDialog, 4 Call-Sites);
  `settings/Profile` user-null (auth-gated); PayPal-SDK-Global via
  `types/paypal.d.ts`; FAQ-Accordion `collapsible`-Prop raus (bits-ui v2
  Default); `CartSheet` onClick-Cast; `Admin/Customers/Show` `fullname`-Tippfehler.
  (4) **Ungenutzte, gegen neuere bits-ui/vaul-APIs generierte Kit-Komponenten**
  (drawer, field, input-group, command, item, button-group, calendar, form)
  aus svelte-check ausgeschlossen (`tsconfig.json` exclude) — Dateien bleiben
  auf Platte, werden typisiert sobald genutzt (Entscheidung Linus: nicht löschen).
  (5) **ESLint**: `svelte/no-navigation-without-resolve` abgeschaltet (SvelteKit-
  Regel, in diesem Inertia-/Wayfinder-Stack durchgängig Fehlmatch auf
  tel:/mailto:/externe Links), zwei echte Fehler gefixt (toter Button-Import in
  Checkout/Success, `$state`+`$effect` → writable `$derived` in Products/Edit).
  (6) **`engines: node >=20.19` + `.nvmrc`** eingecheckt (System-Node 18 crasht
  Vite 8). Verbleiben 4 vorbestehende svelte-check-**Warnings** (2× Dialog-
  `<slot>`-Deprecation, chart-tooltip, ShopHeader-Hover-Backup) — blockieren
  den Exit-Code nicht, separat aufräumbar.
- **Q1–Q3 umgesetzt (09.07.2026):** Q1 — tote Dateien raus (`AppHeader.svelte`
  + `AppHeaderLayout.svelte`, `data/cart.json`, `ShopHeader-left-align.svelte`,
  `old-hero.png`), AGENTS.md nachgezogen. **Ausnahme (Entscheidung Linus): die
  ungenutzten shadcn-Kit-Dateien unter `components/ui/` bleiben bewusst
  (shadcn `add --all`)** — der B2-Fix muss also über Kompatibilität statt
  Löschen laufen. Q3 — `completeCapture()` im PayPalController extrahiert
  (S3); byte-identische `UpdateProduct/ManufacturerRequest` gelöscht,
  `update()` nutzt die Store-Requests (S4; Category-Paar bleibt — legitimer
  unique-ignore-Unterschied); `defaultShipping/BillingAddress()`-Helper am
  Customer, drei Call-Sites umgestellt (S5); ungenutzte `total`-Prop aus
  PayPalButton entfernt + `Model::preventLazyLoading()` außerhalb Produktion
  aktiviert — Suite läuft komplett ohne Lazy-Load-Violation (S6;
  `sessions.user_id` bleibt als rein kosmetisch offen). Q2 — alle Modelle mit
  `@property`-Docblocks + Relation-Generics typisiert, Baseline regeneriert:
  **180 → 117 Einträge (−35 %)**; der Rest ist überwiegend das bekannte
  Cart-Array/DTO-Thema (CartService/PayPalService). Verifiziert: Suite
  200/200, PHPStan grün, Pint, ESLint, Build grün, Shop-Seiten per
  Smoke-Test 200.
- **SEO-Paket 1 umgesetzt (09.07.2026):** SEO-2 (favicon.ico + apple-touch-icon
  aus dem SVG neu erzeugt, Blade-Links vervollständigt), SEO-3 (AppHead um
  `canonical`-Prop erweitert — rendert `<link rel="canonical">` + `og:url`;
  gesetzt auf Welcome, Products/Index, ByCategory, Show), SEO-4 (robots.txt:
  Sitemap-Verweis + Disallow für /cart, /warenkorb, /checkout, /customer,
  /settings, /paypal), SEO-5 teilweise (Hero-`<img>` mit width/height,
  `fetchpriority="high"`, `decoding="async"` — WebP/AVIF-Konvertierung
  bewusst offen), SEO-6 teilweise (FAQ-JSON-LD escaped jetzt `<` wie
  Products/Show; Organization+WebSite-JSON-LD auf der Startseite —
  Zahlung/Versand-Seiten auf Wunsch Linus unberührt). Verifiziert per
  Headless-Chrome gegen den Dev-Server (Canonical inkl. Query-Stripping auf
  `/zubehoer?sort=…`, JSON-LD, Hero-Attribute, HTTP 200 für .ico/.png/robots),
  ESLint auf den geänderten Dateien sauber, svelte-check-Fehlerzahl
  unverändert (219, alle vorbestehend — B2), Build grün, Suite 200/200.

- **V2-Resthärtung „Settings-Werte unvalidiert" teilweise erledigt:**
  `SettingController::update` prüft jetzt Key-Whitelist + String-Typ +
  Maximallänge 1000. Der kritischste Teil (`payment.mode` akzeptierte jeden
  String) ist **gegenstandslos** — das Setting wurde komplett entfernt,
  Sandbox/Live kommt fix aus `APP_ENV` (`App\Support\PaymentMode`). Offen
  bleibt nur Format-Validierung einzelner Felder (S-5).
- **Mail-Architektur sauber verifiziert:** `OrderManager` ist der einzige
  Order-/Mail-Pfad; `markPaid()`/`confirmInvoicePayment()` teilen sich den
  atomaren `transitionToPaid()` (keine Doppel-Mails bei Webhook+Return-Race);
  zwei bewusst getrennte Mail-Templates (Bestellbestätigung mit Bankdaten vs.
  Zahlungsbestätigung ohne). Mails synchron — bei ~5–10 Mails/Tag korrekt,
  Queue-Umstieg bleibt ein kleiner, dokumentierter Schritt.
- **PayPal-Flow erneut geprüft, keine neuen Lücken:** Capture mit
  Ownership-Scope + Betragsabgleich, Webhook signaturverifiziert +
  `throttle:60,1`, Refund-Handler extrahiert Capture-ID korrekt aus dem
  `up`-Link, `cancelStalePendingPayPalOrders` ist kunden- und paypal-scoped.
- **Admin-Bereich geprüft:** `EnsureAdmin` prüft Login **und** `is_admin`;
  Login throttled (6/min); Bild-Upload validiert MIME+Größe+Max-5;
  Varianten-/Bild-Endpoints prüfen Produkt-Zugehörigkeit; Settings-Secrets
  maskiert und verschlüsselt; Dashboard-`is_test`-Entscheidung (41dc28a) ist
  schlüssig begründet (in Produktion kann strukturell keine Sandbox-Order
  mehr entstehen).
- **Frontend-Props/XSS:** `{@html}` nur an 2 Stellen (JSON-LD auf
  Products/Show mit `<`-Escaping, FAQ mit hartkodiertem Inhalt); Customer-
  Model versteckt Secrets via `#[Hidden]`; Kunden-Endpoints prüfen Ownership
  (`customer.orders.show`, `checkout.success` beide Zweige).
- **SEO-Grundgerüst steht:** Alle 46 Seiten nutzen `AppHead` (Title +
  Description + OG/Twitter), Product-JSON-LD vollständig (AggregateOffer bei
  Varianten, AggregateRating, dynamische Availability), FAQPage-JSON-LD,
  Sitemap mit `lastmod` + 1h-Cache, robots.txt sperrt `/admin`,
  `lang="de"` korrekt. Lücken: siehe SEO-Abschnitt.

---

## 🔴 Release-Blocker

### B1. `php artisan db:seed` in Produktion vernichtet den kompletten Katalog

`DatabaseSeeder` ruft `ProductSeeder` **unconditionally** auf (nur User/
Customer/Rating-Seeder sind production-gegated) — und `ProductSeeder`
beginnt mit:

```php
Storage::disk('public')->deleteDirectory('products');  // alle Produktbilder weg
ProductVariant::query()->delete();
Product::query()->delete();
Manufacturer::query()->delete();
Category::query()->delete();
```

Ein einziges unbedachtes `php artisan db:seed` oder `migrate:fresh --seed`
auf dem Produktivsystem löscht alle im Admin gepflegten Produkte, Varianten,
Kategorien, Hersteller **und alle hochgeladenen Bilder** und ersetzt sie
durch den Shopware-CSV-Stand (der die CSV-Datei
`_SHOPWARE-EXPORTS/aktuellen produkte.csv` voraussetzt — fehlt sie, bricht
der Seed mit RuntimeException mitten im Wipe ab). Bestellhistorie überlebt
(Snapshots), aber `order_items.product_id`-Verknüpfungen zeigen ins Leere
bzw. auf neue IDs.
**Vorgehen:** `ProductSeeder` aus `DatabaseSeeder` nehmen und die
Erstbefüllung als expliziten Aufruf dokumentieren (`db:seed
--class=ProductSeeder`, analog PaymentSeeder) — oder mindestens einen
`app()->isProduction() && ! $this->command->confirm(...)`-Guard einbauen.
`DB::prohibitDestructiveCommands()` schützt hier **nicht** (das greift nur
bei `migrate:fresh` & Co., nicht bei Seedern). Launch-Checkliste ergänzen.

### B2. Frontend-Qualitäts-Gate ist rot (svelte-check + ESLint), README verspricht grün ✅ (09.07.2026 — Kurzprotokoll oben)

`npm run lint && npm run types:check` ist laut README Pflicht-Workflow —
aktuell: **ESLint 2 Fehler** (`Checkout/Success.svelte` unbenutzter
`Button`-Import; `Admin/Products/Edit.svelte` `svelte/prefer-writable-derived`)
und **svelte-check 219 Fehler in 149 Dateien**. Davon:

- **13 echte App-Fehler**, u. a. `settings/Profile.svelte` (3× `'user' is
  possibly 'null'`), `Checkout/Confirm.svelte` + `UserSettingsDialog.svelte`
  (`onaddressupdate` existiert nicht im Prop-Typ — Custom-Event-Typisierung),
  `CartSheet.svelte` (unknown → MouseEventHandler), `FAQ.svelte`
  (`collapsible`-Prop existiert an Accordion nicht), `Admin/Customers/Show.svelte`
  (`Address.fullname` fehlt im Typ), `PayPalButton.svelte` (`paypal`-Global
  nicht deklariert).
- **206 Fehler in `components/ui/`**: offenbar frisch hinzukopierte
  shadcn-Komponenten (calendar, alert-dialog, button-group, …), die gegen
  eine **neuere Kit-Version** geschrieben sind — sie importieren
  `@lucide/svelte` (nicht installiert; Projekt nutzt `lucide-svelte`),
  `buttonVariants`/`ButtonVariant` und `WithoutChild`/`WithElementRef`,
  die es in den lokalen `button`/`utils`-Modulen nicht gibt. Der Build läuft
  nur, weil diese Dateien nirgends importiert werden (dead code).

Dazu ein Umgebungsproblem: mit dem System-Node **v18** crasht Vite 8
(`CustomEvent is not defined`) und svelte-check meldet 404× „No Svelte
configuration found" — erst mit Node ≥ 20.19 (nvm v20.20.1 ist vorhanden)
laufen Build und Checks überhaupt.
**Vorgehen:** (1) UI-Kit-Dateien kompatibel machen — **nicht löschen**
(Entscheidung Linus 09.07.2026: shadcn `add --all`, Kit bleibt vollständig;
d. h. `@lucide/svelte`-Imports/fehlende Exports auf das lokale Kit
umschreiben oder das Kit aktualisieren), (2) die 13 App-Typfehler fixen,
(3) beide ESLint-Fehler fixen, (4) `"engines": { "node": ">=20.19" }` +
`.nvmrc` ins Repo, damit der Node-18-Crash nicht erst auf dem Server
auffällt.

---

## 🟠 Sicherheitslücken

### S-1. Rating-Endpoint: UI deaktiviert, Route weiter offen — jetzt reine Spam-Fläche

Das Bewertungsformular auf der Produktseite ist auskommentiert (Entscheidung:
künftig Order-basierte Bewertungen), aber `POST /products/{product}/ratings`
ist **weiterhin aktiv**: anonym, ungedrosselt, ohne Moderation — und die
Bewertungen fließen ungefiltert in die öffentliche Produktseite **und ins
JSON-LD/`aggregateRating`** (Sterne-Snippets in Google). Da kein legitimer
Client den Endpoint mehr aufruft, ist jeder Treffer Missbrauch.
**Vorgehen (5 Minuten):** Route auskommentieren/entfernen, bis die
Order-basierte Lösung kommt — Controller + Tests können bleiben. Mindestens:
`throttle:3,1` + `auth`. Damit wäre der älteste offene Punkt der Serie
(V1 Punkt 9 → V2 S-C → V3) endlich vom Tisch.

### S-2. Kein Throttle auf `paypal/order/create` (Carry-over V2 S-C)

Jeder Klick = PayPal-API-Call + DB-Order + (durch `cancelStalePending…`)
UPDATE-Query. Auth-pflichtig, aber ein eingeloggter Kunde/Bot kann beliebig
Orders erzeugen. `->middleware('throttle:10,1')` kostet eine Zeile.

### S-3. `paypal/after-payment` ohne Auth/Ownership (Carry-over V2 S-C)

Capture durch Dritte mit bekanntem Token weiterhin triggerbar (Geld fließt
an den Shop, Success-Seite prüft Ownership — Risiko gering). `auth` wäre
unschädlich: PayPal leitet den eingeloggten Käufer zurück.

### S-4. `checkout.payment.update` als einzige Checkout-Route ohne `auth` (Carry-over V2 S6)

Harmlos (session-scoped), aber inkonsistent — eine Zeile.

### S-5. Kleinere Härtungen (neu + Rest aus V2) ✅ (bis auf Cookie-Banner, 10.07.2026)

- **Admin-Refund leakt Exception-Text an den Client:** ✅ `OrderController::refund`
  gibt jetzt eine generische Meldung zurück (der Fehler wird weiter geloggt) —
  wie kundenseitig in V1 Punkt 7.
- **`Admin/Orders/Show` reicht `payments` als volle Models durch:** ✅ jetzt
  explizites Feld-Mapping (id, status, order/capture-id, amount, currency,
  payer_email/name, created_at) — der rohe `response_data`-Block (kompletter
  PayPal-API-Response mit Payer-Rohdaten) geht nicht mehr nach vorne. Test:
  `test_show_does_not_expose_raw_paypal_response_data`.
- **Settings-Format-Validierung:** ✅ `SettingController::update` prüft jetzt
  `shop.email`/`mail.smtp_user` als E-Mail und `mail.smtp_port` als Port
  (1–65535); leere Werte bleiben zulässig (= löschen). 3 neue Tests.
- **LIKE-Wildcards ungefiltert:** ✅ Produktsuche (`index`/`search`) escaped
  `%`/`_`/`\` (gemeinsamer `whereNameLike()`-Helper mit `ESCAPE`-Klausel für
  MySQL/SQLite). 2 neue Tests (Wildcard literal, Treffer mit literalem `%`).
- **`is_admin` fillable:** ✅ aus dem `User`-`#[Fillable]` entfernt; der
  `add:admin`-Command setzt das Flag jetzt explizit (nicht mehr per
  Mass-Assignment). Tests: Mass-Assignment-Guard + Command flaggt Admin.
- **Cookie-Banner ist Attrappe:** „Nur notwendige" und „Alle Cookies
  erlauben" tun exakt dasselbe (Banner ausblenden, sessionStorage). Solange
  ausschließlich technisch notwendige Cookies gesetzt werden, ist das
  DSGVO-seitig okay — aber der „Alle erlauben"-Button suggeriert eine Wahl,
  die es nicht gibt. Vor Einbindung von Analytics/Drittdiensten braucht es
  echtes Consent-Management; bis dahin ehrlicherweise nur „OK"-Button.
  **Bewusst offen gelassen (Entscheidung Linus 10.07.2026).**

---

## 🟡 Businesslogik

### B-1. Versandart „Preis auf Anfrage" wird als 0,00 € verkauft

`shipping_methods.price` ist nullable („null = auf Anfrage", so dokumentiert
in SCHEMA.md und im Admin anlegbar). `CartService::amountToCents(null)`
macht daraus aber **0 Cent** — eine „auf Anfrage"-Versandart erscheint im
Checkout als kostenlos und ist bestellbar. Entweder (a) null-Preis-Methoden
aus der Checkout-Auswahl ausblenden, (b) UI-Label „auf Anfrage" + nicht
wählbar, oder (c) das nullable-Konzept abschaffen und Preis verpflichtend
machen. Aktuell ist es eine stille Umsatz-Falle.

### B-2. Alt-Cart-Zeilen umgehen den Variantenzwang

Der Variantenzwang greift nur beim **Hinzufügen** (`AddCartItemRequest`).
Liegt ein Produkt bereits ohne Variante im Warenkorb und der Admin legt
danach Varianten an, bleibt die Zeile gültig (`is_available` prüft nur
Produkt + gewählte Variante) und wird zum alten Basispreis bestellt —
genau das Szenario, das der after-Hook verhindern soll. Fix: in
`CartService::items()` eine Zeile ohne `variant_id` als unbuyable markieren,
wenn `$product->variants` nicht leer ist (eine Bedingung, Daten sind schon
geladen).

### B-3. Capture-Betragsabweichung: Geld ist bei PayPal eingezogen, Order steht auf `failed`

Der Betragsabgleich (Defense-in-Depth aus V1) markiert bei Abweichung
Payment+Order als FAILED — der Capture bei PayPal ist dann aber bereits
erfolgt, das Geld eingezogen, und niemand wird aktiv benachrichtigt (nur
`Log::error`). Praktisch fast unerreichbar (der Betrag stammt aus der
eigenen Order), aber wenn der Fall eintritt, braucht es einen manuellen
Prozess. Minimal: diesen Fall zusätzlich als Admin-Mail melden oder im
Log-Kanal `mail` führen. Kein 1.0-Blocker, nur festgehalten.

### B-4. Kein Bestandskonzept (Carry-over V2 B4, bewusst)

Nur `is_available`, Menge frei bis 99. Bleibt die bewusste Entscheidung
für 1.0 — unverändert festgehalten.

### B-5. Kleinkram

- `OrderManager::summaryFromOrder`: `product_number` fällt bei gelöschtem
  Produkt auf die **OrderItem-ID** zurück — auf Success-Seite/Mails steht
  dann eine Nummer, die nie eine Produktnummer war. `?? '—'` wäre ehrlicher.
- `Admin/OrderController::confirmPayment` ohne `notify` nutzt ein direktes
  `update(['status' => 'paid'])` statt des atomaren Übergangs — bei
  Doppelklick harmlos (Precondition-Check), aber `transitionToPaid()`
  existiert genau dafür; einheitlich machen.

---

## 🔵 Simplifizierung & Code-Refinement

### Q1. Toter Starter-Kit-/Altlast-Code raus ✅ (UI-Kit-Dateien bleiben bewusst)

- `AppHeader.svelte` + `layouts/app/AppHeaderLayout.svelte`: nirgends
  verwendet, `AppHeader` importiert sogar ein nicht existentes `dashboard`
  aus `@/routes` (kompiliert nur, weil nie importiert).
- Die ~206-Fehler-UI-Kit-Dateien aus B2, soweit ungenutzt (calendar,
  button-group, alert-dialog-Teile, …).
- `resources/js/data/cart.json` (in AGENTS.md selbst als Altlast markiert).
- `ShopHeader-left-align.svelte` — laut TODO.md-Notiz von Linus „kann
  komplett weg" (die Hover-Variante bleibt, Entscheidung dokumentiert).
- `public/assets/old-hero.png` (179 KB, ungenutzt, per „CLEANUP"-Commit
  versehentlich eingecheckt).

### Q2. PHPStan-Baseline wächst statt zu schrumpfen ✅ (180 → 117; Rest = Cart-DTO)

Seit V2 sind ~90 neue Baseline-Zeilen dazugekommen (u. a. mit den
Admin-Orders- und Mail-Commits); Stand jetzt 180 ignorierte Fehler. Die
Baseline war als Einfrieren des Altbestands gedacht — **neuer** Code sollte
clean landen. Empfehlung: Baseline-Diff im Review beachten; mittelfristig
die Order/Payment-Property-Typen (der Großteil der Einträge) über
`@property`-PHPDoc am Model lösen statt per Ignore.

### Q3. Carry-over aus V2 ✅ (bis auf `sessions.user_id`, kosmetisch)

- **S3:** Capture-Logik doppelt in `PayPalController::captureOrder()` und
  `afterPayment()` (Betragsabgleich + COMPLETED-Handling zeilengleich) →
  private `completeCapture()`-Methode.
- **S4:** `StoreProductRequest` == `UpdateProductRequest` (byte-identisch
  bis auf Klassennamen) — eine Klasse reicht; gleiches gilt für
  Category/Manufacturer-Paare.
- **S5:** Default-Adress-Queries dupliziert
  (`CheckoutController::prefillAddressFromProfile` ↔ `AddressController`)
  → `defaultShippingAddress()`-Helper am Customer.
- **S6-Rest:** ungenutzte `total`-Prop in PayPalButton (`_total`);
  `sessions.user_id` kosmetisch.
- `Model::preventLazyLoading(! app()->isProduction())` fehlt — hätte
  N+1-Kandidaten in Dev sofort sichtbar gemacht (Boost/Best-Practice-Regel).

---

## 🔍 SEO

Grundgerüst gut (Titles/Descriptions überall, Product- + FAQ-JSON-LD,
Sitemap, robots, `lang="de"`). Konkrete Lücken, nach Impact sortiert:

### SEO-1. Kein SSR → Link-Previews und Nicht-Google-Crawler sehen nichts

Inertia rendert rein clientseitig; `config/inertia.php` hat den
SSR-Bundle-Eintrag auskommentiert, `npm run build:ssr` existiert schon.
Google führt JS aus — aber **WhatsApp/Slack/Teams/LinkedIn-Preview-Bots
nicht**: Wer eine Produktseite teilt, bekommt keinen Titel, keine
Beschreibung, kein OG-Bild (alle Meta-Tags entstehen erst per JS). Für
einen Shop, dessen Produkte per Mail/Messenger geteilt werden, ist das der
größte SEO/Sharing-Hebel. Inertia v3 macht SSR im Dev-Mode bereits
automatisch (via `@inertiajs/vite`) — Produktionsseitig fehlen nur
Bundle-Build + Config-Zeile + Prozess (oder `inertia:start-ssr`).

### SEO-2. Favicon-Links zeigen auf gelöschte Dateien ✅

`resources/app.blade.php` referenziert `/favicon.ico` — die Datei wurde mit
e78f695 gelöscht (nur noch `favicon.svg` existiert). Browser ohne
SVG-Favicon-Support und alle „hole /favicon.ico"-Konsumenten (ältere
Crawler, RSS-Reader, Google-Suchergebnis-Favicons nutzen teils .ico) laufen
auf 404; `apple-touch-icon.png` wurde ebenfalls gelöscht und nicht ersetzt
(iOS-Homescreen-Bookmark zeigt Screenshot statt Logo). Fix: .ico + 180×180-
PNG wieder erzeugen (aus dem SVG) oder die Links entfernen.

### SEO-3. Kein Canonical-Tag ✅

`/products?sort=price_asc`, `?q=…`, Kategorie- und Paginierungs-URLs sind
für Crawler eigenständige Seiten → Duplicate-Content-Verwässerung. `AppHead`
um optionales `canonical`-Prop erweitern (Products/Index, ByCategory, Show,
Welcome), analog zur bestehenden JSON-LD-`url`-Logik.

### SEO-4. robots.txt ohne Sitemap-Verweis ✅

`Sitemap: https://…/sitemap.xml` fehlt — eine Zeile, dann finden Bing & Co.
die Sitemap ohne Search-Console-Anmeldung. (Außerdem `Disallow: /checkout`,
`/warenkorb`, `/settings` erwägen — private/wertlose Crawl-Pfade.)

### SEO-5. Hero-Bild 748 KB PNG = LCP-Bremse auf der Startseite ✅ (Attribute; Konvertierung offen)

`public/assets/hero.png` ist mit 748 KB das größte Asset der wichtigsten
Seite (und die Frontpage stört dich ohnehin noch). WebP/AVIF (~80–150 KB),
`<link rel="preload">` bzw. `fetchpriority="high"`, explizite width/height
gegen CLS. Der Parallax-Transform selbst ist unkritisch (nur `transform`).

### SEO-6. Kleinigkeiten ✅ (teilweise — Zahlung/Versand bewusst offen)

- ~~FAQ-JSON-LD stringifiziert ohne `<`-Escaping~~ ✅ (09.07.2026: gleiche
  Escape-Zeile wie Products/Show).
- `/informationen/zahlung` (`Zahlung.svelte`) vs. `/informationen/versand`
  (`VersandUndZahlung.svelte`): thematische Überschneidung zweier indexierter
  Seiten — Inhalte schärfen oder zusammenlegen. **Bleibt auf Wunsch Linus
  vorerst unberührt (09.07.2026).**
- ~~Startseite hat kein `Organization`/`WebSite`-JSON-LD~~ ✅ (09.07.2026:
  `@graph` mit Organization + WebSite in `Welcome.svelte`, Kontaktdaten aus
  den Shared Props, `<`-escaped).

---

## 🚀 Launch-Checkliste (Ergänzungen zu README)

1. **Node ≥ 20.19 auf dem Build-System** (System-Node 18 crasht Vite 8) —
   `engines`-Feld + `.nvmrc` einchecken (B2).
2. **Niemals blankes `db:seed` in Produktion**, solange B1 nicht gefixt ist;
   Erstbefüllung dokumentiert über explizite Seeder-Klassen.
3. `php artisan storage:link`, `APP_ENV=production`, `APP_DEBUG=false`,
   `SESSION_SECURE_COOKIE=true`, `config:cache`/`route:cache` — unverändert.
4. Admin-Settings: SMTP (+ Testmail), PayPal-Credentials (+ Check),
   **Webhook-ID** (sonst werden alle Webhooks abgelehnt), `shop.email`.
5. Favicon-Set neu erzeugen (SEO-2) und Hero-Bild komprimieren (SEO-5) —
   beides vor dem ersten Indexieren billiger als danach.
6. Kein Queue-Worker, kein Scheduler-Cron nötig (verifiziert:
   `schedule:list` leer, Mails synchron).
7. DB-Backup-Strategie (SQLite-Datei) — weiterhin offen aus V2.

## 🧪 Testing — fehlende Abdeckung

- **Manueller Browser-Test PayPal-Sandbox** (Carry-over aus V2 — CSRF ist in
  PHPUnit deaktiviert, der Button-Flow ist serverseitig nicht testbar).
- Nach S-1: Test, dass `ratings.store` gesperrt/gedrosselt ist.
- Nach B-1: Test „Versandart ohne Preis ist nicht bestellbar".
- Nach B-2: Test „Cart-Zeile ohne Variante wird unbuyable, sobald das
  Produkt Varianten hat".
- Nach B1: Test/Guard „ProductSeeder läuft nicht in Produktion".
- ~~`svelte-check`/`lint` als CI-Gate, sobald B2 grün ist~~ — B2 ist grün
  (09.07.2026); CI-Gate jetzt einrichtbar, sonst driftet es wieder.

## Empfohlene Reihenfolge

1. **B1** (Seeder-Guard — 15 Minuten, verhindert den teuersten Unfall)
   → **S-1** (Rating-Route zu — 5 Minuten).
2. ~~**B2** (Qualitäts-Gate grün: lucide-Paket vereinheitlicht, UI-Kit
   kompatibel, App-Typfehler, Lint, engines/.nvmrc)~~ ✅ (09.07.2026) —
   jetzt CI-fähig.
3. **B-1 + B-2** (Versandpreis-null, Alt-Cart-Variantenzwang) — je mit Test.
4. **SEO-Paket:** ~~Favicons (2) → robots-Sitemap-Zeile (4) → Canonical (3)
   → Hero-Attribute (5)~~ ✅ → Hero-Konvertierung (5) + SSR (1, größter
   Brocken, lohnt vor Launch — Entscheidung Linus: macht er später selbst).
5. S-2/S-3/S-4 (drei Middleware-Zeilen) + ~~S-5~~ ✅ (10.07.2026; Cookie-Banner
   bewusst offen).
6. ~~Q1–Q3 (tote Dateien, Baseline-Disziplin, V2-Simplifizierungen)~~ ✅
   (09.07.2026; UI-Kit bleibt, `sessions.user_id` offen).
