# Utrullinger

## 2026-08-18 – Lokalportalen Core 0.1.0

- Lastet opp til `/www/hadselportalen/wp-content/plugins/lokalportalen-core/` via FTP.
- Filstørrelse kontrollert etter opplasting for alle seks PHP-filer.
- Aktivert i WordPress 7.0.4 uten feil.
- Opprettet aktiv kilde `Hadsel kommune` med feed `https://www.hadsel.kommune.no/feed/`.
- Kilden bruker publiseringsmodus `Til godkjenning (kladd)`.
- Første import: 10 nye, 0 hoppet over, 0 feil.
- Andre import: 0 nye, 10 hoppet over, 0 feil.
- Opprettet skjult sideutkast `Portaltest – skjult` med `[lokalportalen_forside]`.
- Forhåndsvisning rendret Aktuelt og Arrangementer uten PHP- eller WordPress-feil.
- Offentlig forside kontrollert uendret etter utrulling.

Den eldre `HadselPortalen RSS 0.2.0` forble aktiv og uendret.

## 2026-08-18 – Lokalportalen Core 0.2.0

- La til maksimum antall elementer, aldersgrense og inkluder-/ekskluderfiltre per kilde.
- La til kilde- og originallenker i redaksjonell innholdsliste.
- Forbedret importresultat med egne tall for duplikater, filtrerte elementer og feil.
- La til redaktørbeskyttet forhåndsvisning av kladder.
- La til responsivt kortdesign for Aktuelt og Arrangementer.
- Konfigurerte Hadsel kommune med 20 elementer per import og 30 dagers aldersgrense.
- Produksjonstest: 0 nye, 10 duplikater, 0 filtrert, 0 feil.
- Skjult portalside viste seks kladdekort med kilde og lenke til originalen.
- Offentlig forside og eldre RSS-plugin forble uendret.
