<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $icon     = get_post_meta( get_the_ID(), '_wicm_icon', true );
    $features = get_post_meta( get_the_ID(), '_wicm_features', true );
?>

<section class="page-hero">
    <div class="wicm-container">
        <?php if ( $icon ) : ?>
            <div class="program-page-icon"><?php echo esc_html( $icon ); ?></div>
        <?php endif; ?>
        <h1><?php the_title(); ?></h1>
    </div>
</section>

<div class="page-content program-single">
    <div class="wicm-container">
        <div class="program-single-grid">
            <div class="program-single-content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="program-single-image">
                        <?php the_post_thumbnail( 'large', array( 'class' => 'program-card-image' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </div>

            <div class="program-single-sidebar">
                <?php $wicm_is_fr = function_exists( 'pll_current_language' ) && 'fr' === pll_current_language(); ?>
                <?php if ( is_array( $features ) && ! empty( $features ) ) : ?>
                    <div class="program-single-features">
                        <h3><?php echo $wicm_is_fr ? esc_html( 'Ce que vous apprendrez' ) : esc_html( 'What You\'ll Learn' ); ?></h3>
                        <ul class="program-features">
                            <?php foreach ( $features as $feature ) : ?>
                                <li>
                                    <svg class="check-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <?php echo esc_html( $feature ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="program-single-cta">
                    <h3><?php echo $wicm_is_fr ? esc_html( 'Prêt à commencer?' ) : esc_html( 'Ready to Start?' ); ?></h3>
                    <p><?php echo $wicm_is_fr ? esc_html( 'Réservez un cours d\'essai gratuit et découvrez la différence.' ) : esc_html( 'Book a free trial lesson and experience the difference.' ); ?></p>
                    <?php
                    if ( $wicm_is_fr ) {
                        $trial_url  = get_theme_mod( 'wicm_header_cta_url_fr', '/fr/reserver-un-essai/' );
                        $trial_text = 'Réserver un essai gratuit';
                    } else {
                        $trial_url  = get_theme_mod( 'wicm_header_cta_url', '/book-a-trial/' );
                        $trial_text = 'Book a Free Trial';
                    }
                    ?>
                    <a href="<?php echo esc_url( home_url( $trial_url ) ); ?>" class="btn btn-primary"><?php echo esc_html( $trial_text ); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
