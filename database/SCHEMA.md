# Datenbankschema — dormed-shop

> **Wartungspflicht:** Diese Datei muss bei jeder Schemaänderung (Migration, Spalte, Index, FK) sofort aktualisiert werden. Sie ist die einzige Quelle der Wahrheit für die DB-Struktur und wird automatisch in den Agent-Kontext geladen.

Engine: **SQLite** (Entwicklung) — Produktionsumgebung: MySQL/MariaDB-kompatibel

---

## Tabellen-Übersicht

| Tabelle               | Zweck                                      |
|-----------------------|--------------------------------------------|
| `users`               | Admin-Nutzer (internes Panel)              |
| `customers`           | Shop-Kunden (Frontend-Auth via Fortify)    |
| `products`            | Produkte                                   |
| `addresses`           | Adressen (Customer-Profil)                 |
| `manufacturers`       | Hersteller (FK von `products`)             |
| `categories`          | Produktkategorien                          |
| `payments`            | PayPal-Transaktionen (FK zu `orders`)      |
| `orders`              | Bestellungen (FK zu `customers`)           |
| `order_items`         | Bestellpositionen                          |
| `ratings`             | Produktbewertungen (FK zu `products`)      |
| `settings`            | Key-Value-Konfiguration                    |
| `passkeys`            | WebAuthn-Passkeys (FK zu `customers`)      |
| `sessions`            | Laravel-Session-Store                      |
| `cache`               | Laravel-Cache                              |
| `cache_locks`         | Laravel-Cache-Locks                        |
| `jobs`                | Queue-Jobs                                 |
| `job_batches`         | Queue-Job-Batches                          |
| `failed_jobs`         | Fehlgeschlagene Queue-Jobs                 |
| `password_reset_tokens` | Passwort-Reset-Tokens                    |
| `migrations`          | Laravel-Migrationsstatus                   |

---

## users

Admin-Nutzer. Kein Fortify, kein Passkey. Auth über `ensure.admin`-Middleware.

| Spalte           | Typ           | Nullable | Default | Hinweis              |
|------------------|---------------|----------|---------|----------------------|
| `id`             | integer       | NO       | —       | PK, autoincrement    |
| `name`           | varchar       | NO       | —       |                      |
| `email`          | varchar       | NO       | —       | UNIQUE               |
| `password`       | varchar       | NO       | —       |                      |
| `is_admin`       | tinyint(1)    | NO       | `0`     |                      |
| `remember_token` | varchar       | YES      | —       |                      |
| `created_at`     | datetime      | YES      | —       |                      |
| `updated_at`     | datetime      | YES      | —       |                      |

---

## customers

Shop-Kunden. Fortify-Auth, Passkeys, optionale 2FA.

| Spalte                      | Typ      | Nullable | Default | Hinweis           |
|-----------------------------|----------|----------|---------|-------------------|
| `id`                        | integer  | NO       | —       | PK, autoincrement |
| `name`                      | varchar  | NO       | —       |                   |
| `email`                     | varchar  | NO       | —       | UNIQUE            |
| `email_verified_at`         | datetime | YES      | —       |                   |
| `password`                  | varchar  | NO       | —       |                   |
| `remember_token`            | varchar  | YES      | —       |                   |
| `two_factor_secret`         | text     | YES      | —       |                   |
| `two_factor_recovery_codes` | text     | YES      | —       |                   |
| `two_factor_confirmed_at`   | datetime | YES      | —       |                   |
| `created_at`                | datetime | YES      | —       |                   |
| `updated_at`                | datetime | YES      | —       |                   |

Relationen:
- `1──N addresses` — Adressen im Customer-Profil
- `1──N orders` — Bestellungen

---

## addresses

Adressen, die von einem Customer gespeichert werden. Kein FK zu `orders` —
Bestellungen speichern ihre Adressen als JSON-Snapshot (immutable).

| Spalte          | Typ       | Nullable | Default     | Hinweis                           |
|-----------------|-----------|----------|-------------|-----------------------------------|
| `id`            | integer   | NO       | —           | PK, autoincrement                 |
| `customer_id`   | integer   | NO       | —           | FK → `customers.id` CASCADE       |
| `type`          | varchar   | NO       | `'both'`    | shipping / billing / both         |
| `company`       | varchar   | YES      | —           | Firmenname (für B2B)              |
| `salutation`    | varchar   | YES      | —           | Herr / Frau                       |
| `first_name`    | varchar   | NO       | —           |                                   |
| `last_name`     | varchar   | NO       | —           |                                   |
| `street`        | varchar   | NO       | —           |                                   |
| `house_number`  | varchar   | NO       | —           | Getrennt von Straße (z.B. "12a")  |
| `address_line2` | varchar   | YES      | —           | c/o, Adresszusatz                 |
| `zip`           | varchar   | NO       | —           |                                   |
| `city`          | varchar   | NO       | —           |                                   |
| `country`       | varchar   | NO       | `'DE'`      | ISO 3166-1 alpha-2                |
| `phone`         | varchar   | YES      | —           |                                   |
| `is_default`    | boolean   | NO       | `false`     | Standardadresse des Kunden        |
| `created_at`    | datetime  | YES      | —           |                                   |
| `updated_at`    | datetime  | YES      | —           |                                   |

Indexe:
- `addresses_customer_id_type_index` auf (`customer_id`, `type`)
- `addresses_zip_index` auf (`zip`)

---

## manufacturers

| Spalte          | Typ      | Nullable | Default | Hinweis           |
|-----------------|----------|----------|---------|-------------------|
| `id`            | integer  | NO       | —       | PK, autoincrement |
| `name`          | varchar  | NO       | —       |                   |
| `country`       | varchar  | YES      | —       |                   |
| `contact_email` | varchar  | YES      | —       |                   |
| `created_at`    | datetime | YES      | —       |                   |
| `updated_at`    | datetime | YES      | —       |                   |

---

## products

| Spalte            | Typ      | Nullable | Default | Hinweis                          |
|-------------------|----------|----------|---------|----------------------------------|
| `id`              | integer  | NO       | —       | PK, autoincrement                |
| `manufacturer_id` | integer  | NO       | —       | FK → `manufacturers.id` CASCADE  |
| `category_id`     | integer  | YES      | —       | FK → `categories.id` SET NULL    |
| `name`            | varchar  | NO       | —       |                                  |
| `description`     | text     | YES      | —       |                                  |
| `price`           | numeric  | NO       | —       | decimal(8,2)                     |
| `created_at`      | datetime | YES      | —       |                                  |
| `updated_at`      | datetime | YES      | —       |                                  |

---

## categories

| Spalte        | Typ      | Nullable | Default | Hinweis           |
|---------------|----------|----------|---------|-------------------|
| `id`          | integer  | NO       | —       | PK, autoincrement |
| `name`        | varchar  | NO       | —       |                   |
| `slug`        | varchar  | NO       | —       | UNIQUE            |
| `description` | text     | YES      | —       |                   |
| `created_at`  | datetime | YES      | —       |                   |
| `updated_at`  | datetime | YES      | —       |                   |

---

## orders

Bestellungen mit JSON-Adress-Snapshots (immutable zum Zeitpunkt der Bestellung).

| Spalte                      | Typ      | Nullable | Default     | Hinweis                            |
|-----------------------------|----------|----------|-------------|-------------------------------------|
| `id`                        | integer  | NO       | —           | PK, autoincrement                   |
| `customer_id`               | integer  | NO       | —           | FK → `customers.id` CASCADE         |
| `status`                    | varchar  | NO       | `'pending'` | pending/paid/failed/cancelled       |
| `is_test`                   | boolean  | NO       | `false`     | true = Sandbox-/Testbestellung      |
| `stripe_checkout_session_id`| varchar  | YES      | —           | UNIQUE, Stripe Session              |
| `stripe_payment_intent_id`  | varchar  | YES      | —           | Stripe Payment Intent               |
| `total_amount`              | numeric  | NO       | —           | decimal(10,2)                       |
| `shipping_amount`           | numeric  | NO       | `0.00`      | decimal(10,2), Versandkosten        |
| `shipping_address`          | JSON     | YES      | —           | Snapshot der Lieferadresse          |
| `billing_address`           | JSON     | YES      | —           | Snapshot der Rechnungsadresse       |
| `created_at`                | datetime | YES      | —           |                                     |
| `updated_at`                | datetime | YES      | —           |                                     |

JSON-Struktur (shipping_address / billing_address):
```json
{
    "company": "Firma GmbH",
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

## order_items

Einzelne Positionen einer Bestellung.

| Spalte          | Typ       | Nullable | Default | Hinweis                       |
|-----------------|-----------|----------|---------|-------------------------------|
| `id`            | integer   | NO       | —       | PK, autoincrement             |
| `order_id`      | integer   | NO       | —       | FK → `orders.id` CASCADE      |
| `product_id`    | integer   | YES      | —       | nullable (Produkt gelöscht)   |
| `product_name`  | varchar   | NO       | —       |                               |
| `unit_price`    | numeric   | NO       | —       | decimal(10,2)                 |
| `quantity`      | integer   | NO       | —       |                               |
| `created_at`    | datetime  | YES      | —       |                               |
| `updated_at`    | datetime  | YES      | —       |                               |

---

## payments

PayPal-Transaktionen, verknüpft mit einer Bestellung.

| Spalte              | Typ      | Nullable | Default     | Hinweis                                |
|---------------------|----------|----------|-------------|----------------------------------------|
| `id`                | integer  | NO       | —           | PK, autoincrement                      |
| `order_id`          | integer  | NO       | —           | FK → `orders.id` CASCADE               |
| `paypal_order_id`   | varchar  | YES      | —           | PayPal-Order-ID (z. B. `1AB23456`)     |
| `paypal_payer_id`   | varchar  | YES      | —           | Payer-ID (z. B. `PAYER123456`)         |
| `paypal_capture_id` | varchar  | YES      | —           | Capture-/Transaktions-ID               |
| `status`            | varchar  | NO       | `'CREATED'` | CREATED / APPROVED / COMPLETED / FAILED / REFUNDED |
| `amount`            | numeric  | NO       | —           | decimal(10,2)                          |
| `currency`          | varchar  | NO       | `'EUR'`     | ISO 4217                               |
| `fee`               | numeric  | YES      | —           | decimal(10,2), PayPal-Gebühr           |
| `payer_email`       | varchar  | YES      | —           | Käufer-E-Mail                          |
| `payer_name`        | varchar  | YES      | —           | Käufer-Name                            |
| `response_data`     | json     | YES      | —           | Roher API-Response (Auditing)          |
| `created_at`        | datetime | YES      | —           |                                        |
| `updated_at`        | datetime | YES      | —           |                                        |

Indexe:
- `payments_paypal_order_id_index` auf (`paypal_order_id`)
- `payments_status_index` auf (`status`)

---

## ratings

Öffentliche Produktbewertungen ohne Login.

| Spalte       | Typ      | Nullable | Default | Hinweis                     |
|--------------|----------|----------|---------|-----------------------------|
| `id`         | integer  | NO       | —       | PK, autoincrement           |
| `product_id` | integer  | NO       | —       | FK → `products.id` CASCADE  |
| `stars`      | tinyint  | NO       | —       | Wertebereich 1–5            |
| `content`    | text     | NO       | —       | Bewertungstext              |
| `created_at` | datetime | YES      | —       |                             |
| `updated_at` | datetime | YES      | —       |                             |

Indexe:
- `ratings_product_id_created_at_index` auf (`product_id`, `created_at`)

---

## settings

Key-Value-Store für Systemkonfiguration (SMTP, Shop-Name, etc.).

| Spalte       | Typ      | Nullable | Default | Hinweis      |
|--------------|----------|----------|---------|--------------|
| `key`        | varchar  | NO       | —       | PK (string)  |
| `value`      | text     | YES      | —       |              |
| `created_at` | datetime | YES      | —       |              |
| `updated_at` | datetime | YES      | —       |              |

---

## passkeys

WebAuthn-Passkeys, gehören zu einem `Customer`.

| Spalte          | Typ      | Nullable | Default | Hinweis                       |
|-----------------|----------|----------|---------|-------------------------------|
| `id`            | integer  | NO       | —       | PK, autoincrement             |
| `customer_id`   | integer  | NO       | —       | FK → `customers.id` CASCADE   |
| `name`          | varchar  | NO       | —       |                               |
| `credential_id` | varchar  | NO       | —       | UNIQUE                        |
| `credential`    | text     | NO       | —       | JSON                          |
| `last_used_at`  | datetime | YES      | —       |                               |
| `created_at`    | datetime | YES      | —       |                               |
| `updated_at`    | datetime | YES      | —       |                               |

Indexe:
- `passkeys_customer_id_index` auf (`customer_id`)

---

## sessions

| Spalte          | Typ     | Nullable | Hinweis        |
|-----------------|---------|----------|----------------|
| `id`            | varchar | NO       | PK             |
| `user_id`       | integer | YES      | nullable       |
| `ip_address`    | varchar | YES      |                |
| `user_agent`    | text    | YES      |                |
| `payload`       | text    | NO       |                |
| `last_activity` | integer | NO       | Index          |

---

## Fremdschlüssel-Übersicht

| Tabelle       | Spalte              | Ziel                   | ON DELETE |
|---------------|---------------------|------------------------|-----------|
| `products`    | `manufacturer_id`   | `manufacturers.id`     | CASCADE   |
| `products`    | `category_id`       | `categories.id`        | SET NULL  |
| `addresses`   | `customer_id`       | `customers.id`         | CASCADE   |
| `orders`      | `customer_id`       | `customers.id`         | CASCADE   |
| `order_items` | `order_id`          | `orders.id`            | CASCADE   |
| `payments`    | `order_id`          | `orders.id`            | CASCADE   |
| `ratings`     | `product_id`        | `products.id`          | CASCADE   |
| `passkeys`    | `customer_id`       | `customers.id`         | CASCADE   |
