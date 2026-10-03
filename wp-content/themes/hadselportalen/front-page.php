<?php
get_header();

$asset = static fn(string $file): string => get_template_directory_uri() . '/assets/images/' . $file;
$themes = array(
    array(
        'id' => 'natur',
        'title' => 'Natur og aktive opplevelser',
        'intro' => 'Fra en rolig tur langs havna til høyfjell, fjorder, sykling og innendørs aktivitet når været skifter.',
        'image' => 'scenic-adventures.jpg',
        'alt' => 'Fjell- og kystlandskap i Hadsel',
        'items' => array(
            array('Storheia og utsikten', 'Fjellområdet ovenfor Stokmarknes har stier, skiløyper og vid utsikt over Hadseløya, Langøya, Børøya, Lofoten og storhavet. Vurder alltid vær og føre.'),
            array('Møysalen og nasjonalparken', 'Møysalen er et av Hadsels tydeligste landemerker. Det vernede landskapet viser møtet mellom kyst og høyfjell. Lengre turer krever planlegging og riktig utstyr.', 'https://snl.no/M%C3%B8ysalen', 'Les om Møysalen'),
            array('Trollfjorden og Raftsundet', 'Bratte fjell, smale sund, båttrafikk og kysthistorie gjør dette til et klassisk mål for sjøbaserte utflukter.', 'https://snl.no/Trollfjorden', 'Les om Trollfjorden'),
            array('Vesterålen Bike Park', 'Et lokalt sykkeltilbud på Stokmarknes for både nybegynnere og mer erfarne syklister.', 'https://www.vesteralenbikepark.no/', 'Besøk sykkelparken'),
            array('Hadsel Hallpark og Trollfjordveggen', 'Idrettsarenaer på Melbu og Stokmarknes, med klatreruter, buldring og kurs i Trollfjordveggen.', 'https://hadselhallpark.no/klatrehallen/', 'Se klatretilbudet'),
            array('Vesterålen Padel', 'Et sosialt innendørsalternativ nær Stokmarknes sentrum – særlig fint når været skifter.', 'https://vesteralenpadel.no/', 'Se baner og bestilling'),
        ),
    ),
    array(
        'id' => 'kultur',
        'title' => 'Kultur, mat og arktisk småbyliv',
        'intro' => 'Konserter, festivaler, musikkhistorie, bibliotek, kino og serveringssteder på begge sider av Hadseløya.',
        'image' => 'local-culture.jpg',
        'alt' => 'Kultur og småbyliv i Hadsel',
        'items' => array(
            array('Vesterålen Vinfestival', 'Vinfestivalen samler vininteresserte, produsenter og fagfolk til smakinger, måltider og gode møter på Stokmarknes.', 'https://www.vesteralenvinfestival.no/', 'Besøk Vesterålen Vinfestival'),
            array('Stokmarknes 250', 'Jubileumsprosjektet løfter historien, arrangementene og menneskene som har formet Stokmarknes som handelssted.', 'https://www.stokmarknes250.no/', 'Besøk Stokmarknes 250'),
            array('Hurtigrutens Hus', 'Kulturhuset rommer kino, bibliotek, kulturskole, arrangementer og møteplasser ved havna på Stokmarknes.', 'https://www.hurtigrutenshus.no/', 'Se programmet'),
            array('Madrugada har røtter her', 'Stokmarknes er hjembyen til Madrugada. Bandets nordlige opphav er en viktig del av den lokale musikkhistorien.'),
            array('Restaurant 1893', 'Fransk-nordisk mat inspirert av Vesterålen, servert ved vannet i Quality Hotel Richard With.', 'https://restaurant1893.no/', 'Besøk Restaurant 1893'),
            array('Rødbrygga', 'Et lokalt serveringssted og møtested i Markedsgata med mat og lange åpningstider.', 'https://rødbrygga.no/', 'Besøk Rødbrygga'),
            array('Sommer-Melbu', 'En tradisjonsrik festival med konserter, litteratur, filosofi, kunst og lokale aktiviteter på Melbu.'),
            array('Lokal mat og steder å bo', 'Hadsel har kafeer og restauranter ved havna, hoteller nær kaia og overnatting med utsikt mot sjø, gårdslandskap og fjell.'),
        ),
    ),
    array(
        'id' => 'historie',
        'title' => 'Kysthistorie og moderne arbeidsliv',
        'intro' => 'Handel, fiskeri, Hurtigruten, sjømatproduksjon og moderne industri møtes i historien om Hadsel.',
        'image' => 'iconic-history.jpg',
        'alt' => 'Kysthistorie og havn i Hadsel',
        'items' => array(
            array('Hurtigrutens fødested', 'Richard With og Vesteraalens Dampskibsselskab satte den første hurtigruten i trafikk i 1893 og endret kommunikasjonen langs kysten.'),
            array('Hurtigrutemuseet', 'Museet forteller historien om kystruten og lar publikum gå om bord i MS Finnmarken inne i det markante museumsbygget.', 'https://hurtigrutemuseet.no/', 'Besøk Hurtigrutemuseet'),
            array('Handelsstedet og markedsbyen', 'Stokmarknes fikk status som privilegert handelssted i 1776. De årlige markedene samlet mennesker, båter og handel fra hele regionen.'),
            array('Fiskeri og nordlandsbåter', 'Skjermede havner og rike fiskefelt gjorde Hadsel til en møteplass for båter, tørrfisk, frakt og kysthandel.'),
            array('Nordlaks og den moderne kysten', 'Nordlaks har hovedkontor på Stokmarknes og er en av Hadsels største private arbeidsgivere. På Børøya preger havbruk, sjømat og leverandørindustri dagens arbeidsliv.', 'https://nordlaks.no/', 'Besøk Nordlaks'),
            array('Vei, sjø og luft', 'Broer, ferger, hurtigbåter, buss, Hurtigruten og Stokmarknes lufthavn knytter øyene til resten av Vesterålen og Lofoten.'),
        ),
    ),
    array(
        'id' => 'perler',
        'title' => 'Små oppdagelser og åpne landskap',
        'intro' => 'Kunst ved sjøen, strender, ly mot været og korte stier der tettstedene åpner seg mot himmel og hav.',
        'image' => 'hidden-gems.jpg',
        'alt' => 'Rolig kystlandskap og skjulte steder i Hadsel',
        'items' => array(
            array('Days and Nights', 'Kunstverket på Børøya er en del av Skulpturlandskap Nordland. De lyse og mørke husformene speiler kontrastene i det nordnorske lyset.'),
            array('Bruparken og bystranda', 'Sitteplasser, grillmuligheter, sjøutsikt og en liten bystrand nær brua på Stokmarknes.'),
            array('Uværshula', 'Et værvendt, lokalt skjulested der vind, sjø og kystlandskap kommer tett på.'),
            array('Årnesan og fjæreområdene', 'Et nærturområde på Langøya med stier, rasteplasser og ly mot været.'),
            array('Kyststien på Børøya', 'Stier og tilrettelegging gjør det lettere å oppleve sjøen, brua og utsikten tilbake mot Stokmarknes.'),
            array('Små oppdagelser i byen', 'Se etter branntårnet, gjenreisningsarkitektur, lokale kafeer, båtskulpturen ved kaia og vinterlyset over Markedsgata.'),
        ),
    ),
    array(
        'id' => 'nordlys',
        'title' => 'Nordlys og mørketidskvelder',
        'intro' => 'Klare vinterkvelder, åpne horisonter og levende tettsteder gjør mørketida til en opplevelse i hele Hadsel.',
        'image' => 'northern-lights.jpg',
        'alt' => 'Nordlys over Hadsel',
        'items' => array(
            array('Hvor kan du se?', 'Velg åpne steder med fri sikt og avstand til sterkt lys. Langs fjordene og øyene finnes mange gode utsiktspunkter når forholdene er trygge.'),
            array('Når bør du gå ut?', 'De mørkeste månedene gir lange muligheter, men klar himmel, varme klær og tålmodighet er viktigere enn å jage prognoser.'),
            array('Liv i mørketida', 'Restauranter, kulturarrangementer, kino, bibliotek, puber og idrettshaller holder Hadsel levende gjennom vinterkveldene.'),
            array('Arktisk lys gjennom året', 'Blåtime, lav vintersol, lyse vårkvelder og lange sommernetter gir stadig nye uttrykk over fjordene.'),
        ),
    ),
);
?>
<main id="main">
    <section class="hp-intro">
        <div class="hp-wrap">
            <p class="hp-eyebrow">Opplev Hadsel</p>
            <h1>Nært havet. Midt i historien.</h1>
            <p class="hp-intro__lead">En lokal inngang til natur, kultur, kysthistorie, små oppdagelser og nordlys – fra Melbu og Stokmarknes til Møysalen, Raftsundet og øyene rundt.</p>
            <nav class="hp-quicknav" aria-label="Temaer">
                <?php foreach ($themes as $theme) : ?>
                    <a href="#<?php echo esc_attr($theme['id']); ?>"><?php echo esc_html($theme['title']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>
    <div class="hp-wrap hp-themes">
        <?php foreach ($themes as $theme) : ?>
            <details class="hp-theme" id="<?php echo esc_attr($theme['id']); ?>">
                <summary class="hp-theme__head">
                    <div class="hp-theme__copy">
                        <h2><?php echo esc_html($theme['title']); ?></h2>
                        <p><?php echo esc_html($theme['intro']); ?></p>
                        <span class="hp-theme__action" aria-hidden="true">Åpne kategorien</span>
                    </div>
                    <div class="hp-theme__visual">
                        <img src="<?php echo esc_url($asset($theme['image'])); ?>" alt="<?php echo esc_attr($theme['alt']); ?>" loading="lazy">
                    </div>
                </summary>
                <div class="hp-theme__items">
                    <?php foreach ($theme['items'] as $item) : ?>
                        <article class="hp-detail">
                            <h3><?php echo esc_html($item[0]); ?></h3>
                            <div class="hp-detail__body">
                                <p><?php echo esc_html($item[1]); ?></p>
                                <?php if (!empty($item[2])) : ?>
                                    <p><a href="<?php echo esc_url($item[2]); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($item[3]); ?> →</a></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</main>
<?php get_footer(); ?>
