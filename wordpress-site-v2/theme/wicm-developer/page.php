<?php get_header(); ?>

<section class="page-hero">
    <div class="wicm-container">
        <h1><?php the_title(); ?></h1>
    </div>
</section>

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
