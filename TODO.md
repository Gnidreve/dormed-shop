# TODO — dormed-shop

## Wartungsmodus (Maintenance Mode)

**Ziel:** Wenn die Seite noch nicht live ist oder gewartet wird, soll das Frontend für normale Besucher gesperrt sein. Der Admin-Bereich bleibt erreichbar.

**Möglicher Ansatz:**
- Laravel `php artisan down --except=/admin` als Quick-Option (eingebaut)
- Oder eigene Middleware `MaintenanceMode` mit DB-Setting `shop.maintenance = true/false`
  - Leitet alle nicht-Admin-Routen auf eine statische Wartungsseite um
  - Admin-Panel bleibt normal erreichbar
  - Wartungsseite: kein Layout, kein Header, nur Nachricht + Logo

**Noch offen:**
- Wie wird der Modus geschaltet? (Admin-Setting, Artisan, Coolify-Env?)
- Soll die Wartungsseite per Coolify-Env steuerbar sein oder nur im Admin?

---
