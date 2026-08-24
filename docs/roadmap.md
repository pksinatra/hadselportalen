# Videre utviklingsplan

Denne planen skiller mellom den gjenbrukbare `Lokalportalen Core` og Hadsel-spesifikt innhold, design og kildeoppsett. Hver fase skal kunne testes og rulles tilbake separat.

## Status

- Fase 1 – teknisk grunnplattform: fullført og satt i produksjon.
- Fase 2 – kildekontroll og redaktørvisning: fullført og satt i produksjon.
- Fase 3 – arrangementer og katalogmodell: fullført som skjult prototype. Hurtigrutens Hus er første arrangementskilde, og katalogen har et kvalitetssikret startutvalg i kladd.
- Fase 4 – praktiske meldinger, filtrert katalog og jobbmodul: satt i produksjon som skjult redaktørprototype.

## Neste leveranse – portalprototype

1. Gjennomgå og redaksjonelt godkjenne startutvalget sammen med samarbeidspartnerne.
2. Supplere katalogen med Melbu, øvrige lokalsamfunn og lag/foreninger.
3. Teste søk, filtre og kortvisning grundig på fysisk mobil og desktop.
4. Avklare bilder, kreditering og eventuell gjenbruk av flere VisitStokmarknes Explore-tekster.
5. Holde prototypen skjult for vanlige besøkende til innhold, kilder og visning er godkjent.

Det første innholdsutvalget prioriterer Stokmarknes 250, Vesterålen Vinfestival og norske bearbeidinger av VisitStokmarknes Explore. Se [content-sources.md](content-sources.md).

## Innsending og eierskap

- Lage separate skjemaer for arrangement og katalogoppføring.
- Lagre alle innsendinger som kladd; ingen anonym innsending publiseres direkte.
- Bruke nonce, spamvern, ratebegrensning og tydelig personverntekst.
- Lagre eierstatus og bekreftelsesdato uten å vise private kontaktdata offentlig.
- Innføre «gjør krav på oppføringen» som en moderert forespørsel, ikke automatisk eierskifte.
- Varsle redaktør om oppføringer som ikke er kontrollert innen valgt tidsgrense.

## Ledige stillinger

- Bruke NAVs registrerte stillingsfeed som hovedkilde og filtrere geografisk til Hadsel.
- Bruke NAV-data også når originalannonsen kommer fra FINN, fremfor å skrape FINN direkte.
- Teamtailor-adapteren for Hadsel-relevante Nordlaks-stillinger er satt i produksjon og importerer til kladd.
- Koble på dokumenterte lokale karrieresider én om gangen.
- Lagre original URL, arbeidsgiver, arbeidssted, ansettelsesform, stillingsprosent og søknadsfrist.
- Skjule utløpte eller trukne annonser automatisk.
- Beholde søknadsprosessen hos originalkilden; HadselPortalen skal ikke motta jobbsøknader.

## Praktiske datakilder

Integrasjoner innføres én om gangen gjennom normaliserte adaptere:

1. Vær fra en offentlig, dokumentert API-kilde.
2. Veg- og trafikkmeldinger fra offentlig datastrøm med geografisk avgrensning til Hadsel.
3. Ferge og hurtigbåt fra Entur eller annen dokumentert sanntidskilde.
4. Strøm- og driftsmeldinger bare når leverandøren tilbyr en stabil og tillatt kilde.
5. Nød- og vaktinformasjon som redaksjonelt kontrollert grunninformasjon der sanntidskilde ikke finnes.

Hver adapter skal ha kildeangivelse, tidsstempel, utløpsregel, feilstatus og cache. En feilende ekstern kilde skal aldri gjøre portalforsiden utilgjengelig.

## Fast redaksjonelt innhold

- Hadsel og lokalsamfunnene.
- Lokalhistorie og den opprinnelige HadselPortalen.
- Flytte til Hadsel.
- Transport og praktiske veivisere.
- Opplevelser, turer og severdigheter.
- Tydelig samarbeid og krysslenking med VisitStokmarknes uten å duplisere guidens innhold.

Dette innholdet bygges som ordinære, redigerbare WordPress-sider og kobles til portalens steder og kategorier der det gir mening.

## Offentlig lansering

Før den nye portalen erstatter dagens forside:

- ta database- og filbackup
- teste med redaktør, mobil, desktop og tastaturnavigasjon
- kontrollere alle kilde- og originallenker
- verifisere cron, cache, feillogg og utløp
- kontrollere personvern, skjema og moderering
- lage versjonert utrullingspakke og eksplisitt rollback
- publisere i en avgrenset pilotperiode før bredere kildeinnhenting aktiveres

## Gjenbrukbar portalmal

Når Hadsel-versjonen er stabil, skilles følgende helt fra kjernen:

- kommune- og stedsnavn
- farger, logo og presentasjonstekster
- kilder og API-konfigurasjon
- kategorier og startinnhold
- promoer og samarbeidspartnere

Malen kan deretter pakkes med en oppstartsveiviser, eksempelinnhold og dokumentert importgrensesnitt uten Hadsel-spesifikke standardverdier.
