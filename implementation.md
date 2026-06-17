# Implementation: Customer- und Address-Struktur für Laravel-Shop

## Ziel

Diese Implementierung trennt Kundendaten sauber von Adressdaten.

Ein Kunde kann:

- privat oder geschäftlich sein
- eine oder mehrere Adressen besitzen
- getrennte Rechnungs- und Lieferadressen verwenden
- pro Bestellung eingefrorene Adressdaten bekommen

Wichtig: Bestellungen speichern Adressen als Snapshot, damit spätere Änderungen am Kundenprofil keine alten Rechnungen oder Lieferdaten verändern.

---

## Grundidee

Nicht alle Adressfelder gehören direkt in die `customers`-Tabelle.

Stattdessen:

```text
customers
└── customer_addresses
└── orders
    └── order_addresses
```

---

## Tabellenübersicht

### `customers`

Speichert die eigentlichen Kundendaten.

```text
id
customer_type        // private | business
first_name
last_name
email
phone
company_name         // nullable
vat_id               // nullable
created_at
updated_at
```

### `customer_addresses`

Speichert aktuelle Adressen eines Kunden.

```text
id
customer_id
address_type         // private | business | billing | shipping
company_name         // nullable
first_name
last_name
street
house_number
address_extra        // nullable
zip
city
country_code
is_default
created_at
updated_at
```

### `orders`

Speichert Bestellungen.

```text
id
customer_id
order_number
status
total_gross
total_net
currency
created_at
updated_at
```

### `order_addresses`

Speichert eingefrorene Adressen pro Bestellung.

```text
id
order_id
address_type         // billing | shipping
company_name
first_name
last_name
street
house_number
address_extra
zip
city
country_code
created_at
updated_at
```

---

## Warum Adressen nicht direkt in `customers`?

Schlecht skalierbares Beispiel:

```text
customers
- private_street
- private_city
- business_street
- business_city
- billing_street
- billing_city
- shipping_street
- shipping_city
```

Probleme:

- viele `NULL`-Spalten
- schwer erweiterbar
- mehrere Lieferadressen kaum sauber möglich
- alte Bestellungen könnten durch Profiländerungen verfälscht werden
- keine klare Trennung zwischen Stammdaten und Adressdaten

---

## Empfohlene Laravel-Struktur

```bash
php artisan make:model Customer -m
php artisan make:model CustomerAddress -m
php artisan make:model Order -m
php artisan make:model OrderAddress -m
```

---

## Migration: `customers`

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();

    $table->string('customer_type')->default('private');
    // Werte: private, business

    $table->string('first_name');
    $table->string('last_name');

    $table->string('email')->unique();
    $table->string('phone')->nullable();

    $table->string('company_name')->nullable();
    $table->string('vat_id')->nullable();

    $table->timestamps();

    $table->index('customer_type');
});
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
    // Werte: private, business, billing, shipping

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

## Migration: `orders`

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();

    $table->foreignId('customer_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->string('order_number')->unique();

    $table->string('status')->default('pending');
    // pending, paid, shipped, cancelled, completed

    $table->unsignedInteger('total_net')->default(0);
    $table->unsignedInteger('total_gross')->default(0);

    $table->string('currency', 3)->default('EUR');

    $table->timestamps();

    $table->index('status');
});
```

Hinweis: Geldbeträge werden hier als Integer in Cent gespeichert.

Beispiel:

```text
19,99 € => 1999
```

---

## Migration: `order_addresses`

```php
Schema::create('order_addresses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('order_id')
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

    $table->timestamps();

    $table->index(['order_id', 'address_type']);
});
```

---

## Model: `Customer`

```php
class Customer extends Model
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

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function billingAddresses()
    {
        return $this->hasMany(CustomerAddress::class)
            ->where('address_type', 'billing');
    }

    public function shippingAddresses()
    {
        return $this->hasMany(CustomerAddress::class)
            ->where('address_type', 'shipping');
    }

    public function defaultBillingAddress()
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('address_type', 'billing')
            ->where('is_default', true);
    }

    public function defaultShippingAddress()
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

    public function customer()
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
        'total_net',
        'total_gross',
        'currency',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class)
            ->where('address_type', 'billing');
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class)
            ->where('address_type', 'shipping');
    }
}
```

---

## Model: `OrderAddress`

```php
class OrderAddress extends Model
{
    protected $fillable = [
        'order_id',
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
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

---

## Beispiel: Kunde mit Rechnungs- und Lieferadresse anlegen

```php
$customer = Customer::create([
    'customer_type' => 'business',
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'email' => 'max@example.com',
    'phone' => '+49123456789',
    'company_name' => 'Muster GmbH',
    'vat_id' => 'DE123456789',
]);

$customer->addresses()->create([
    'address_type' => 'billing',
    'company_name' => 'Muster GmbH',
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'street' => 'Musterstraße',
    'house_number' => '12',
    'zip' => '12345',
    'city' => 'Musterstadt',
    'country_code' => 'DE',
    'is_default' => true,
]);

$customer->addresses()->create([
    'address_type' => 'shipping',
    'company_name' => 'Muster GmbH Lager',
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'street' => 'Lagerstraße',
    'house_number' => '4',
    'zip' => '12345',
    'city' => 'Musterstadt',
    'country_code' => 'DE',
    'is_default' => true,
]);
```

---

## Beispiel: Bestellung mit Adress-Snapshot erstellen

```php
$billingAddress = $customer->defaultBillingAddress;
$shippingAddress = $customer->defaultShippingAddress;

$order = Order::create([
    'customer_id' => $customer->id,
    'order_number' => 'ORD-' . now()->format('YmdHis'),
    'status' => 'pending',
    'total_net' => 1680,
    'total_gross' => 1999,
    'currency' => 'EUR',
]);

$order->addresses()->create([
    'address_type' => 'billing',
    'company_name' => $billingAddress->company_name,
    'first_name' => $billingAddress->first_name,
    'last_name' => $billingAddress->last_name,
    'street' => $billingAddress->street,
    'house_number' => $billingAddress->house_number,
    'address_extra' => $billingAddress->address_extra,
    'zip' => $billingAddress->zip,
    'city' => $billingAddress->city,
    'country_code' => $billingAddress->country_code,
]);

$order->addresses()->create([
    'address_type' => 'shipping',
    'company_name' => $shippingAddress->company_name,
    'first_name' => $shippingAddress->first_name,
    'last_name' => $shippingAddress->last_name,
    'street' => $shippingAddress->street,
    'house_number' => $shippingAddress->house_number,
    'address_extra' => $shippingAddress->address_extra,
    'zip' => $shippingAddress->zip,
    'city' => $shippingAddress->city,
    'country_code' => $shippingAddress->country_code,
]);
```

---

## Alternative: Gleiche Adresse für Rechnung und Lieferung

Wenn Rechnungs- und Lieferadresse identisch sind, kann trotzdem in `order_addresses` zweimal gespeichert werden:

```text
billing
shipping
```

Vorteil:

- Rechnungsadresse bleibt eindeutig
- Lieferadresse bleibt eindeutig
- spätere Logik ist einfacher
- PDFs, Rechnungen und Versanddaten brauchen keine Sonderfälle

---

## Enum-Alternative für Adresstypen

Wenn du PHP 8.1+ nutzt, kannst du Enums verwenden.

```php
enum AddressType: string
{
    case Private = 'private';
    case Business = 'business';
    case Billing = 'billing';
    case Shipping = 'shipping';
}
```

Dann im Model casten:

```php
protected $casts = [
    'address_type' => AddressType::class,
    'is_default' => 'boolean',
];
```

---

## Validierung für Customer

```php
$request->validate([
    'customer_type' => ['required', 'in:private,business'],
    'first_name' => ['required', 'string', 'max:255'],
    'last_name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
    'phone' => ['nullable', 'string', 'max:255'],
    'company_name' => ['nullable', 'string', 'max:255'],
    'vat_id' => ['nullable', 'string', 'max:255'],
]);
```

---

## Validierung für Address

```php
$request->validate([
    'address_type' => ['required', 'in:private,business,billing,shipping'],
    'company_name' => ['nullable', 'string', 'max:255'],
    'first_name' => ['required', 'string', 'max:255'],
    'last_name' => ['required', 'string', 'max:255'],
    'street' => ['required', 'string', 'max:255'],
    'house_number' => ['required', 'string', 'max:50'],
    'address_extra' => ['nullable', 'string', 'max:255'],
    'zip' => ['required', 'string', 'max:20'],
    'city' => ['required', 'string', 'max:255'],
    'country_code' => ['required', 'string', 'size:2'],
    'is_default' => ['boolean'],
]);
```

---

## Wichtige Regeln

### 1. Kundendaten bleiben Stammdaten

In `customers` gehören nur Daten, die den Kunden selbst beschreiben.

Beispiele:

- Name
- E-Mail
- Telefonnummer
- Kundentyp
- Firma
- USt-ID

### 2. Adressen gehören in eigene Tabelle

Adressen können sich ändern, mehrfach existieren oder unterschiedliche Rollen haben.

### 3. Bestellungen brauchen eigene Adresskopien

Eine Bestellung darf nicht von späteren Profiländerungen abhängig sein.

### 4. Rechnungs- und Lieferadresse nicht nur referenzieren

Nicht empfohlen:

```text
orders.billing_address_id
orders.shipping_address_id
```

Problem:

Wenn die referenzierte Adresse später geändert oder gelöscht wird, verändert sich indirekt die Bestellung.

Besser:

```text
order_addresses
```

als Snapshot.

---

## Optional: Default-Adresse erzwingen

Wenn pro Kunde nur eine Default-Rechnungsadresse erlaubt sein soll, muss das auf Applikationsebene geprüft werden.

Beispiel:

```php
if ($request->boolean('is_default')) {
    $customer->addresses()
        ->where('address_type', $request->address_type)
        ->update(['is_default' => false]);
}
```

Danach die neue Adresse speichern.

---

## Empfehlung für den Start

Für eine kleine Shop-Implementation reicht dieses Modell:

```text
customers
customer_addresses
orders
order_addresses
```

Nicht direkt am Anfang nötig:

- separate `companies`-Tabelle
- separate `countries`-Tabelle
- komplexe Adresshistorie
- polymorphe Adressen
- eigene Tabellen für Privat- und Firmenkunden

Diese Dinge können später ergänzt werden, wenn der Shop wächst.

---

## Zusammenfassung

Empfohlene Struktur:

```text
Customer
hasMany CustomerAddress

Order
belongsTo Customer
hasMany OrderAddress
```

`customers` speichert den Kunden.

`customer_addresses` speichert aktuelle Kundenadressen.

`order_addresses` speichert eingefrorene Rechnungs- und Lieferadressen pro Bestellung.
