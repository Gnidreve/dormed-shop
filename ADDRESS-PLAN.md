# Address-System — Plan

## 1. Datenbank

### 1.1 `addresses`-Tabelle (Customer-Profil)

```sql
CREATE TABLE addresses (
    id              INTEGER PRIMARY KEY AUTO_INCREMENT,
    customer_id     INTEGER NOT NULL,
    type            VARCHAR(20) NOT NULL DEFAULT 'both',
    -- 'shipping' | 'billing' | 'both'
    company         VARCHAR(255) DEFAULT NULL,
    salutation      VARCHAR(10)  DEFAULT NULL,    -- 'Herr' | 'Frau' | NULL (B2B)
    first_name      VARCHAR(255) NOT NULL,
    last_name       VARCHAR(255) NOT NULL,
    street          VARCHAR(255) NOT NULL,
    house_number    VARCHAR(20)  NOT NULL,
    address_line2   VARCHAR(255) DEFAULT NULL,    -- c/o, Adresszusatz
    zip             VARCHAR(20)  NOT NULL,
    city            VARCHAR(255) NOT NULL,
    country         VARCHAR(2)   NOT NULL DEFAULT 'DE',
    phone           VARCHAR(50)  DEFAULT NULL,
    is_default      TINYINT(1)   NOT NULL DEFAULT 0,

    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX addresses_customer_type (customer_id, type),
    INDEX addresses_zip (zip)
);
```

**Warum `house_number` separat?** — Deutsche Adressen: "Musterstraße 12a". Mit getrennten Feldern kann man später besser validieren und formatieren (PayPal/Stripe erwarten oft `street + house_number` in einem Feld).

### 1.2 `orders` — Address-Snapshots

Der `orders`-Tabelle bekommt **keine FK auf `addresses`**. Stattdessen wird die Adresse zum Zeitpunkt der Bestellung als JSON-Snapshot gespeichert — Adressen im Customer-Profil können sich später ändern, die Bestellung muss aber die ursprüngliche Adresse behalten.

**Neue Spalten in `orders`:**

| Spalte             | Typ           | Nullable | Beschreibung                              |
|--------------------|---------------|----------|-------------------------------------------|
| `shipping_address` | JSON          | YES      | Vollständiger Snapshot der Lieferadresse  |
| `billing_address`  | JSON          | YES      | Vollständiger Snapshot der Rechnungsadresse |
| `customer_email`   | VARCHAR(255)  | YES      | Kopie von Customer::email (für Nachrichten) |
| `customer_phone`   | VARCHAR(50)   | YES      | Aus der shipping_address (bei Bedarf)     |

```sql
ALTER TABLE orders
    ADD COLUMN shipping_address JSON DEFAULT NULL,
    ADD COLUMN billing_address  JSON DEFAULT NULL;
```

**JSON-Struktur** (identisch für shipping_ und billing_):
```json
{
    "company": "dormed 24 GmbH",
    "salutation": "Herr",
    "first_name": "Max",
    "last_name": "Mustermann",
    "street": "Musterstraße",
    "house_number": "12a",
    "address_line2": "c/o Empfang",
    "zip": "12345",
    "city": "Musterstadt",
    "country": "DE",
    "phone": "+49 176 12345678"
}
```

---

## 2. Eloquent Relations

### Customer
```php
// App\Models\Customer
public function addresses(): HasMany
{
    return $this->hasMany(Address::class);
}
```

### Address
```php
// App\Models\Address
protected $fillable = [
    'customer_id', 'type', 'company', 'salutation',
    'first_name', 'last_name', 'street', 'house_number',
    'address_line2', 'zip', 'city', 'country', 'phone',
    'is_default',
];

public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

// Helpers
public function fullName(): string
{
    return trim($this->first_name . ' ' . $this->last_name);
}

public function streetWithNumber(): string
{
    return trim($this->street . ' ' . $this->house_number);
}

public function fullAddress(): string
{
    return implode("\n", array_filter([
        $this->company,
        $this->fullName(),
        $this->streetWithNumber(),
        $this->address_line2,
        "{$this->zip} {$this->city}",
    ]));
}

// Für PayPal / Stripe — normiertes Array
public function toShipmentArray(): array
{
    return [
        'name'       => $this->fullName(),
        'address'    => [
            'address_line_1' => $this->streetWithNumber(),
            'address_line_2' => $this->address_line2,
            'admin_area_2'   => $this->city,
            'postal_code'    => $this->zip,
            'country_code'   => $this->country,
        ],
    ];
}
```

### Order — Snapshot-Helfer
```php
// App\Models\Order
protected function casts(): array
{
    return [
        'shipping_address' => 'array',
        'billing_address'  => 'array',
        'total_amount'     => 'decimal:2',
        'shipping_amount'  => 'decimal:2',
    ];
}

// Aus dem Snapshot lesen
public function shippingCity(): ?string
{
    return $this->shipping_address['city'] ?? null;
}

public function shippingFullName(): ?string
{
    $a = $this->shipping_address;
    if (! $a) return null;
    return trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
}
```

---

## 3. Checkout-Ablauf (neu)

### Schritt A: Kunde wählt Zahlungsart → Confirm-Seite

**Confirm.svelte** bekommt ein **Address-Formular** oberhalb der Zahlungsauswahl:

```
┌─────────────────────────────────────┐
│  Lieferadresse                      │
│  ┌────────────────────────────────┐ │
│  │ Firma (optional)               │ │
│  │ Anrede     │ Vorname * Nachname│ │
│  │ Straße *   │ Hausnr. *         │ │
│  │ Adresszusatz (optional)        │ │
│  │ PLZ *      │ Ort *             │ │
│  │ Telefon (optional)             │ │
│  └────────────────────────────────┘ │
│  □ Rechnungsadresse weicht ab      │
│  ┌─ abweichende Rechnungsadresse ─┐ │
│  │ (gleiche Felder)               │ │
│  └────────────────────────────────┘ │
├─────────────────────────────────────┤
│  Zahlungsart                        │
│  ○ PayPal  ○ Kreditkarte (Stripe)   │
├─────────────────────────────────────┤
│  Zusammenfassung + Button           │
└─────────────────────────────────────┘
```

**Datenhaltung:** Die Adressdaten werden im Session-Cart gespeichert, nicht direkt in die DB geschrieben. Erst bei `submit()` werden sie persistiert.

### Schritt B: Bestellung absenden

**Stripe (`CheckoutController::submit()`):**
1. Kunden-Adresse aus Session-Cart lesen
2. Order mit `shipping_address`-Snapshot + `billing_address`-Snapshot anlegen
3. Stripe Session mit `shipping_address_collection` + `shipping_options` erstellen
4. Weiterleitung zu Stripe

**PayPal (`PayPalController::createOrder()`):**
1. Kunden-Adresse aus Session-Cart lesen
2. Purchase-Unit mit `shipping.address` befüllen
3. Nach erfolgreichem Capture: Order mit Address-Snapshot updaten

### Schritt C: Nach erfolgreicher Zahlung

**Address speichern im Customer-Profil (optional):**
- Wenn der Kunde noch keine Adresse hat → `Address`-Record mit `type: 'both'` anlegen
- Wenn der Kunde bereits eine Adresse hat → nichts automatisieren (evtl. später "Als neue Adresse speichern"-Checkbox)

---

## 4. API / Controller-Änderungen

### Neue/geänderte Controller

| Controller                    | Methode            | Änderung                                                   |
|-------------------------------|--------------------|------------------------------------------------------------|
| `CheckoutController`          | `confirm()`        | Adressformular-Daten aus Cart laden + an Inertia übergeben |
| `CheckoutController`          | `updateAddress()`  | **NEU** — Adressdaten aus Formular in Session Cart speichern |
| `CheckoutController`          | `submit()`         | Address-Snapshot in Order schreiben                        |
| `PayPalController`            | `createOrder()`    | `shipping.address` im Purchase-Unit setzen                 |
| `CartService`                 | —                  | Address-State + Persistenz hinzufügen                      |
| `CustomerController` (Admin)  | —                  | Address-Verwaltung (optional, später)                      |

### Routen (NEU)
```php
// routes/checkout.php
Route::patch('/checkout/address', [CheckoutController::class, 'updateAddress'])
    ->name('checkout.address.update');
```

---

## 5. CartService — Address-State

Aktuell speichert `CartService` nur `items`, `shipping_method`, `payment_method`. Neu kommt dazu:

```php
private function state(): array
{
    return [
        'items'           => [...],
        'shipping_method' => '...',
        'payment_method'  => '...',
        'shipping_address' => [
            'company'       => '',
            'salutation'    => '',
            'first_name'    => '',
            'last_name'     => '',
            'street'        => '',
            'house_number'  => '',
            'address_line2' => '',
            'zip'           => '',
            'city'          => '',
            'country'       => 'DE',
            'phone'         => '',
        ],
        'billing_address' => null, // null → gleiche wie shipping
    ];
}
```

**Methoden (NEU):**
- `setShippingAddress(array $data)` — validiert + persistiert
- `setBillingAddress(?array $data)` — null = gleiche wie shipping
- `cart()`-Response enthält `shipping_address` + `billing_address`

---

## 6. Frontend — Svelte-Komponenten

### Neue Komponenten

| Komponente                         | Beschreibung                                |
|------------------------------------|---------------------------------------------|
| `components/AddressForm.svelte`    | Generisches Formular (wiederverwendbar)     |
| `components/BillingAddress.svelte` | Checkbox "weicht ab" + bedingtes Formular   |

### AddressForm Props
```ts
type AddressFormData = {
    company?: string;
    salutation?: string;
    first_name: string;
    last_name: string;
    street: string;
    house_number: string;
    address_line2?: string;
    zip: string;
    city: string;
    country: string;
    phone?: string;
};

let {
    data: AddressFormData,
    errors: Record<string, string>,
    onUpdate: (data: AddressFormData) => void,
    prefix: string,       // "shipping" | "billing"
}: Props = $props();
```

### Validierung (Client)
- `first_name`, `last_name`, `street`, `house_number`, `zip`, `city` → required
- `zip` → 5-stelliges deutsches Format `/^\d{5}$/`
- `email` → gültiges Format
- `phone` → optional, wenn angegeben `/^\+?[\d\s\-()]{6,20}$/`

### PayPal Integration — shipping.address

Im `PayPalController::createOrder()` wird beim Aufruf von `PayPalService::createOrder()` die Adresse mitgegeben:

```php
$data = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'amount' => [...],
            'shipping' => [
                'name' => ['full_name' => $address['first_name'].' '.$address['last_name']],
                'address' => [
                    'address_line_1' => $address['street'].' '.$address['house_number'],
                    'address_line_2' => $address['address_line2'] ?? '',
                    'admin_area_2'   => $address['city'],
                    'postal_code'    => $address['zip'],
                    'country_code'   => $address['country'],
                ],
            ],
        ],
    ],
    'application_context' => [...],
];
```

---

## 7. Payments-Migration (new)

```sql
ALTER TABLE orders ADD COLUMN shipping_address JSON DEFAULT NULL;
ALTER TABLE orders ADD COLUMN billing_address JSON DEFAULT NULL;
```

**Migration-Datei:** `2026_06_24_000002_add_address_fields_to_orders.php`

**Address-Model Migration:** `2026_06_24_000003_create_addresses_table.php`

---

## 8. Reihenfolge der Umsetzung

| # | Schritt                          | Aufwand | Ergebnis                          |
|---|-----------------------------------|---------|-----------------------------------|
| 1 | Migration: `addresses`-Tabelle   | ~30min  | DB-Struktur steht                 |
| 2 | Model: `Address` + Relations     | ~15min  | Eloquent-Zugriff                  |
| 3 | Migration: `orders` JSON-Spalten | ~10min  | Order-Captures                    |
| 4 | CartService: Address-State       | ~30min  | Session-Cart speichert Adresse    |
| 5 | `AddressForm.svelte`             | ~45min  | Wiederverwendbares Formular       |
| 6 | `Confirm.svelte` einbinden       | ~30min  | Formular auf Checkout-Seite       |
| 7 | PayPal: `shipping.address`       | ~15min  | PayPal bekommt Adresse            |
| 8 | Stripe: `shipping_address_collection` | ~10min | Stripe-Sammelt Adresse        |
| 9 | Order-Creation: Address-Snapshot | ~20min  | Adresse wird bei Bestellung fixiert |
|10 | Address-Validierung (Server)     | ~15min  | Sicherheit                        |
|11 | SCHEMA.md aktualisieren          | ~10min  | Dokumentation                     |

**Gesamtaufwand:** ca. 4–5 Stunden.
