<?php get_header(); ?>

<section class="page-hero">
    <div class="wicm-container">
        <h1><?php the_archive_title(); ?></h1>
        <?php the_archive_description( '<p>', '</p>' ); ?>
    </div>
</section>

<div class="page-content">
    <div class="wicm-container">
        <?php if ( have_posts() ) : ?>
            <div class="blog-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                <article class="post-card">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'program-card' ) ); ?>" alt="<?php the_title_attribute(); ?>" class="post-card-image" loading="lazy">
                    <?php endif; ?>
                    <div class="post-card-body">
                        <div class="post-card-meta"><?php echo esc_html( get_the_date() ); ?></div>
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p class="post-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 25 ) ); ?></p>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
        <?php else : ?>
            <p>No posts found.</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
