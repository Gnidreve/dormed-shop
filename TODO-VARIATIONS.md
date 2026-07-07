# TODO - Variantenmodell

> **Status 07.07.2026 — für 1.0 weitgehend hinfällig, als 1.1-Idee behalten.**
>
> Der eigentliche Bug hinter diesem Dokument (angezeigter Variantenpreis ≠
> berechneter Preis, unklare Semantik) ist mit ANALYSE-V2 Businesslogik 5
> gelöst — allerdings mit dem **absoluten** Preismodell, konsequent
> durchgezogen: Produkte mit Varianten sind nur noch als konkrete Variante
> bestellbar, `product_variants.price` ist der volle Endpreis und wandert
> exakt so in Warenkorb und Order-Snapshot. Das Modell ist damit in sich
> konsistent; `products.price` gilt nur noch für variantenlose Produkte.
>
> Was von diesem Dokument übrig bleibt (optionaler 1.1-Refactor, kein Bug):
>
> - Admin-Ergonomie „Aufpreis statt Endpreis" (additives Modell) — reine
>   Geschmacks-/UX-Frage der Datenpflege.
> - Listing-Preise: Karten/Listen zeigen `products.price`. Bei Produkten mit
>   Varianten sollte der Basispreis dem Preis der Default-Variante
>   entsprechen (Pflege-Konvention) — oder später „ab X €"-Anzeige.
>
> Der Rest des Dokuments ist als Ideensammlung für Option A erhalten.

## Problem

Das aktuelle Variantenmodell ist fachlich unsauber:

- `products.price` ist der eigentliche Produktpreis.
- `product_variants.price` wird aktuell aber wie ein kompletter Ersatzpreis behandelt.
- Sobald eine Default-Variante gepflegt wird, ist unklar, ob der Produktpreis noch gilt oder schon ueberschrieben wurde.
- Eine "Default-Variante" ohne Preisaufschlag ist nur schwer sauber abbildbar.
- Fuer Packungsvarianten wie `1er Pack` / `12er Pack` fuehlt sich das falsch an, weil das Basismodell des Produkts verloren geht.

Beispiel `UPP-110HG`:

- Produktpreis: `10,00 EUR`
- Variante `1er Pack`: eigentlich kein anderer Preis, sondern der Basisfall
- Variante `12er Pack`: nicht `100,00 EUR statt Produktpreis`, sondern fachlich eher `+90,00 EUR` auf den Basispreis

## Ziel

Varianten sollen auf dem Produktpreis aufbauen, nicht den Produktpreis ersetzen.

Gewuenschtes Modell:

- `products.price` bleibt immer der Basispreis des Produkts.
- Varianten definieren Auswahlwerte wie `1er Pack`, `12er Pack`, `schwarz`, `weiss`, etc.
- Varianten tragen nur die Preisabweichung zum Basisprodukt.
- Der angezeigte Endpreis berechnet sich aus:

`Endpreis = products.price + product_variants.price_adjustment`

## Vorschlag

### 1. Datenmodell aendern

Statt eines absoluten Variantenpreises:

- bisher: `product_variants.price`
- neu: `product_variants.price_adjustment`

Bedeutung:

- `0.00` = keine Preisabweichung
- `90.00` = 90 EUR Aufpreis gegenueber dem Basisprodukt
- optional spaeter auch negativ moeglich, z. B. `-10.00`

Zusatz:

- `is_default` bleibt sinnvoll
- `name` oder `label` bleibt Pflicht, damit auch die Basisvariante benennbar ist

### 2. Produkt bleibt Source of Truth fuer den Basispreis

Das Produkt selbst muss immer einen gueltigen Preis haben, auch wenn Varianten existieren.

Das verhindert:

- versteckte Preislogik
- unklare Defaults
- Sonderfaelle im Listing
- widerspruechliche Seeder-Daten

### 3. Varianten nur bei Bedarf anzeigen

Frontend-Regel bleibt:

- keine Varianten vorhanden -> keine Auswahl anzeigen
- Varianten vorhanden -> Dropdown / Select anzeigen
- Preis aktualisiert sich anhand der gewaehlten Variante

## Konkretes Beispiel

### UPP-110HG

Produkt:

- `price = 10.00`

Varianten:

- `1er Pack`
  - `price_adjustment = 0.00`
  - `is_default = true`
- `12er Pack`
  - `price_adjustment = 90.00`
  - `is_default = false`

Ergebnis:

- Auswahl `1er Pack` -> `10.00 EUR`
- Auswahl `12er Pack` -> `100.00 EUR`

## Auswirkungen im Code

### Seeder

Seeder sollten kuenftig:

- immer den echten Basispreis auf `products.price` schreiben
- Varianten mit `price_adjustment` anlegen
- fuer Standardauswahlen `0.00` setzen

### Admin

Im Admin sollte die Variantenpflege sprachlich klar sein:

- nicht mehr `Preis`
- sondern `Aufpreis` oder `Preisabweichung`

Optional:

- Hilfetext: `Wird auf den Produktpreis addiert.`

### Shop-Frontend

Auf der Produktdetailseite:

- Basispreis aus Produkt laden
- bei Variantenauswahl berechneten Endpreis anzeigen

In Listen / Karten:

- entweder nur den Basispreis anzeigen
- oder spaeter "ab X EUR", falls Produkte stark abweichende Variantenpreise haben

## Migration-Idee

Pragmatischer Umbau:

1. Neue Migration fuer `product_variants`
2. Spalte `price` in `price_adjustment` umbenennen oder ersetzen
3. Bestehende Daten migrieren

Migrationslogik fuer vorhandene Varianten:

- wenn eine Variante bisher den absoluten Endpreis gespeichert hat:
  - `price_adjustment = variant.price - product.price`

Beispiel:

- Produkt `10.00`
- alte Variante `100.00`
- neue `price_adjustment = 90.00`

## Offene Entscheidung

Es gibt zwei fachliche Richtungen:

### Option A - additive Varianten

Gut fuer:

- Packgroessen
- Material-/Farbaufschlaege
- einfache Produktoptionen

Vorteil:

- passt gut zu unserem aktuellen Shop
- kleiner Umbau
- kein komplett neues Produktmodell noetig

### Option B - echte Child-Produkte

Gut fuer:

- komplett eigenstaendige Auspraegungen
- eigene SKU je Variante
- eigene Lagerbestaende
- eigene Bilder je Variante

Nachteil:

- deutlich groesserer Umbau
- fuer den aktuellen Stand wahrscheinlich ueberdimensioniert

## Empfehlung

Kurzfristig sollte Dormed Shop auf **Option A - additive Varianten** umgestellt werden.

Das ist mit dem aktuellen Datenmodell am naechsten verwandt, loest den Preisfehler sauber und passt direkt zu Faellen wie:

- `1er Pack`
- `12er Pack`
- spaeter ggf. andere Packungs- oder Ausstattungsoptionen

## Naechste Schritte

- Migration fuer `product_variants.price_adjustment` vorbereiten
- Seeder auf additives Modell umstellen
- Admin-Maske textlich und logisch anpassen
- Produktdetailseite auf berechneten Endpreis umstellen
- pruefen, ob Listenpreise spaeter `ab`-Preise benoetigen
