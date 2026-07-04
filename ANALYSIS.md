Komplettanalyse dormed-shop — Stand vor Release 1.0
TL;DR: Die Architektur ist solide und aufgeräumt, aber das Projekt ist in diesem Zustand nicht releasefähig: Die Zahlartauswahl ist funktional tot (Kunden können nur auf Rechnung kaufen), Produktvarianten landen falsch im Warenkorb, die komplette Testsuite ist rot (111 von 135 Tests schlagen fehl), und es gibt einen geseedeten Admin-Account mit Passwort password.

Was gut ist (je ein Satz)
Saubere Schichtung: Controller → FormRequests → CartService/OrderManager/PayPalService, Geld-Rechnung intern in Cents.
Beide Webhooks (Stripe & PayPal) verifizieren Signaturen und sind gedrosselt, CSRF-Ausnahmen sind korrekt minimal.
Secrets in der settings-Tabelle werden verschlüsselt gespeichert und im Admin-UI nur maskiert ausgegeben.
Getrennte Auth-Guards für Kunden (web/Fortify mit 2FA & Passkeys) und Admins, Admin-Login gedrosselt.
CustomerOrderController::show prüft Ownership korrekt, Admin-Bildupload validiert Mime-Typen sauber.
markPaid() ist als idempotenter zentraler Punkt für „bezahlt + Mails" konzipiert — richtige Idee.
Svelte-Frontend nutzt kein gefährliches {@html} außer für selbst erzeugtes JSON-LD; Wayfinder-Routen statt hartkodierter URLs.
🔴 Release-Blocker
1. Zahlartauswahl existiert nicht — Stripe & PayPal sind für Kunden unerreichbar
Der Endpoint checkout.payment.update validiert gegen config('shop.cart.payment_methods') — diesen Key gibt es nicht (config/shop.php hat providers). Rule::in([]) lehnt damit jede Eingabe ab: UpdateCartPaymentMethodRequest.php:21. Zusätzlich gibt es im gesamten Frontend kein UI, das diese Route aufruft — weder im Warenkorb noch auf der Confirm-Seite gibt es eine Zahlart-Auswahl. Da CartService::paymentMethods() immer „Rechnung" als erste Methode voreinstellt, ist die Default-Zahlart immer Rechnung; die PayPal-/Stripe-Zweige in Confirm.svelte:393-419 sind toter Code. Bezeichnend: Kein einziger Test deckt einen anderen Payment-Flow als invoice ab, sonst wäre es aufgefallen.

Vorgehen: Config-Key in der Request-Validierung auf die tatsächliche providers-Struktur umstellen, im Checkout (Warenkorb- oder Confirm-Seite) eine Radio-Auswahl analog zur Versandart bauen, und je einen Feature-Test pro Zahlart (Auswahl + Submit-Routing) schreiben. Alternativ, falls 1.0 bewusst nur Rechnung können soll: Stripe/PayPal-Code hinter ein Feature-Flag ziehen und die tote Route entfernen — das wäre die ehrlichere Simplifizierung.

2. Produktvarianten werden beim Kauf ignoriert
Die Produktseite zeigt Varianten mit eigenem Preis an, aber addToCart() sendet nur product_id + quantity (Show.svelte:155-167). Der Warenkorb kennt keine Varianten (CartService.php:72-86) — der Kunde wählt „Variante XY für 99 €", bestellt aber das Basisprodukt zum Basispreis. Falscher Preis, falsche Bestellposition, im Zweifel rechtlich problematisch.

Vorgehen: Entweder Varianten vollständig durchziehen (Cart-Items um variant_id + Varianten-Preis erweitern, OrderItem ebenfalls) — oder für 1.0 das Varianten-Feature komplett entfernen (Model, Migrationen, Admin-UI, Produktseiten-Select). Angesichts deines Simplifizierungsziels: entfernen, wenn der Shop es nicht zwingend braucht, und in 1.1 sauber bauen.

3. Testsuite komplett rot + vendor/lock out of sync
php artisan test → 111 von 135 Tests error mit DevCommands should be registered in application code…. Ursache: Installiert ist laravel/framework v13.16.0, die composer.lock verlangt v13.17.0 (d.h. composer install wurde nach dem Lock-Update nie ausgeführt), aktuell ist v13.18.1 — der fragile Backtrace-Guard in DevCommands::preventVendorRegistration() feuert beim Test-Bootstrap. Solange die Suite rot ist, ist jede Aussage „Tests sind grün" wertlos und CI unmöglich.

Vorgehen: composer update laravel/framework (Patch-Update, kein Breaking Change — braucht aber laut euren Projektregeln dein OK), danach die Suite erneut laufen lassen und verbleibende echte Failures fixen. Das ist Schritt 1 vor allem anderen, weil alle weiteren Fixes Tests brauchen.

4. PayPal-Rückkehrer sehen nie die Erfolgsseite
PayPalController.php:224,244 leitet nach erfolgreichem Capture zu checkout.success ohne Parameter — success() findet ohne session_id/paypal_order_id/order_id keine Order und leitet zur Startseite (CheckoutController.php:236-263). Der Kunde hat bezahlt und landet kommentarlos auf der Homepage.

Vorgehen: to_route('checkout.success', ['paypal_order_id' => $token]) — plus Feature-Test für den Return-Flow.

🟠 Sicherheitslücken
5. Geseedeter Admin mit Passwort „password"
UserSeeder.php legt mail@dormed.de mit dem Factory-Default-Passwort password an (UserFactory.php:22), CustomerSeeder.php:19 ebenso. Wird der DatabaseSeeder in Produktion ausgeführt (er hängt mit migrate --force im composer setup-Flow nahe), ist der Admin-Bereich mit einem Wörterbuch-Passwort übernehmbar. Vorgehen: Seeder in Produktions- und Dev-Seeder trennen; Admin-Anlage über einen Artisan-Command mit Laravel\Prompts-Passwortabfrage statt Seeder.

6. env() zur Laufzeit bricht Webhooks bei config:cache
StripeWebhookController.php:26 und PayPalService.php:37-46,184 rufen env() außerhalb von Config-Dateien auf. Mit php artisan config:cache (Standard im Deployment) liefert env() null — falls das jeweilige Setting nicht in der DB steht, schlägt die Stripe-Signaturprüfung dauerhaft fehl und alle Zahlungsbestätigungen bleiben aus. Zusätzlich verifiziert PayPalService::verifyWebhook mit config('paypal')-Credentials statt der Settings-basierten — sind die Keys nur im Admin gepflegt, ist die Webhook-Verifikation tot. Vorgehen: alle env()-Fallbacks durch config()-Werte ersetzen (Keys in config/services.php/config/paypal.php deklarieren) und verifyWebhook denselben Client wie der Rest der Klasse nutzen lassen.

7. Erfolgsseite leakt Bestelldaten ohne Login
checkout.success?session_id=… bzw. ?paypal_order_id=… zeigt Adresse, E-Mail und Bestellinhalt ohne Auth und ohne Ownership-Prüfung — nur der order_id-Zweig prüft den Kunden (CheckoutController.php:244-253). Die IDs sind zwar schwer ratbar, landen aber in Browser-History, Server-Logs und ggf. Referrern. Vorgehen: Route hinter auth legen und zusätzlich customer_id in allen drei Zweigen mitprüfen — der Käufer ist ohnehin immer eingeloggt (Checkout erfordert Login).

8. PayPal-Capture ohne Ownership + Fehler-Interna an den Client
captureOrder sucht das Payment nur per paypal_order_id — jeder eingeloggte Nutzer kann fremde Captures anstoßen und bekommt Status fremder Zahlungen zurück (PayPalController.php:90-110). Außerdem geben debug => $e->getMessage() (Zeile 56, 79) interne Exception-Texte an den Browser. Vorgehen: whereHas('order', fn($q) => $q->where('customer_id', $request->user()->id)) ergänzen; debug-Felder entfernen (Details stehen ohnehin im Log).

9. Admin-Check-Endpoints mit Seiteneffekten per GET
/admin/settings/mail/check versendet eine Mail per GET (SettingController.php:121-144), Stripe/PayPal-Checks machen externe API-Calls per GET. GET braucht kein CSRF-Token — eine fremde Seite kann den eingeloggten Admin diese Endpoints feuern lassen. Vorgehen: auf POST umstellen (drei Zeilen in routes/admin.php:66-68 + Frontend-Aufrufe).

10. Settings-Update ohne Key-Whitelist
SettingController::update schreibt jeden übermittelten Key in die DB (SettingController.php:103-119) — inklusive payment.mode, das versehentlich per manipuliertem Request auf live kippen kann, und beliebiger Müll-Keys. Vorgehen: erlaubte Keys als Konstante whitelisten und Rule::in/Array-Key-Filter anwenden.

11. Anonyme Bewertungen ohne Bremse
POST /products/{product}/ratings ist unauthentifiziert, ungedrosselt und ohne Moderation (routes/public/rating.php, RatingController.php) — ein Skript kann jedes Produkt mit 1-Stern-Spam fluten, der sofort öffentlich rendert. Vorgehen: mindestens throttle:3,1 + Honeypot; besser: nur verifizierte Käufer bewerten lassen (Check auf bezahlte Order mit dem Produkt) und ein is_approved-Flag für Admin-Freigabe.

12. is_admin-Flag wird nie geprüft
EnsureAdmin prüft nur, ob irgendein users-Datensatz im admin-Guard eingeloggt ist (EnsureAdmin.php:17); das Feld is_admin auf dem User-Model ist toter Schalter. Aktuell harmlos (nur Admins in users), aber eine tickende Falle, sobald jemand dort einen Nicht-Admin anlegt. Vorgehen: entweder Flag in Middleware/Login prüfen — oder das Feld und die ungenutzten 2FA-Spalten der users-Tabelle entfernen (Simplifizierung). Die Migration add_two_factor_columns_to_users_table ist Fortify-Restmüll, Admin-2FA existiert nicht.

🟡 Businesslogik
13. is_available wird nirgends durchgesetzt
Nicht verfügbare Produkte erscheinen im Listing, in der Suche, lassen sich in den Warenkorb legen und bestellen — das Flag wird nur gesetzt, nie geprüft (ProductController.php, CartController.php:29, CheckoutController::submit). Schlimmer: gelöschte Produkte bleiben als Session-Snapshot mit altem Preis/Namen im Warenkorb bestellbar (is_available-Feld im Cart-Item markiert sie nur). Vorgehen: where('is_available', true) als Scope in Listing/Suche/Add-to-Cart, und in submit()/createOrder() einen finalen Verfügbarkeits- und Preis-Recheck gegen die DB mit Fehlermeldung statt stiller Bestellung.

14. Preis-Snapshot im Warenkorb ist sessionlang eingefroren
add() friert unit_price/name beim ersten Hinzufügen ein (CartService.php:78-83) — ändert der Admin den Preis, kauft der Kunde tage später zum alten. Für einen Shop ohne Reservierungslogik ist das die falsche Stelle zum Einfrieren: Der Snapshot gehört erst in die Order (macht OrderManager::createFromCart ja bereits). Vorgehen: Preis im Cart immer live vom Produkt lesen; das vereinfacht nebenbei den Cart-State deutlich (nur noch product_id => quantity).

15. Jeder PayPal-Button-Klick erzeugt eine verwaiste Order
createOrder legt bei jedem Klick eine neue lokale pending-Order an (PayPalController.php:43); bricht der Kunde ab und klickt neu, sammeln sich Karteileichen, die Admin-Liste und Dashboard verfälschen. Vorgehen: vorhandene pending-PayPal-Order des Warenkorbs wiederverwenden oder abgebrochene beim Neuanlegen auf cancelled setzen; zusätzlich einen Scheduled Command, der alte pending-Orders ohne Payment aufräumt.

16. Dashboard-Umsatz zählt Test-, Fehlgeschlagen- und Unbezahlt-Orders
DashboardController.php:19-29 summiert total_amount über alle Orders — inklusive is_test, failed, cancelled, pending. Der angezeigte „Umsatz" ist damit Fantasie. Vorgehen: ->where('is_test', false)->whereIn('status', ['paid', 'processing', 'completed']).

17. Kleinere Flow-Punkte
Race-Conditions: markPaid() (check-then-update, OrderManager.php:59-70) und captureOrder können bei gleichzeitigem Webhook + Return-URL doppelte Mails erzeugen → Status-Übergang atomar machen (Order::whereKeyNot status paid ->update() und nur bei affected > 0 mailen).
Mails synchron im Request: Bestellbestätigungen blockieren den Checkout-Response und ein SMTP-Timeout kostet den Kunden Sekunden — ShouldQueue auf die Mailables (Queue-Infrastruktur existiert bereits).
Stripe-Refund fehlt: Admin kann nur PayPal erstatten (OrderController::refund); Stripe-Bestellungen müssen im Stripe-Dashboard erstattet werden, ohne dass der Shop-Status nachzieht (kein charge.refunded-Handling im Webhook).
Webhook ohne Betragsabgleich: checkout.session.completed markiert bezahlt, ohne amount_total gegen die Order zu prüfen — als Defense-in-Depth eine Zeile wert.
🔵 Simplifizierung & Code-Refinement
18. Toter Code entfernen (risikofrei, sofort)
Leere Routendateien: routes/manufacturer.php, routes/admin/company.php, routes/admin/settings.php, leerer Ordner routes/customer/checkout/
routes/public/products.php ist ein nicht eingebundenes Duplikat von routes/products.php
CheckoutController::ADDRESS_FIELDS ist ungenutzt (PHPStan bestätigt), config('shop.cart.shipping_methods') ist seit dem ShippingMethod-Model tot
tests/Feature/ExampleTest.php + tests/Unit/ExampleTest.php
Falls Entscheidung aus Punkt 2/12: Varianten-Feature bzw. is_admin + users-2FA-Spalten
19. Adressvalidierung dreifach dupliziert
Identische Regeln in CheckoutController::updateAddress, AddressController::ADDRESS_RULES und implizit im AddressForm. Vorgehen: ein AddressRules-Support-Objekt (oder FormRequest mit shipping/billing-Präfix-Helper), von beiden Controllern genutzt.

20. SettingController konsolidieren
index() und loadSettings() sind fast identische 40-Zeilen-Blöcke (SettingController.php:67-101 vs. 202-244); index() referenziert zudem veraltete Keys (stripe.publishable_key ohne Mode). Eine Key-Whitelist-Konstante (löst gleichzeitig Punkt 10) + eine Load-Methode reichen.

21. Settings- und Cart-Zugriffe cachen — jede Seite macht ~15 unnötige Queries
HandleInertiaRequests::share() läuft bei jedem Request und ruft CartService::cart() (Produkte-, ShippingMethod- ×2, Settings-Queries) plus ~6 einzelne Setting::get()-Queries plus Kategorien auf (HandleInertiaRequests.php:52-84). Vorgehen: Setting einmal pro Request komplett laden und memoisieren (statische Property oder Cache::rememberForever mit Invalidierung in Setting::set); CartService die ShippingMethods pro Request nur einmal abfragen lassen; Cart-Props per Inertia::optional/partial reload statt auf jeder Seite.

22. PHPStan ist konfiguriert, aber nutzlos: 213 Fehler auf Level 7
Kein Baseline, nie grün — damit fängt es keine Regressionen. Die Fehler zeigen echte Schwächen: $request->user() typisiert als Customer|User|null (Guard-Generics fehlen), fehlende Array-Shapes im Cart-Array, Order::$orders-Property-Magie im Dashboard. Vorgehen: entweder auf Level 5 senken und die Restfehler fixen bis grün, oder Baseline einfrieren und in CI erzwingen — ein roter Linter ist schlechter als keiner. Das Cart-Array wäre langfristig als DTO (CartData-Klasse) statt array<string,mixed> deutlich wartbarer.

🧪 Testing
Die vorhandenen 135 Tests decken Auth, Admin-CRUD, Cart- und Invoice-Checkout ordentlich ab — aber (a) läuft die Suite gerade gar nicht (Punkt 3) und (b) fehlen genau die kritischen Pfade:

Kein Test für Zahlartwechsel (hätte Blocker 1 gefangen), Stripe-Submit, Stripe-Webhook-Signatur/markPaid, alle PayPal-Endpoints (create/capture/afterPayment/webhook), Success-Page-Autorisierung, Varianten-Kauf.
Vorgehen: Nach dem Framework-Update zuerst Regressionstests für die Blocker-Fixes schreiben, dann: Stripe via StripeClient-Fake im Container, PayPal via Http::fake()/Service-Mock, Webhooks mit selbst signierten Payloads. Ziel: jeder der drei Bezahlwege hat einen End-to-End-Feature-Test vom Cart bis status=paid + Mail-Assertions (Mail::fake).
Empfohlene Reihenfolge
composer update laravel/framework → Testsuite grün (Fundament für alles Weitere)
Blocker 1, 2, 4 fixen (Zahlartauswahl, Varianten-Entscheidung, PayPal-Return) — jeweils mit Tests
Security-Paket: Seeder-Passwort, env()→config(), Success-Page-Auth, Capture-Ownership, GET→POST-Checks, Settings-Whitelist, Rating-Throttle
Businesslogik: is_available-Enforcement, Live-Preise im Cart, Dashboard-Filter, verwaiste Orders
Simplifizierung: toter Code raus, Adressregeln deduplizieren, Settings-Cache
PHPStan grün ziehen + CI (Tests & Pint & PHPStan) aufsetzen
Punkte 1–3 sind aus meiner Sicht harte Voraussetzung für den 1.0-Release; 4–6 können direkt danach kommen. Sag Bescheid, womit ich anfangen soll — Schritt 1+2 würde ich in einem Rutsch machen, da die Fixes ohne laufende Tests nicht seriös verifizierbar sind.