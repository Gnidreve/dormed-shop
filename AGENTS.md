# dormed-shop — Agent Context

## IDENTITY

- **Name:** Dormed Shop Bot
- **Creature:** Assistent und DevOps-Ingenieur
- **Language:** Deutsch

> **Datenbankschema:** [`database/SCHEMA.md`](database/SCHEMA.md) — vollständige Tabellenstruktur, Spalten, Indizes und Fremdschlüssel. Muss bei jeder Schemaänderung aktualisiert werden. Immer lesen bevor Migrationen oder Modelle erstellt werden.
>
> **Projekt-Landkarte:** [`README.md`](README.md) — übergreifende Flows (Checkout, PayPal, Verifikation, Varianten, Settings), Betrieb & Launch-Checkliste. Bei Flow-Änderungen mitpflegen.

Ablösung für Shopware. Medical equipment online shop für dormed 24 (Medizintechnik). Gebaut mit Laravel 13 + Inertia v3 + Svelte 5.

## Was diese App ist

B2B/B2C-Shop für Medizintechnik (Ultraschallsysteme, Zubehör, Verbrauchsmaterialien). Zwei eigenständige Bereiche:

- **Shop-Frontend** — öffentlich, kein persistentes Layout, eigener `ShopHeader`
- **Admin-Panel** — intern, Sidebar-`AppLayout`, eigener Auth-Guard

---

## Auth: Zwei unabhängige Guards

| Guard   | Model      | Tabelle     | Login-Route    | Middleware       |
| ------- | ---------- | ----------- | -------------- | ---------------- |
| `web`   | `Customer` | `customers` | `/login`       | `auth` (Fortify) |
| `admin` | `User`     | `users`     | `/admin/login` | `ensure.admin`   |

- `Customer` — Shop-Kunden; Passkeys + 2FA via Fortify, implementiert `MustVerifyEmail`
- **Bestell-Hebel:** `verified`-Middleware auf `checkout.confirm/address/submit` und `paypal.order.create/capture` — ohne bestätigte E-Mail keine Bestellung
- `User` — interne Admin-Nutzer; eigener `LoginController`, kein Fortify; `EnsureAdmin` prüft Login **und** `is_admin`-Flag
- In Svelte: `page.props.auth.user` ist immer der aktuelle `Customer`
- TypeScript-Typ: `Customer` aus `@/types`

---

## Layout-Auflösung (`resources/js/app.ts`)

| Seitenmuster                  | Layout                              |
| ----------------------------- | ----------------------------------- |
| `Admin/*` (außer Login)       | `AppLayout` (Admin-Sidebar)         |
| `Admin/Login`, `auth/*`       | `AuthLayout`                        |
| `settings/*`                  | `CustomerLayout` + `SettingsLayout` |
| alles andere (Shop-Frontend)  | keins (standalone)                  |

Standalone-Seiten (Welcome, Products/*, Checkout/*, statische Seiten) binden `<ShopHeader>` + `<AppFooter>` selbst ein.

---

## Routen-Überblick

| Datei                      | Routen                                    |
| -------------------------- | ----------------------------------------- |
| `routes/web.php`           | Home (`/`), statische Seiten (AGB, FAQ, …), Sitemap; lädt die anderen Routedateien |
| `routes/products.php`      | `GET /products`, `/products/search`, `/products/{product}` |
| `routes/categories.php`    | `GET /{category:slug}` (Catch-all, ein Segment) |
| `routes/public/rating.php` | `POST /products/{product}/ratings` (anonym — siehe TODO) |
| `routes/checkout.php`      | Cart (`/cart/items/{product}/{variant?}`), Checkout (`confirm`/`address`/`submit` mit `auth`+`verified`), Success, Kundenbestellungen |
| `routes/paypal.php`        | `order/create` + `order/capture` (`auth`+`verified`), `after-payment`, `webhook` (signaturverifiziert, ohne CSRF) |
| `routes/admin.php`         | Admin-CRUD (Produkte inkl. Bilder/Varianten, Kategorien, Hersteller, Kunden), Bestell-Aktionen (`orders.status`, `orders.refund`), Settings + Versandarten |
| `routes/settings.php`      | Profil, Adressen, Sicherheit (Kunde)      |

---

## Modelle & Datenbank

| Model            | Tabelle            | Hinweise                          |
| ---------------- | ------------------ | --------------------------------- |
| `Customer`       | `customers`        | Shop-Auth, Fortify, Passkeys, 2FA, `MustVerifyEmail` |
| `User`           | `users`            | Nur Admin-Auth (`is_admin`-Flag)  |
| `Product`        | `products`         | `manufacturer_id`/`category_id` FKs, `is_available`, `available()`-Scope |
| `ProductVariant` | `product_variants` | Absoluter Endpreis (`price`), `is_default`, `sort_order` |
| `ProductImage`   | `product_images`   | public-Disk, `sort_order` (0 = Hauptbild) |
| `Manufacturer`   | `manufacturers`    |                                   |
| `Category`       | `categories`       | Slug-basierte Shop-Route          |
| `Order`          | `orders`           | FK zu `customers` **nullable, ON DELETE SET NULL** (Historie überlebt Kontolöschung); Adress-Snapshots als JSON |
| `OrderItem`      | `order_items`      | Snapshot: `product_name` (inkl. Varianten-Label), `unit_price` |
| `Payment`        | `payments`         | PayPal-Transaktionen (order_id, capture_id, response_data) |
| `Rating`         | `ratings`          | Anonym (Stand jetzt), FK `products` |
| `Address`        | `addresses`        | Kunden-Stammadressen (shipping/billing, `is_default`) |
| `ShippingMethod` | `shipping_methods` | Versandarten inkl. Preis          |
| `Setting`        | `settings`         | Key-Value, sensible Keys verschlüsselt, pro Request memoisiert |

---

## Frontend-Struktur

```
resources/js/
├── pages/
│   ├── Welcome.svelte              # Startseite (Hero + Trust-Bar)
│   ├── AGB/Datenschutz/FAQ/...     # Statische Seiten
│   ├── Checkout/
│   │   ├── Index.svelte            # Warenkorb-Review mit Tabelle
│   │   ├── Confirm.svelte          # Bestellabschluss (Adresse, AGB, Zahlart, PayPal-Button)
│   │   ├── Success.svelte          # Bestellbestätigung
│   │   └── Error.svelte
│   ├── Products/
│   │   ├── Index.svelte            # Produktliste (InfiniteScroll, Sortierung)
│   │   ├── ByCategory.svelte       # Kategorie-Listing
│   │   └── Show.svelte             # Detailseite (Varianten, Zoom, Ratings, JSON-LD)
│   ├── Admin/                      # Dashboard, Products, Orders, Customers,
│   │                               # Categories, Manufacturers, Settings, Login
│   ├── auth/                       # Fortify-Auth-Seiten (inkl. VerifyEmail)
│   └── settings/                   # Profil, Adressen, Sicherheit, Bestellungen (+ Show)
├── components/
│   ├── ShopHeader.svelte           # Logo, Suche (+Empty-State), User-Dropdown, Cart-Trigger
│   ├── CartSheet.svelte            # Slide-in Warenkorb (rechts)
│   ├── PayPalButton.svelte         # PayPal-JS-SDK-Integration (fetchJson)
│   ├── AddressForm.svelte          # Wiederverwendetes Adressformular (prefix-basiert)
│   └── ui/                         # shadcn-svelte Komponentenbibliothek
├── lib/
│   ├── http.ts                     # fetchJson() — CSRF-sicherer Fetch für Nicht-Inertia-Endpoints
│   └── currency.ts / utils.ts / …
├── layouts/
│   ├── AppLayout.svelte            # Admin-Sidebar-Layout
│   ├── CustomerLayout.svelte       # Kunden-Settings-Rahmen
│   ├── AuthLayout.svelte           # Auth-Seiten
│   └── settings/Layout.svelte      # Settings-Sub-Layout
├── actions/                        # Wayfinder (Controller-Routen als TS-Funktionen)
├── routes/                         # Wayfinder (Named Routes als TS-Funktionen)
├── data/cart.json                  # Altlast/Referenz — NICHT Source of Truth
└── types/                          # auth.ts, cart.ts (CartItem inkl. line_key/variant_*), …
```

**Wichtig — CSRF:** Inertias `router.*` sendet den XSRF-Token automatisch.
Rohe `fetch()`-Aufrufe **müssen** über `fetchJson()` aus `@/lib/http` laufen
(sonst 419; es gibt kein csrf-Meta-Tag im Blade-Template).

---

## Cart/Checkout-Daten

Der Cart läuft server-seitig über **`App\Support\Cart\CartService`** (Store via `CartStore`-Contract). `CartService::cart()` liefert das vollständige Cart-Array (Items, Versand-/Zahlungsarten, Adressen, Summen) und wird in `HandleInertiaRequests` als shared Prop `cart` verteilt. `resources/js/data/cart.json` ist nur noch Altlast/Referenz, **nicht** die Source of Truth.

- **Session-State:** `items[lineKey => quantity]`; Line-Key = `productId` oder `productId:variantId` (Varianten = eigene Zeilen).
- **Preise live:** Namen/Preise werden bei jedem Aufruf aus der DB gelesen; der Snapshot entsteht erst in der Order (`OrderManager::createFromCart`, transaktional).
- **Varianten:** absolutes Preismodell (`product_variants.price` = Endpreis); Produkte mit Varianten nur als Variante bestellbar; Label wird Teil des Zeilennamens.
- Frontend adressiert Cart-Zeilen über `item.line_key` (each-Key) und `/cart/items/{product}/{variant?}`.

---

## Frontend-Konventionen

### Svelte 5 Runes (zwingend)

- `let { prop } = $props()` statt `export let prop`
- `$state()`, `$derived()`, `$effect()` statt `$:`
- `onclick=` statt `on:click`
- `{#snippet name(args)}` + `{@render name()}` statt `slot`

### shadcn-svelte Regeln

- `gap-*` nicht `space-y-*` / `space-x-*`
- `size-*` wenn Breite = Höhe (z.B. `size-4` statt `w-4 h-4`)
- `Sheet`, `Dialog`, `Drawer` brauchen immer einen `Title` (ggf. `class="sr-only"`)
- Keine manuellen `z-index`-Werte auf Overlay-Komponenten
- `cn()` aus `@/lib/utils` für bedingte Klassen

### Navigation

- Interne Links immer mit `<Link>` aus `@inertiajs/svelte`, nie `<a>`
- Ausnahme: externe Links oder reine Anker (`tel:`, `mailto:`, `#section`)

### Wayfinder

- Controller-Routen: `import * as ProductController from '@/actions/App/Http/Controllers/ProductController'`
- Named Routes: `import { logout } from '@/routes'`
- URL auflösen: `toUrl(editProfile())` aus `@/lib/utils`

### Brand-Farben (Shop-Frontend)

| Name      | Wert      | Verwendung                    |
| --------- | --------- | ----------------------------- |
| Navy      | `#0d1f44` | Primäre CTAs, Hintergrundtext |
| Blue      | `#1a6bbf` | Links, Icons, Akzente         |
| Dark blue | `#1a3a5c` | Sekundärtext, Nav-Hover       |

### Button-as-Link Muster (asChild)

```svelte
<Button asChild class="...">
    {#snippet children(props)}
        <Link href="/pfad" class={props.class}>Label</Link>
    {/snippet}
</Button>
```

### SheetClose + Link (Sheet schließen + navigieren)

```svelte
<SheetClose asChild>
    {#snippet children(closeProps)}
        <Button asChild class="...">
            {#snippet children(btnProps)}
                <Link href="/pfad" class={btnProps.class} onclick={closeProps.onClick as () => void}>
                    Label
                </Link>
            {/snippet}
        </Button>
    {/snippet}
</SheetClose>
```

**Wichtig:** `SheetTrigger asChild` reicht `props.onclick` (Kleinbuchstaben) weiter, nicht `props.onClick`.

---

## Backup-Dateien (nicht aktiv)

- `ShopHeader-left-align.svelte` — alte linksbündige Header-Variante
- `ShopHeader-with-hover.svelte` — Mega-Menü-Variante

Nicht importiert. **Entscheidung Linus (07/2026): die Hover-Variante bleibt
erhalten und darf nicht gelöscht werden.**

---

## Zahlungen (Payments)

Zwei Bezahlarten — mehr gibt es nicht —, ein gemeinsamer Order-/Mail-Pfad über **`App\Support\Orders\OrderManager`**.

| Bezahlart | Flow | Order-Status nach Abschluss |
| --------- | ---- | --------------------------- |
| Rechnung (`invoice`) | `CheckoutController::submit` | bleibt `pending` (Zahlung per Überweisung) |
| PayPal (`paypal`) | `PayPalController` (JS-SDK, createOrder/captureOrder) + Return-URL `paypal/after-payment` | `paid` (nach Capture) |

Regeln:

- **`OrderManager` ist die einzige Stelle**, die aus dem Cart eine Order baut (`createFromCart`, in DB-Transaktion), Bestätigungsmails versendet (`sendConfirmations`) und „bezahlt"-Übergänge idempotent macht (`markPaid`, atomarer Statuswechsel → keine Doppel-Mails). Neue Gateways hier andocken, nicht in den Controllern duplizieren.
- **Bestellen nur mit bestätigter E-Mail** (`verified`-Middleware, siehe Auth-Abschnitt).
- **Angebotene Zahlarten sind fest verdrahtet**: Kauf auf Rechnung + PayPal (kein Admin-Setting, kein Provider-Umschalter). Labels/Reihenfolge in `config/shop.php`, Auswahl-UI: Radio auf `Checkout/Confirm`.
- **Sandbox/Live** = `App\Support\PaymentMode`. Setting `payment.mode` (sandbox|live) gewinnt, sonst Fallback auf `APP_ENV` (production = live). Im Admin unter Einstellungen → Zahlungsarten umschaltbar.
- **Secrets** liegen verschlüsselt in `settings` (`Setting::$encryptedKeys`) — **Single Source of Truth**. Es gibt keine `PAYPAL_*`-env-Fallbacks mehr; Erstbefüllung nur über `SEED_PAYPAL_*` + `PaymentSeeder`.
- **Webhooks** (`/paypal/webhook`): signaturverifiziert; `CAPTURE.COMPLETED` → `markPaid`; `CAPTURE.REFUNDED`/`DENIED` setzen Payment **und** Order (Achtung: beim Refund ist `resource.id` die Refund-ID, die Capture-ID steckt im `up`-Link).
- **Benachrichtigungs-Empfänger** = Setting `shop.notification_emails` (kommagetrennt), Fallback `mail.admin_address` → `mail.from.address`. Beide Bestellmails sind **queued** — Queue-Worker nötig.
- **Admin-Bestellaktionen**: Status setzen (optional mit Kundenmail) + PayPal-Refund unter `Admin/Orders/Show` (`orders.status`, `orders.refund`).

## Noch nicht gebaut

- Wartungsmodus, Produktfilter, Bulk-Aktionen, Bestell-/Kunden-Filter im Admin (siehe `TODO.md`)
- Rating-Throttle/Moderation (ANALYSE-V2 S-C, Wiedervorlage Linus)
- Kleinere Härtungen: Settings-Werte-Validierung, Throttle auf `paypal/order/create` (ANALYSE-V2 S-C)
