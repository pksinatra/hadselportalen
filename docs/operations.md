# Drift, utrulling og rollback

## Produksjonsstatus ved oppstart

Kartlagt 18. august 2026 uten endringer:

- WordPress 7.0.4
- aktivt tema: Inspiro
- Elementor 3.33.1
- Falang for Elementor Lite 1.32, men hovedpluginen Falang mangler
- HadselPortalen RSS 0.2.0
- sju tilgjengelige oppdateringer
- ingen feeder konfigurert i HadselPortalen RSS

Produksjonskoden for `HadselPortalen RSS 0.2.0` ble hentet skrivebeskyttet og arkivert uendret under `legacy/hadsel-rss-0.2.0/` 18. august 2026.

Elementor har et stort versjonssprang tilgjengelig. Oppdatering av WordPress, Elementor, tema eller andre plugins er ikke del av første utrulling.

## Før staging

1. Skaff SFTP/SSH eller et separat FTP-passord for automatisert utrulling. Ikke legg legitimasjon i repoet.
2. Last ned hele `wp-content` og databaseeksport til en datert, kryptert sikkerhetskopi.
3. Kontroller at snapshotet av `wp-content/plugins/hadsel-rss/` under `legacy/` fortsatt samsvarer med produksjon.
4. Opprett staging på egen database og URL.
5. Bekreft at e-post, skjemaer og søkemotorindeksering er deaktivert på staging.

## Installere på staging

Kopier bare denne mappen:

```text
wp-content/plugins/lokalportalen-core/
```

Aktiver pluginen i WordPress. Aktivering oppretter ingen portalinnhold og endrer ikke eksisterende sider. Gå deretter til **Lokalportalen → Kilder**, opprett én testkilde i kladdemodus og kjør manuell import.

## Server-cron

For stabil drift bør `DISABLE_WP_CRON` settes til `true` først når webhotellet har en ekte cron som kaller:

```text
https://hadselportalen.no/wp-cron.php?doing_wp_cron
```

Anbefalt intervall er hvert 15. minutt. Pluginens egen importjobb kjører timevis og ignorerer kilder som nylig er hentet.

## Verifikasjon

- Test at samme feed importert to ganger ikke lager duplikater.
- Test både kladdemodus og publiseringsmodus.
- Kontroller original URL, kilde, publiseringsdato og utdrag.
- Kontroller at utløpte arrangementer ikke vises i shortcode.
- Kontroller adminloggen og at ingen hemmeligheter logges.
- Kontroller forsiden på mobil og desktop før eventuell innbygging.

## Rollback

1. Deaktiver `Lokalportalen Core`.
2. Fjern pluginmappen eller erstatt den med forrige release.
3. Tøm side-/objektcache.
4. Gjenopprett database bare dersom portalinnholdet også må fjernes. Pluginen sletter ikke data ved deaktivering.
5. Eksisterende `hadsel-rss`, sider og Elementor-innhold påvirkes ikke og kan fortsette som før.

Produksjonsutrulling skal bruke en versjonert zip/release og en eksplisitt filliste. Aldri synkroniser hele en lokal WordPress-installasjon over produksjonen.
