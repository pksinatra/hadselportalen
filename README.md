# HadselPortalen

HadselPortalen er en automatisert lokal informasjonsportal for Hadsel kommune. Prosjektet er ikke en nettavis og er ikke en offisiell kommunal nettside. Portalen samler, strukturerer og peker videre til informasjon fra tydelig oppgitte originalkilder.

## Første utviklingsfase

Repoet inneholder første versjon av den gjenbrukbare WordPress-pluginen **Lokalportalen Core**. Den etablerer:

- innholdstypene Kilde, Sted, Aktuelt og Arrangement
- felles taksonomier for sted og kategori
- strukturerte felt for kilde, datoer, kartposisjon og kontrollstatus
- RSS/Atom-import med kilde-ID og URL-basert duplikatkontroll
- publiseringsmodus per kilde: kladd eller direkte publisering
- importlogg og manuell import fra WordPress-administrasjonen
- automatisk import via WordPress-cron, med støtte for ekte server-cron
- shortcodes for aktuelt, arrangementer og en enkel portaloversikt

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

Se [docs/operations.md](docs/operations.md) før første installasjon eller oppdatering i staging/produksjon.

Produksjonsversjonen av `HadselPortalen RSS 0.2.0` er arkivert uendret i `legacy/hadsel-rss-0.2.0/`.
