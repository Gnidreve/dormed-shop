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
| `manufacturers`       | Hersteller (FK von `products`)             |
| `categories`          | Produktkategorien                          |
| `orders`              | Bestellungen (FK zu `customers`)           |
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

| Spalte         | Typ      | Nullable | Default     | Hinweis                        |
|----------------|----------|----------|-------------|--------------------------------|
| `id`           | integer  | NO       | —           | PK, autoincrement              |
| `customer_id`  | integer  | NO       | —           | FK → `customers.id` CASCADE    |
| `status`       | varchar  | NO       | `'pending'` | pending/processing/completed/cancelled |
| `total_amount` | numeric  | NO       | —           | decimal(10,2)                  |
| `created_at`   | datetime | YES      | —           |                                |
| `updated_at`   | datetime | YES      | —           |                                |

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

## Infrastruktur-Tabellen (Laravel intern)

Diese Tabellen werden von Laravel verwaltet und sollten nicht manuell geändert werden.

| Tabelle                 | Zweck                          |
|-------------------------|--------------------------------|
| `cache`                 | Cache-Einträge (key/value/ttl) |
| `cache_locks`           | Distributed Locks              |
| `jobs`                  | Queue-Jobs                     |
| `job_batches`           | Job-Batches                    |
| `failed_jobs`           | Fehlgeschlagene Jobs           |
| `password_reset_tokens` | Reset-Token (email/token/ttl)  |
| `migrations`            | Migrationsstatus               |

---

## Fremdschlüssel-Übersicht

| Tabelle    | Spalte          | Ziel                   | ON DELETE |
|------------|-----------------|------------------------|-----------|
| `products` | `manufacturer_id` | `manufacturers.id`   | CASCADE   |
| `orders`   | `customer_id`   | `customers.id`         | CASCADE   |
| `passkeys` | `customer_id`   | `customers.id`         | CASCADE   |
