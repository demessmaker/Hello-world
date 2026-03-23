<?php get_header(); ?>

<section class="page-hero">
    <div class="wicm-container">
        <h1><?php the_title(); ?></h1>
    </div>
</section>

<main id="main-content" class="page-content single-post">
    <div class="wicm-container">
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="entry-meta">
                <?php echo esc_html( get_the_date() ); ?> &middot; <?php the_category( ', ' ); ?>
            </div>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
