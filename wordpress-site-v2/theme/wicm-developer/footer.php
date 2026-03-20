<footer class="site-footer">
    <div class="wicm-container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <div class="footer-tagline"><?php esc_html_e( 'Making music magical since 1999', 'wicm-developer' ); ?></div>
                <p><?php esc_html_e( 'Professional music education for all ages in West Island, Montreal. Piano, guitar, voice, drums, violin and more.', 'wicm-developer' ); ?></p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/westislandmusicschool" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/westislandmusicschool" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-column">
                <h4><?php esc_html_e( 'Quick Links', 'wicm-developer' ); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About Us', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>"><?php esc_html_e( 'Book a Trial', 'wicm-developer' ); ?></a></li>
                </ul>
            </div>

            <!-- Programs -->
            <div class="footer-column">
                <h4><?php esc_html_e( 'Programs', 'wicm-developer' ); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/program/piano-lessons/' ) ); ?>"><?php esc_html_e( 'Piano Lessons', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/program/guitar-lessons/' ) ); ?>"><?php esc_html_e( 'Guitar Lessons', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/program/voice-lessons/' ) ); ?>"><?php esc_html_e( 'Voice Lessons', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/program/drum-lessons/' ) ); ?>"><?php esc_html_e( 'Drum Lessons', 'wicm-developer' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/program/violin-lessons/' ) ); ?>"><?php esc_html_e( 'Violin Lessons', 'wicm-developer' ); ?></a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-column">
                <h4><?php esc_html_e( 'Contact', 'wicm-developer' ); ?></h4>
                <ul>
                    <li><?php esc_html_e( 'Pointe-Claire, QC, Canada', 'wicm-developer' ); ?></li>
                    <li><a href="mailto:info@westislandmusicschool.com">info@westislandmusicschool.com</a></li>
                    <li><?php
                        /* translators: business hours weekday */
                        esc_html_e( 'Mon-Fri: 9:00 AM - 9:00 PM', 'wicm-developer' );
                    ?></li>
                    <li><?php
                        /* translators: business hours weekend */
                        esc_html_e( 'Saturday: 9:00 AM - 5:00 PM', 'wicm-developer' );
                    ?></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php
                /* translators: %s: site name in copyright notice */
                printf( esc_html__( '%s. All rights reserved.', 'wicm-developer' ), 'West Island Music School' );
            ?>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button class="back-to-top" id="back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'wicm-developer' ); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
    </svg>
</button>

<?php wp_footer(); ?>
</body>
</html>
