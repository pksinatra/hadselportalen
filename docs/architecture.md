# Arkitektur

## Produktgrense

`Lokalportalen Core` skal være kommune- og temauavhengig. Hadsel-spesifikke kilder, steder, kategorier, tekster og design lagres som nettstedskonfigurasjon eller i et separat tema. Dermed kan kjernen brukes i andre lokale portaler.

## Innholdsmodell

| Type | WordPress-nøkkel | Formål |
| --- | --- | --- |
| Kilde | `lp_source` | RSS/API-kilde, publiseringsregel og importstatus |
| Sted | `lp_place` | Lokalsamfunn eller geografisk sted |
| Aktuelt | `lp_current` | Importert eller redaksjonell melding med originalkilde |
| Arrangement | `lp_event` | Tidsavgrenset aktivitet med automatisk filtrering av utløpte hendelser |
| Importlogg | `lp_import_log` | Teknisk, ikke-offentlig spor for hver importkjøring |

Taksonomiene `lp_location` og `lp_category` deles av portalinnholdet. Dette gir konsistente filtre uten å blande portaldata med vanlige WordPress-kategorier.

## Importflyt

1. En aktiv Kilde velges av cron eller en administrator.
2. WordPress henter og parser RSS/Atom med `fetch_feed()`.
3. Hvert element normaliseres og får en stabil ekstern ID.
4. Eksisterende post finnes via ekstern ID eller kanonisk URL.
5. Nye elementer opprettes som `lp_current` med kilde, utdrag og original URL.
6. Status blir `draft` eller `publish` etter kildens publiseringsmodus.
7. Importresultatet lagres i et separat importlogginnlegg.

Importer lagrer aldri hele originalartikkelen som standard. Innholdet består av et kort tekstutdrag og lenke til originalen.

## Videre utvidelser

Planlagte typer etter første fase er Virksomhet, Opplevelse, Lag og forening og Praktisk melding. API-kilder kan implementere samme normaliserte grensesnitt som RSS-importøren.

## Personvern og redaksjonell kontroll

- Kilder må ha dokumentert delingsgrunnlag.
- Nye eller usikre kilder bruker kladdemodus.
- Alle importerte elementer viser kilden tydelig.
- Administrator kan deaktivere en kilde uten å slette historikken.
- Loggen inneholder tekniske resultater, men skal ikke inneholde passord eller API-nøkler.

