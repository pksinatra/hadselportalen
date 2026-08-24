# HadselPortalen

HadselPortalen er en automatisert lokal informasjonsportal for Hadsel kommune. Prosjektet er ikke en nettavis og er ikke en offisiell kommunal nettside. Portalen samler, strukturerer og peker videre til informasjon fra tydelig oppgitte originalkilder.

## Første utviklingsfase

Repoet inneholder første versjon av den gjenbrukbare WordPress-pluginen **Lokalportalen Core**. Den etablerer:

- innholdstypene Kilde, Sted, Aktuelt, Arrangement og Praktisk melding
- felles taksonomier for sted og kategori
- strukturerte felt for kilde, datoer, kartposisjon og kontrollstatus
- RSS/Atom-import med kilde-ID og URL-basert duplikatkontroll
- publiseringsmodus per kilde: kladd eller direkte publisering
- importlogg og manuell import fra WordPress-administrasjonen
- automatisk import via WordPress-cron, med støtte for ekte server-cron
- shortcodes for aktuelt, arrangementer, praktiske meldinger, katalog og en enkel portaloversikt

Eksisterende WordPress-innhold og pluginen `hadsel-rss` endres ikke av denne kodebasen.

## Struktur

```text
docs/                                      Arkitektur og driftsrutiner
legacy/                                    Uendrede snapshots fra produksjon
wp-content/plugins/lokalportalen-core/     Gjenbrukbar portalplugin
tests/                                     Enkle kildekontroller
```

## Lokal kontroll

```bash
find wp-content/plugins/lokalportalen-core -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/source-contract.php
```

Se [docs/operations.md](docs/operations.md) før installasjon eller oppdatering, [docs/deployments.md](docs/deployments.md) for utrullingshistorikk, [docs/roadmap.md](docs/roadmap.md) for videre utviklingsrekkefølge og [docs/content-sources.md](docs/content-sources.md) for prioriterte samarbeidspartnerkilder.

Produksjonsversjonen av `HadselPortalen RSS 0.2.0` er arkivert uendret i `legacy/hadsel-rss-0.2.0/`.

## Shortcodes

- `[lokalportalen_forside]` viser praktiske meldinger, aktuelt, arrangementer, VisitStokmarknes-promo og katalog. Promoen kan skjules med `promo="0"` eller tilpasses med `promo_tittel`, `promo_tekst`, `promo_url` og `promo_lenketekst`.
- `[lokalportalen_meldinger]` viser aktive praktiske meldinger; meldinger med passert utløpsdato skjules automatisk.
- `[lokalportalen_aktuelt]`, `[lokalportalen_arrangementer]` og `[lokalportalen_finn]` viser hver sin innholdstype.
- `[lokalportalen_finn filtre="1"]` gir søk og filtre for type, sted og kategori.
- `[lokalportalen_promo tittel="Opplev Stokmarknes" tekst="En liten guide til byen." url="https://visitstokmarknes.com/" lenketekst="Besøk VisitStokmarknes"]` lager en kompakt promoflate uten å låse kjernen til én portal.
- `antall` begrenser resultatet. `kladder="1"` inkluderer kladder bare for innloggede brukere som kan redigere innlegg.
