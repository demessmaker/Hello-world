<?php get_header(); ?>

<main id="main-content" class="page-content">
    <div class="wicm-container">
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
