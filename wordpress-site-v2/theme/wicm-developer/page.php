<?php get_header(); ?>

<div class="page-content">
    <div class="wicm-container">
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>
