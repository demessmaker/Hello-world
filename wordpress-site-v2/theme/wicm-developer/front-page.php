<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

    <main id="main-content" class="entry-content front-page-content alignfull-container">
        <?php the_content(); ?>
    </main>

<?php endwhile; ?>

<?php get_footer(); ?>
