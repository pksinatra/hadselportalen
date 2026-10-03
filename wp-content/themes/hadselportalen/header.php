<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="hp-skip" href="#main">Hopp til innholdet</a>
<header class="hp-header">
    <div class="hp-wrap hp-header__inner">
        <a class="hp-brand" href="<?php echo esc_url(home_url('/')); ?>">HADSELPORTALEN</a>
        <button class="hp-menu-toggle" type="button" aria-expanded="false" aria-controls="hp-primary-menu">Meny</button>
        <nav id="hp-primary-menu" class="hp-nav" aria-label="Hovedmeny">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'hp_primary_fallback')); ?>
        </nav>
    </div>
</header>
