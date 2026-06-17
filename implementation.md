# Implementation: Customer- und Address-Struktur für Laravel-Shop

## Ziel

Diese Implementierung trennt Kundendaten sauber von Adressdaten.

Ein Kunde kann:

- privat oder geschäftlich sein
- eine oder mehrere Adressen besitzen (Rechnungs- und Lieferadresse)
- pro Bestellung eingefrorene Adressdaten bekommen

Wichtig: Bestellungen speichern Adressen als Snapshot direkt in der `orders`-Tabelle, damit spätere Änderungen am Kundenprofil keine alten Rechnungen oder Lieferdaten verändern.

---

## Grundidee

```text
customers
└── customer_addresses   (aktuelle Kundenadressen)
└── orders               (Adress-Snapshot direkt in der Tabelle)
```

Kein separates `order_addresses`-Modell. Die Rechnungs- und Lieferadresse jeder Bestellung wird als prefixierte Spalten direkt in `orders` gespeichert.

---

## Konflikt mit bestehender `customers`-Tabelle

Die aktuelle `customers`-Tabelle ist eine Auth-Tabelle mit:

```text
name                     // einzelnes Feld
email
email_verified_at
password
remember_token
two_factor_secret
two_factor_recovery_codes
two_factor_confirmed_at
```

Diese Tabelle muss migriert werden:

- `name` → `first_name` + `last_name` (aufteilen)
- Auth-Felder (`password`, `email_verified_at`, `remember_token`, `two_factor_*`) bleiben erhalten
- Neue Felder ergänzen: `customer_type`, `phone`, `company_name`, `vat_id`

---

## Tabellenübersicht

### `customers`

Speichert Kundendaten und Auth-Informationen.

```text
id
customer_type            // private | business
first_name               // war: name (aufgeteilt)
last_name
email
email_verified_at
password
remember_token
phone                    // nullable
company_name             // nullable
vat_id                   // nullable
two_factor_secret        // nullable
two_factor_recovery_codes// nullable
two_factor_confirmed_at  // nullable
created_at
updated_at
```

### `customer_addresses`

Speichert aktuelle Adressen eines Kunden. Nur `billing` und `shipping`.

```text
id
customer_id
address_type             // billing | shipping
company_name             // nullable
first_name
last_name
street
house_number
address_extra            // nullable
zip
city
country_code
is_default
created_at
updated_at
```

### `orders`

Speichert Bestellungen inkl. eingefrorener Adress-Snapshots als direkte Spalten.

```text
id
customer_id              // nullable (Gastbestellung möglich)
order_number             // unique
status                   // pending | paid | shipped | cancelled | completed
total_net                // decimal
total_gross              // decimal
currency                 // z.B. EUR
billing_company_name     // nullable
billing_first_name
billing_last_name
billing_street
billing_house_number
billing_address_extra    // nullable
billing_zip
billing_city
billing_country_code
shipping_company_name    // nullable
shipping_first_name
shipping_last_name
shipping_street
shipping_house_number
shipping_address_extra   // nullable
shipping_zip
shipping_city
shipping_country_code
created_at
updated_at
```

---

## Warum Adress-Snapshot direkt in `orders`?

Eine separate `order_addresses`-Tabelle würde für billing und shipping immer genau zwei Zeilen pro Bestellung erzeugen — mit einem `address_type`-Feld zur Unterscheidung. Das ist unnötige Komplexität für ein festes 1:2-Verhältnis.

Stattdessen: prefixierte Spalten direkt in `orders`.

Vorteile:
- Eine Bestellung = eine Zeile, alles sichtbar
- Kein Join nötig für Adressen
- Kein `address_type`-Enum im order_addresses-Kontext

---

## Empfohlene Laravel-Struktur

```bash
php artisan make:model CustomerAddress -mf
php artisan make:migration modify_customers_table
php artisan make:migration modify_orders_table
```

---

## Migration: `customers` anpassen

```php
Schema::table('customers', function (Blueprint $table) {
    $table->string('customer_type')->default('private')->after('id');
    // Werte: private, business

    $table->string('first_name')->after('customer_type');
    $table->string('last_name')->after('first_name');
    // Hinweis: bestehendes 'name'-Feld wird nach Datenmigration entfernt

    $table->string('phone')->nullable()->after('email_verified_at');
    $table->string('company_name')->nullable()->after('phone');
    $table->string('vat_id')->nullable()->after('company_name');

    $table->index('customer_type');
});

// Datenmigration: name -> first_name + last_name
// Danach: $table->dropColumn('name');
```

---

## Migration: `customer_addresses`

```php
Schema::create('customer_addresses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('customer_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('address_type');
    // Werte: billing, shipping

    $table->string('company_name')->nullable();

    $table->string('first_name');
    $table->string('last_name');

    $table->string('street');
    $table->string('house_number');
    $table->string('address_extra')->nullable();

    $table->string('zip');
    $table->string('city');
    $table->string('country_code', 2)->default('DE');

    $table->boolean('is_default')->default(false);

    $table->timestamps();

    $table->index(['customer_id', 'address_type']);
});
```

---

## Migration: `orders` anpassen

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('order_number')->unique()->after('customer_id');

    $table->string('currency', 3)->default('EUR')->after('total_amount');
    // Hinweis: total_amount bleibt als decimal; ggf. aufteilen in total_net + total_gross

    // Billing-Adress-Snapshot
    $table->string('billing_company_name')->nullable();
    $table->string('billing_first_name');
    $table->string('billing_last_name');
    $table->string('billing_street');
    $table->string('billing_house_number');
    $table->string('billing_address_extra')->nullable();
    $table->string('billing_zip');
    $table->string('billing_city');
    $table->string('billing_country_code', 2)->default('DE');

    // Shipping-Adress-Snapshot
    $table->string('shipping_company_name')->nullable();
    $table->string('shipping_first_name');
    $table->string('shipping_last_name');
    $table->string('shipping_street');
    $table->string('shipping_house_number');
    $table->string('shipping_address_extra')->nullable();
    $table->string('shipping_zip');
    $table->string('shipping_city');
    $table->string('shipping_country_code', 2)->default('DE');
});
```

Hinweis zu Geldbeträgen: Die bestehende `total_amount`-Spalte ist `numeric` (decimal). Dieses Format bleibt erhalten — keine Integer-Cent-Umrechnung.

---

## Model: `Customer`

```php
class Customer extends Authenticatable
{
    protected $fillable = [
        'customer_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',
        'vat_id',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->where('address_type', 'billing');
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->where('address_type', 'shipping');
    }

    public function defaultBillingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('address_type', 'billing')
            ->where('is_default', true);
    }

    public function defaultShippingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('address_type', 'shipping')
            ->where('is_default', true);
    }
}
```

---

## Model: `CustomerAddress`

```php
class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'address_type',
        'company_name',
        'first_name',
        'last_name',
        'street',
        'house_number',
        'address_extra',
        'zip',
        'city',
        'country_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

## Model: `Order`

```php
class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'total_amount',
        'currency',
        'billing_company_name',
        'billing_first_name',
        'billing_last_name',
        'billing_street',
        'billing_house_number',
        'billing_address_extra',
        'billing_zip',
        'billing_city',
        'billing_country_code',
        'shipping_company_name',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_street',
        'shipping_house_number',
        'shipping_address_extra',
        'shipping_zip',
        'shipping_city',
        'shipping_country_code',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

## Beispiel: Adress-Snapshot beim Erstellen einer Bestellung

```php
$billing = $customer->defaultBillingAddress;
$shipping = $customer->defaultShippingAddress;

$order = Order::create([
    'customer_id'  => $customer->id,
    'order_number' => 'ORD-' . now()->format('YmdHis'),
    'status'       => 'pending',
    'total_amount' => 19.99,
    'currency'     => 'EUR',

    'billing_company_name'   => $billing->company_name,
    'billing_first_name'     => $billing->first_name,
    'billing_last_name'      => $billing->last_name,
    'billing_street'         => $billing->street,
    'billing_house_number'   => $billing->house_number,
    'billing_address_extra'  => $billing->address_extra,
    'billing_zip'            => $billing->zip,
    'billing_city'           => $billing->city,
    'billing_country_code'   => $billing->country_code,

    'shipping_company_name'  => $shipping->company_name,
    'shipping_first_name'    => $shipping->first_name,
    'shipping_last_name'     => $shipping->last_name,
    'shipping_street'        => $shipping->street,
    'shipping_house_number'  => $shipping->house_number,
    'shipping_address_extra' => $shipping->address_extra,
    'shipping_zip'           => $shipping->zip,
    'shipping_city'          => $shipping->city,
    'shipping_country_code'  => $shipping->country_code,
]);
```

---

## Optional: Default-Adresse erzwingen

Wenn pro Kunde nur eine Default-Adresse pro Typ erlaubt sein soll:

```php
if ($request->boolean('is_default')) {
    $customer->addresses()
        ->where('address_type', $request->address_type)
        ->update(['is_default' => false]);
}

$customer->addresses()->create($request->validated());
```

---

## Wichtige Regeln

### 1. Kundendaten bleiben Stammdaten

In `customers` gehören nur Daten, die den Kunden selbst beschreiben — kein Adressfelder direkt.

### 2. Nur `billing` und `shipping` als Adresstypen

`customer_addresses.address_type` kennt nur zwei Werte: `billing` und `shipping`.

### 3. Bestellungen speichern Adressen als Snapshot

Adressfelder werden beim Erstellen einer Bestellung aus `customer_addresses` kopiert und direkt in `orders` gespeichert. Kein Join auf Kundenadressen für Bestelldetails nötig.

### 4. Geldbeträge als Decimal

`total_amount` bleibt `decimal` — passend zur bestehenden Struktur. Keine Integer-Cent-Umrechnung.

---

## Zusammenfassung

```text
Customer
  hasMany CustomerAddress (address_type: billing | shipping)
  hasMany Order

Order
  belongsTo Customer
  // Adress-Snapshot direkt als Spalten (billing_* und shipping_*)
```
