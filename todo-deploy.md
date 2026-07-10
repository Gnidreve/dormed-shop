Das grounded jetzt richtig — und die Antwort ist erfreulich: **du hast schon ~80 % davon.** Es gibt bereits `update.sh` (dein Deploy-Skript) und GitHub-Actions-Workflows (`tests.yml`, `lint.yml`) — das ist der CI-Teil. Es fehlt nur der **Auslöser**, der `update.sh` nach einem Push auf dem Server startet. Aber bevor ich zur Architektur komme, muss ich zwei Dinge in der `update.sh` flaggen, die **jede** Automatisierung sofort gefährlich machen würden.

## ⚠️ Zwei akute Probleme in der bestehenden `update.sh`

**1. `php artisan migrate:fresh --seed --force` — das ist eine Datenbank-Bombe.**
`migrate:fresh` **droppt alle Tabellen** und baut sie neu auf. Bei *jedem* Deploy wären alle Bestellungen, Kunden, Zahlungen weg. Das steht in direktem Widerspruch zu dem B1-Guard, den wir gerade eingebaut haben. Die gute Nachricht: In Produktion greift `DB::prohibitDestructiveCommands()` (haben wir), das `migrate:fresh` **blockiert** — der Deploy würde also *abbrechen* statt wipen. Heißt aber: Das Skript ist faktisch ein **Reset-Skript für Dev/Staging**, kein Produktions-Update. Für Produktion muss die Zeile `php artisan migrate --force` heißen (spielt nur *neue* Migrationen ein, droppt nie).

**2. Kein SSR.** Das Skript baut `npm run build` (nur Client) und startet den SSR-Prozess nicht neu. Seit gestern haben wir SSR — ein Produktions-Deploy muss `npm run build:ssr` bauen **und** `inertia:start-ssr` neustarten, sonst rendert der Server veralteten oder kaputten Code.

Beides muss gefixt werden, *bevor* irgendein Auto-Deploy scharf geschaltet wird. Das ist unabhängig von der Architekturfrage.

## Deine eigentliche Frage: hält der „Laravel-Webhook, der sich selbst updatet"-Ansatz?

**Ja, mechanisch funktioniert er** — deine Sorge war teils begründet, teils nicht:

| Deine damalige Sorge | Urteil |
|---|---|
| „Geht das in Laravel überhaupt?" | **Unbegründet.** Ein signierter Webhook-Endpoint, der ein Skript triggert, ist trivial. |
| „PHP kann keinen minutenlangen Build in einem Web-Request fahren" | **Begründet.** Der Request würde timen out. Man darf den Build nicht *inline* im Request laufen lassen. |
| „Sicherheit" | **Sehr begründet.** Ein öffentlicher Endpoint, der `git pull` + `composer` + Shell ausführt, ist im Grunde *RCE-as-a-Service*, wenn die Signaturprüfung je bröckelt. Für einen Medizin-Shop mit Kundendaten der heikelste Teil. |
| „Der Web-User müsste sich selbst manipulieren" | **Begründet.** `www-data` bräuchte Git-, Composer-, npm- und Schreibrechte aufs ganze Projekt + FPM-Reload. Viel Privileg für den öffentlichen Prozess. |

Der springende Punkt: **Nicht der Mechanismus ist das Schwere, sondern das Rechte-/Sicherheitsmodell.**

## Drei Wege — und meine Empfehlung für deinen Fall

**A) GitHub Actions → SSH → `update.sh` (empfohlen).**
Du hast Actions schon. Man ergänzt einen `deploy`-Job: bei Push auf `main` verbindet sich GitHub per SSH auf den Server und ruft `update.sh`. **Kein** öffentlicher Endpoint, keine RCE-Fläche, das Secret (ein SSH-Deploy-Key) liegt in den GitHub-Actions-Secrets. Das ist der Industriestandard („push deploy") und für dich die kleinste Ergänzung, weil die halbe Infrastruktur steht. GitHub „drückt" zum Server statt der Server „lauscht".

**B) Dein Webhook-Ansatz, aber entkoppelt.**
Laravel-Route `POST /deploy`, die **nur** die GitHub-HMAC-Signatur prüft und dann *nicht* inline baut, sondern einen Trigger schreibt / einen Job dispatcht, den ein **separater Prozess** (systemd-Service oder Queue-Worker als eigener Deploy-User) abarbeitet. Das löst „minutenlanger Build im Request" und „Rechte scoped auf den Deploy-User statt www-data". Funktioniert — aber mehr bewegliche Teile als A, und die öffentliche Fläche bleibt.

**C) Laravel Envoy.**
Der Laravel-eigene SSH-Task-Runner (`envoy run deploy`). Sehr idiomatisch, wenn du „Laravel streamlined das" wörtlich willst — man ruft es lokal oder aus GitHub Actions auf. Im Kern dasselbe wie A, nur mit Laravel-Syntax für die Deploy-Steps.

**Für dich konkret:** Da du Coolify nicht nutzen kannst, CI aber schon läuft, ist **A** der Sweet Spot — reuse der vorhandenen Actions, nur ein SSH-Key als Secret, null öffentliche Angriffsfläche. Deine Webhook-Idee (B) ist elegant gedacht, aber sie tauscht Sicherheit gegen „alles in Laravel" — bei Kundendaten würde ich das nicht als erste Wahl nehmen.

**Zur Skalierung noch die Entwarnung:** Bei 5–10 Bestellungen/Tag brauchst du **keine** atomaren Symlink-Deploys (Deployer/Envoyer-Stil). Ein In-Place-`git pull` + Build hat ein Inkonsistenz-Fenster von ein paar Sekunden — bei deiner Last vernachlässigbar. Die schwere Maschinerie kannst du dir sparen.

---

Wenn du willst, setze ich Weg **A** um: die `update.sh` produktionssicher machen (`migrate --force` statt `fresh`, `build:ssr` + SSR-Neustart, opcache/FPM-Reload-Hinweis) und einen `deploy.yml`-Actions-Workflow schreiben, der nach grünem `tests.yml` per SSH deployt. Sag nur Bescheid — das ist dann echte Umsetzung, kein Nachdenken mehr.
