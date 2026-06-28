# dormed-shop — Agent Context

## IDENTITY

- **Name:** Dormed Shop Bot
- **Creature:** Assistent und DevOps-Ingenieur
- **Language:** Deutsch

> **Datenbankschema:** [`database/SCHEMA.md`](database/SCHEMA.md) — vollständige Tabellenstruktur, Spalten, Indizes und Fremdschlüssel. Muss bei jeder Schemaänderung aktualisiert werden. Immer lesen bevor Migrationen oder Modelle erstellt werden.

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

- `Customer` — Shop-Kunden; unterstützt Passkeys + 2FA via Fortify
- `User` — interne Admin-Nutzer; eigener `LoginController`, kein Fortify
- In Svelte: `page.props.auth.user` ist immer der aktuelle `Customer`
- TypeScript-Typ: `Customer` aus `@/types`

---

## Layout-Auflösung (`resources/js/app.ts`)

| Seitenmuster  | Layout                         |
| ------------- | ------------------------------ |
| `Welcome`     | keins (standalone)             |
| `Checkout/*`  | keins (standalone)             |
| `Products/*`  | keins (standalone)             |
| `auth/*`      | `AuthLayout`                   |
| `Admin/Login` | `AuthLayout`                   |
| `settings/*`  | `AppLayout` + `SettingsLayout` |
| alles andere  | `AppLayout` (Admin)            |

Seiten ohne Layout müssen `<ShopHeader>` selbst einbinden.

---

## Routen-Überblick

| Datei                 | Routen                                    |
| --------------------- | ----------------------------------------- |
| `routes/web.php`      | Home (`/`), lädt die anderen Routedateien |
| `routes/products.php` | `GET /products`, `GET /products/search`   |
| `routes/checkout.php` | `GET /checkout`, `GET /checkout/confirm`  |
| `routes/admin.php`    | Admin-Login/Logout + Dashboard            |
| `routes/settings.php` | Profil + Sicherheitseinstellungen (Kunde) |

---

## Modelle & Datenbank

| Model          | Tabelle         | Hinweise                          |
| -------------- | --------------- | --------------------------------- |
| `Customer`     | `customers`     | Shop-Auth, Fortify, Passkeys, 2FA |
| `User`         | `users`         | Nur Admin-Auth                    |
| `Product`      | `products`      | Hat `manufacturer_id` FK          |
| `Manufacturer` | `manufacturers` |                                   |
| `Order`        | `orders`        | FK zu `customers`                 |

---

## Frontend-Struktur

```
resources/js/
├── pages/
│   ├── Welcome.svelte              # Startseite (Hero + Trust-Bar)
│   ├── Checkout/
│   │   ├── Index.svelte            # Warenkorb-Review mit Tabelle
│   │   └── Confirm.svelte          # Bestellabschluss (Adresse, Zahlung, Versand)
│   ├── Products/
│   │   └── Index.svelte            # Produktliste
│   ├── Admin/
│   │   ├── Login.svelte
│   │   └── Dashboard.svelte
│   ├── auth/                       # Fortify-Auth-Seiten
│   └── settings/                   # Profil + Sicherheit
├── components/
│   ├── ShopHeader.svelte           # Logo, Suche, User-Dropdown, Cart-Trigger
│   ├── CartSheet.svelte            # Slide-in Warenkorb (rechts), Sheet-Komponente
│   └── ui/                         # shadcn-svelte Komponentenbibliothek
├── data/
│   └── cart.json                   # Platzhalter-Daten (→ später API)
├── layouts/
│   ├── AppLayout.svelte            # Admin-Sidebar-Layout
│   ├── AuthLayout.svelte           # Auth-Seiten
│   └── settings/Layout.svelte     # Settings-Layout
├── actions/                        # Wayfinder (Controller-Routen als TS-Funktionen)
├── routes/                         # Wayfinder (Named Routes als TS-Funktionen)
└── types/
    └── index.d.ts                  # Customer-Typ + globale Props
```

---

## Platzhalter-Daten (`resources/js/data/cart.json`)

Source of Truth für Cart/Checkout bis die echte API steht. Enthält:

- `items[]` — Produkte mit SKU, Preis, Menge, Lieferzeitraum, Bild-URL
- `shippingMethods[]` — Versandoptionen mit Preis und `selected`-Flag
- `paymentMethods[]` — Zahlungsarten mit Beschreibung und `selected`-Flag
- `vatRate` — MwSt-Satz (19)
- `customer` — Lieferadresse, Rechnungsadresse

Alle drei Seiten (`CartSheet`, `Checkout/Index`, `Checkout/Confirm`) importieren diese Datei direkt für späteren API-Austausch.

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
- `ShopHeader-with-hover.svelte` — Mega-Menü-Experiment (entfernt)

Nicht importiert, können nach Designfreigabe gelöscht werden.

---

## Noch nicht gebaut

- Produktdetailseite (`/products/{id}`)
- Cart-Persistenz (aktuell nur `cart.json`)
- Bestellabschluss-Backend (`/checkout/confirm` Button ohne Action)
- Warenkorb-Seite (`/warenkorb`, verlinkt im CartSheet-Footer)
- Rechtsseiten (`/agb`, `/widerrufsbelehrung`)
- Admin-Produkt- und Bestellverwaltung
- Hersteller-Verwaltung
