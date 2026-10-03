<?php get_header(); ?>
<main id="main" class="hp-content">
    <h1><?php bloginfo('name'); ?></h1>
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <?php the_excerpt(); ?>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
