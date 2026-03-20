<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

    <div class="entry-content front-page-content alignfull-container">
        <?php the_content(); ?>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>
