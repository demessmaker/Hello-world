<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'wicm-developer' ); ?></a>

<style>.site-header li,.site-header ul,.site-header ol,.primary-nav li,.primary-nav ul{list-style:none!important;list-style-type:none!important;margin:0;padding:0}.primary-nav ul,.primary-nav>ul{display:flex!important;align-items:center;gap:.25rem;flex-direction:row!important}.header-inner{display:flex!important;align-items:center;justify-content:space-between}</style>
<header class="site-header" id="site-header">
    <div class="header-inner">
        <!-- Logo -->
        <div class="header-logo">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else :
                $logo_text   = get_theme_mod( 'wicm_logo_text', 'West Island Conservatory of Music' );
                $logo_accent = get_theme_mod( 'wicm_logo_accent', 'West Island' );
                // Split text: accent part in red, remainder in normal color
                $remainder   = trim( str_replace( $logo_accent, '', $logo_text ) );
            ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <span class="logo-text"><span><?php echo esc_html( $logo_accent ); ?></span> <?php echo esc_html( $remainder ); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Primary Navigation -->
        <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'wicm-developer' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => 'wicm_fallback_menu',
                'depth'          => 1,
            ) );
            ?>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <?php if ( function_exists( 'pll_the_languages' ) ) : ?>
                <div class="lang-switcher">
                    <?php pll_the_languages( array( 'show_flags' => 1, 'show_names' => 1, 'hide_current' => 1 ) ); ?>
                </div>
            <?php endif; ?>
            <?php
            $wicm_is_fr = function_exists( 'pll_current_language' ) && 'fr' === pll_current_language();
            if ( $wicm_is_fr ) {
                $cta_text = get_theme_mod( 'wicm_header_cta_text_fr', 'Réserver un essai' );
                $cta_url  = get_theme_mod( 'wicm_header_cta_url_fr', '/fr/reserver-un-essai/' );
            } else {
                $cta_text = get_theme_mod( 'wicm_header_cta_text', 'Book a Trial' );
                $cta_url  = get_theme_mod( 'wicm_header_cta_url', '/book-a-trial/' );
            }
            ?>
            <a href="<?php echo esc_url( home_url( $cta_url ) ); ?>" class="btn btn-primary btn-sm"><?php echo esc_html( $cta_text ); ?></a>

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="<?php esc_attr_e( 'Toggle menu', 'wicm-developer' ); ?>" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <nav class="mobile-menu" id="mobile-menu" role="navigation" aria-label="<?php esc_attr_e( 'Mobile Menu', 'wicm-developer' ); ?>">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'fallback_cb'    => 'wicm_fallback_menu',
            'depth'          => 1,
        ) );
        ?>
        <?php if ( function_exists( 'pll_the_languages' ) ) : ?>
            <div class="lang-switcher-mobile">
                <?php pll_the_languages( array( 'show_flags' => 1, 'show_names' => 1, 'hide_current' => 1 ) ); ?>
            </div>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( $cta_url ) ); ?>" class="btn btn-primary" style="margin: 1rem; display: block; text-align: center;"><?php echo esc_html( $cta_text ); ?></a>
    </nav>
</header>
