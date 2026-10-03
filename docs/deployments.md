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

## 2026-08-24 – Lokalportalen Core 0.6.2

- Oppgraderte produksjonspluginen via WordPress sin versjonerte zip-erstatning.
- Beholdt en lokal rollback-pakke av forrige produksjonsversjon før oppgraderingen.
- Opprettet skjult sideutkast `Ledige stillinger i Hadsel` og utvidet den skjulte portaltesten med jobbmodulen.
- Konfigurerte to aktive Nordlaks-kilder i kladdemodus: geografisk Stokmarknes-feed og et presist Blokken-filter.
- Verifiserte sju aktive Nordlaks-kort og stabil duplikatkontroll ved ny import.
- Etablerte stedstaksonomien Stokmarknes, Melbu, Blokken, Hennes, Sandnes og Innlandet.
- Etablerte et begrenset sett portalkategorier for næringsliv, kultur, historie, opplevelser, arrangement, reiseliv, friluftsliv og lag/foreninger.
- La inn skjulte, kategoriserte utkast for Nordlaks, Stokmarknes 250, Vesterålen Vinfestival, Hurtigrutemuseet og Storheia.
- Kontrollerte at den skjulte portalen viser startinnholdet, og at VisitStokmarknes peker til `visitstokmarknes.com`.
- Offentlig forside forble uendret. NAV-feed og FINN-relatert arbeid er satt på pause i påvente av NAV-tilgang.

## 2026-08-24 – Lokalportalen Core 0.7.0

- La til shortcode og portalmodul for utvalgte lokale Facebook-grupper.
- Viser `Hadselværing` som privat gruppe og `Gamle bilder fra Hadsel` som offentlig gruppe.
- Modulen er en lenkesamling og henter ikke innlegg, medlemsdata eller andre personopplysninger fra Facebook.
- Verifiserte begge gruppelenkene, personvernmerkingen og forklaringsteksten i den skjulte portaltesten.
- Kontrollerte at VisitStokmarknes fortsatt bruker `.com`, og at offentlig forside er uendret.

## 2026-08-24 – innholdsoppdatering: Næringslivet i Hadsel

- Omskrev den offentlige siden `/naeringslivet-i-hadsel` med etterprøvbare eksempler og mindre generell reklamespråk.
- Løftet fram Nordlaks og industrimiljøet på Børøya, Skretting, leverandørnæringene og Melbu som næringssted.
- Oppdaterte omtalen av handel og tjenester, offentlig sektor, reiseliv, kultur, lokalmat, transport og framtidige muligheter.
- Fjernet en udokumentert påstand om SSB-prognoser og stabil vekst.
- La inn originallenker til Nordlaks, Skretting, Hurtigrutemuseet og VisitStokmarknes Explore (`.com`).
- Kontrollerte den publiserte siden etter oppdatering. Forrige tekst er tilgjengelig i WordPress-revisjonene.

## 2026-08-26 – Lokalportalen Core 0.9.1 og NAV stillingsfeed

- Oppgraderte produksjonspluginen via WordPress sin versjonerte zip-erstatning.
- La til NAV stillingsfeed som egen kildetype med Bearer-autentisering, eksakt kommuneavgrensning til Hadsel og håndtering av inaktive annonser.
- Lagret det private NAV-tokenet i et maskert kildefelt. Tokenverdien er ikke skrevet i repoet eller denne loggen.
- Opprettet den aktive kilden `NAV – ledige stillinger i Hadsel` i publiseringsmodus `Til godkjenning (kladd)`.
- Rettet førstegangsinnhentingen slik at `If-Modified-Since` følger alle sider i den avgrensede 180-dagersperioden.
- Fullførte førstegangsinnhentingen til NAV-feedens sluttmarkør uten importfeil.
- Verifiserte 14 aktive NAV-stillinger for Hadsel i den skjulte jobbvisningen, blant annet stillinger hos Hadsel kommune, Nordlandssykehuset, Coop Nordland og Boreal.
- Jobbsiden forble et utkast, og toppmenyen/offentlig forside ble ikke endret.

## 2026-09-06 – offentlig jobbside og Lokalportalen Core 0.9.2

- Publiserte siden `/ledige-stillinger-i-hadsel` med innledning og en visning som bare henter publiserte, aktive og ikke-utløpte stillinger.
- La `Jobb` inn som tredje punkt i hovedmenyen.
- Oppgraderte produksjonspluginen til 0.9.2.
- La til sikker statussynkronisering når en jobbkilde bytter mellom godkjenning og automatisk publisering.
- Aktiverte automatisk publisering for NAV og de to Nordlaks-kildene. Den separate Webcruiter-kilden for Nordlandssykehuset står i godkjenningsmodus for å unngå dubletter med NAV.
- Offentlig, utlogget kontroll viste 24 jobbkort, fungerende menypunkt og ingen utløpte søknadsfrister.
- Kontrollerte at `visitstokmarknes.no` ikke forekommer på jobbsiden.

## 2026-10-03 – Lokalportalen Core 0.9.5

- Formaterte datoer i portalkort med norske månedsnavn, for eksempel `25. okt. 2026`.
- Bevarer avsnitt og små undertitler fra HTML-baserte jobbfeeder i kortenes ingresser.
- Oppdaterer også ingress, tittel og relevante metadata når en eksisterende Teamtailor-stilling importeres på nytt.
- Kjørte begge Nordlaks-feedene på nytt uten importfeil.
- Kontrollerte offentlig og utlogget at `Renholdsmedarbeider i moderne kantine` og `Vi søker en ny kokk` står på egne linjer med luft før brødteksten.

## 2026-10-03 – Lokalportalen Core 0.9.6

- Reparerer manglende avsnittsgrenser i eldre, allerede importerte jobbingresser ved visning.
- Kontrollerte offentlig og utlogget at det er luft etter `Dette er Nord universitet` og etter `Velkommen til portalen for åpen søknad til AIV`.
- Kontrollerte samtidig at norske søknadsfrister og tidligere bevarte avsnitt fortsatt vises riktig.

## 2026-10-03 – offentlig katalog og samlet innholdsoversikt

- Publiserte `/finn-i-hadsel` med innledning, fritekstsøk og filtre for type, sted og kategori.
- La `Finn i Hadsel` inn som fjerde punkt i hovedmenyen.
- Publiserte det kvalitetssikrede startutvalget: Nordlaks, Stokmarknes 250, Vesterålen Vinfestival, Hurtigrutemuseet og Storheia.
- Publiserte `/hele-hadselportalen` som en samlet gjennomgangsside for praktiske meldinger, aktuelt, arrangementer, jobber, katalog, VisitStokmarknes-promo og lokale Facebook-grupper.
- Kontrollerte begge sidene offentlig og utlogget. Katalogen viste fem oppføringer, oversikten viste 22 aktive kort, og `visitstokmarknes.no` forekom ikke.

## 2026-10-03 – katalog flyttet til forsiden og Lokalportalen Core 0.9.7

- Flyttet det foreløpige utvalget på fem katalogoppføringer til forsiden som seksjonen `Finn i Hadsel`.
- Utvidet katalog-shortcoden med valgfrie attributter for seksjonstittel og innledning.
- Fjernet `Finn i Hadsel` fra hovedmenyen og la den separate siden i WordPress-papirkurven, slik at den fortsatt kan gjenopprettes.
- Kontrollerte offentlig og utlogget at forsiden viser alle fem kortene, at den gamle katalog-URL-en gir HTTP 404, og at menylenken er borte.

## 2026-10-03 – Opplev Hadsel

- Erstattet den smale Storheia-oppføringen med den norske temasiden `/experience/opplev-hadsel`.
- Bearbeidet hele innholdsutvalget fra VisitStokmarknes Explore til HadselPortalen, med fem hovedtemaer og 28 underpunkter.
- Brukte `Hadsel` for kommuneomfattende natur-, frilufts-, transport-, overnattings- og nordlystemaer, og beholdt `Stokmarknes` der omtalen gjelder konkrete steder eller lokalhistorie.
- Beholdt tydelig kildehenvisning til `visitstokmarknes.com/explore/` og relevante eksterne aktører og bakgrunnskilder.
- Oppdaterte forsiden til å vise kortet `Opplev Hadsel` med en norsk ingress.
- Kontrollerte siden og forsiden offentlig og utlogget; alle fem hovedtemaer og 28 underoverskrifter var synlige, og `visitstokmarknes.no` forekom ikke.

## 2026-10-03 – eget HadselPortalen-tema 1.0.2

- Bygget og aktiverte et eget, lett WordPress-tema uten stor hero eller avhengighet til en sidebygger.
- Bygget forsiden rundt fem temaer: natur og aktivitet, kultur og mat, kysthistorie og arbeidsliv, små oppdagelser og nordlys.
- Integrerte Vesterålen Vinfestival, Stokmarknes 250, Nordlaks og Hurtigrutemuseet i de relevante temasamlingene.
- Brukte fem redaksjonseide bilder fra VisitStokmarknes-materialet; forsiden omtaler ikke innholdet som lånt.
- Viser tittel, ingress og bilde først, mens utdypende innhold ligger i tilgjengelige kollapsbokser.
- Migrerte Markedsbyen-siden til en egen temamal og lot Lokalportalen Core fortsette å drive jobb- og portalinnhold.
- Testet temaet i WordPress på desktop og mobil før aktivering.
- Kontrollerte forsiden, jobb, næringsliv, Markedsbyen og Opplev Hadsel i produksjon etter aktivering; alle svarte med HTTP 200.
- Deaktiverte Elementor og Falang for Elementor Lite uten å slette pluginfilene. HadselPortalen RSS og Lokalportalen Core forble aktive.
- Kontrollerte at `visitstokmarknes.no` ikke forekommer i temaet.
