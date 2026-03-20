<?php get_header(); ?>

<!-- HERO -->
<section class="hero-section" id="hero">
    <div class="hero-content">
        <h1 class="fade-in"><?php esc_html_e( 'Making Music Magical', 'wicm-developer' ); ?></h1>
        <p class="hero-subtitle fade-in"><?php esc_html_e( 'Music lessons for all ages in the West Island', 'wicm-developer' ); ?></p>
        <div class="hero-buttons fade-in">
            <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-primary btn-lg"><?php esc_html_e( 'Book Your Free Trial Lesson', 'wicm-developer' ); ?></a>
            <a href="#programs" class="btn btn-outline btn-lg"><?php esc_html_e( 'Discover Our Programs', 'wicm-developer' ); ?></a>
        </div>
    </div>
</section>

<!-- PROGRAMS -->
<section class="programs-section wicm-section" id="programs">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2><?php esc_html_e( 'Our Programs', 'wicm-developer' ); ?></h2>
            <p><?php esc_html_e( 'Discover your musical journey with our expert instructors', 'wicm-developer' ); ?></p>
        </div>

        <div class="programs-grid">
            <?php
            $programs = array(
                array(
                    'icon'     => '🎹',
                    'name'     => __( 'Piano', 'wicm-developer' ),
                    'image'    => 'https://images.unsplash.com/photo-1552422535-c45813c61732?w=800&h=500&fit=crop',
                    'desc'     => __( 'From classical to contemporary, learn piano at your own pace with personalized instruction for all skill levels.', 'wicm-developer' ),
                    'features' => array(
                        __( 'Classical & Contemporary', 'wicm-developer' ),
                        __( 'Music Theory', 'wicm-developer' ),
                        __( 'Sight Reading', 'wicm-developer' ),
                        __( 'Performance Skills', 'wicm-developer' ),
                    ),
                ),
                array(
                    'icon'     => '🎸',
                    'name'     => __( 'Guitar', 'wicm-developer' ),
                    'image'    => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=800&h=500&fit=crop',
                    'desc'     => __( 'Electric, acoustic, or classical - master the guitar with customized lessons tailored to your musical goals.', 'wicm-developer' ),
                    'features' => array(
                        __( 'Electric & Acoustic', 'wicm-developer' ),
                        __( 'Chord Progressions', 'wicm-developer' ),
                        __( 'Fingerpicking', 'wicm-developer' ),
                        __( 'Song Writing', 'wicm-developer' ),
                    ),
                ),
                array(
                    'icon'     => '🥁',
                    'name'     => __( 'Drums', 'wicm-developer' ),
                    'image'    => 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=800&h=500&fit=crop',
                    'desc'     => __( 'Build rhythm, coordination, and technique with dynamic drum lessons that get you playing your favorite songs.', 'wicm-developer' ),
                    'features' => array(
                        __( 'Rhythm Fundamentals', 'wicm-developer' ),
                        __( 'Hand Coordination', 'wicm-developer' ),
                        __( 'Multiple Styles', 'wicm-developer' ),
                        __( 'Band Integration', 'wicm-developer' ),
                    ),
                ),
                array(
                    'icon'     => '🎤',
                    'name'     => __( 'Voice', 'wicm-developer' ),
                    'image'    => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800&h=500&fit=crop',
                    'desc'     => __( 'Unlock your vocal potential with training in breath control, range expansion, and performance techniques.', 'wicm-developer' ),
                    'features' => array(
                        __( 'Breath Control', 'wicm-developer' ),
                        __( 'Range Expansion', 'wicm-developer' ),
                        __( 'Performance Coaching', 'wicm-developer' ),
                        __( 'Multiple Genres', 'wicm-developer' ),
                    ),
                ),
                array(
                    'icon'     => '🎻',
                    'name'     => __( 'Violin', 'wicm-developer' ),
                    'image'    => 'https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?w=800&h=500&fit=crop',
                    'desc'     => __( 'Embrace the elegance of string music with patient, step-by-step violin instruction for beginners to advanced.', 'wicm-developer' ),
                    'features' => array(
                        __( 'Classical Training', 'wicm-developer' ),
                        __( 'Proper Technique', 'wicm-developer' ),
                        __( 'Orchestra Prep', 'wicm-developer' ),
                        __( 'Solo Performance', 'wicm-developer' ),
                    ),
                ),
            );

            foreach ( $programs as $program ) :
            ?>
            <div class="program-card fade-in">
                <img src="<?php echo esc_url( $program['image'] ); ?>" alt="<?php
                    /* translators: %s: instrument name */
                    echo esc_attr( sprintf( __( '%s lessons at West Island Music School', 'wicm-developer' ), $program['name'] ) );
                ?>" class="program-card-image" loading="lazy" width="800" height="500">
                <div class="program-card-body">
                    <div class="program-card-icon"><?php echo esc_html( $program['icon'] ); ?></div>
                    <h3><?php echo esc_html( $program['name'] ); ?></h3>
                    <p><?php echo esc_html( $program['desc'] ); ?></p>
                    <ul class="program-features">
                        <?php foreach ( $program['features'] as $feature ) : ?>
                        <li>
                            <svg class="check-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <?php echo esc_html( $feature ); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo esc_url( home_url( '/programs/' ) ); ?>" class="btn btn-outline-dark btn-sm"><?php esc_html_e( 'Learn More', 'wicm-developer' ); ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section class="about-section wicm-section" id="about">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2><?php esc_html_e( 'About West Island Music', 'wicm-developer' ); ?></h2>
            <p><?php esc_html_e( 'Your one-stop destination for quality music education', 'wicm-developer' ); ?></p>
        </div>

        <div class="about-grid">
            <div class="about-card fade-in">
                <div class="about-card-icon">📖</div>
                <h3><?php esc_html_e( 'Our Story', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'For over two decades, West Island Music has been nurturing musical talent in the Montreal community. What started as a small studio has grown into a comprehensive music education center, serving students of all ages and skill levels.', 'wicm-developer' ); ?></p>
            </div>
            <div class="about-card fade-in">
                <div class="about-card-icon">🎯</div>
                <h3><?php esc_html_e( 'Our Mission', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'We believe everyone has musical potential waiting to be discovered. Our mission is to provide personalized, high-quality music education in a warm, encouraging environment where students can explore their creativity and develop lifelong skills.', 'wicm-developer' ); ?></p>
            </div>
            <div class="about-card fade-in">
                <div class="about-card-icon">🏢</div>
                <h3><?php esc_html_e( 'Our Facilities', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'Our modern studios feature professional-grade equipment, soundproofed rooms, and advanced air purification systems to ensure a comfortable learning environment.', 'wicm-developer' ); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="wicm-container">
        <div class="stats-grid">
            <div class="stat-item fade-in">
                <div class="stat-number">25+</div>
                <div class="stat-label"><?php esc_html_e( 'Years Experience', 'wicm-developer' ); ?></div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">500+</div>
                <div class="stat-label"><?php esc_html_e( 'Students Taught', 'wicm-developer' ); ?></div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">98%</div>
                <div class="stat-label"><?php esc_html_e( 'Satisfaction Rate', 'wicm-developer' ); ?></div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">15+</div>
                <div class="stat-label"><?php esc_html_e( 'Instruments Offered', 'wicm-developer' ); ?></div>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="why-section wicm-section">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2><?php esc_html_e( 'Why Choose Us', 'wicm-developer' ); ?></h2>
            <p><?php esc_html_e( 'What makes West Island Music School the right choice for your musical journey', 'wicm-developer' ); ?></p>
        </div>

        <div class="why-grid">
            <div class="why-card fade-in">
                <div class="why-card-icon">🎓</div>
                <h3><?php esc_html_e( 'Expert Teachers', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'Learn from conservatory-trained teachers with years of performance and teaching experience.', 'wicm-developer' ); ?></p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">🎯</div>
                <h3><?php esc_html_e( 'Personalized Curriculum', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'Lessons tailored to your goals, whether classical, jazz, pop, or music theory.', 'wicm-developer' ); ?></p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">👨‍👩‍👧‍👦</div>
                <h3><?php esc_html_e( 'All Ages Welcome', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'Programs for children as young as 4, teens, adults, and seniors. Beginners to advanced.', 'wicm-developer' ); ?></p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">📅</div>
                <h3><?php esc_html_e( 'Flexible Scheduling', 'wicm-developer' ); ?></h3>
                <p><?php esc_html_e( 'Convenient lesson times including evenings and weekends to fit your busy schedule.', 'wicm-developer' ); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section wicm-section" id="testimonials">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2><?php esc_html_e( 'What Our Students Say', 'wicm-developer' ); ?></h2>
            <p><?php esc_html_e( 'Join hundreds of satisfied families', 'wicm-developer' ); ?></p>
        </div>

        <div class="testimonials-grid">
            <?php
            $testimonials = array(
                array(
                    'quote' => __( 'The warm environment and supportive teachers helped me build confidence I never knew I had. My vocal range has expanded tremendously!', 'wicm-developer' ),
                    'name'  => 'Amanda Walsh',
                    'role'  => __( 'Voice Student', 'wicm-developer' ),
                ),
                array(
                    'quote' => __( "Top notch instruction quality. We've been customers for 15 years and both my children have flourished under their guidance.", 'wicm-developer' ),
                    'name'  => 'Donna Burgess',
                    'role'  => __( 'Parent', 'wicm-developer' ),
                ),
                array(
                    'quote' => __( 'The skills and lasting joy my daughter has developed through her piano lessons here are priceless. Highly recommended!', 'wicm-developer' ),
                    'name'  => 'Damien Holtz',
                    'role'  => __( 'Parent', 'wicm-developer' ),
                ),
                array(
                    'quote' => __( 'My son was selected for the Montreal Jazz Festival Blues Camp thanks to the exceptional training he received here.', 'wicm-developer' ),
                    'name'  => 'John McGuinness',
                    'role'  => __( 'Parent', 'wicm-developer' ),
                ),
            );

            foreach ( $testimonials as $t ) :
            ?>
            <div class="testimonial-card fade-in">
                <div class="testimonial-stars">
                    <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?php endfor; ?>
                </div>
                <p class="testimonial-quote">&ldquo;<?php echo esc_html( $t['quote'] ); ?>&rdquo;</p>
                <div class="testimonial-author"><?php echo esc_html( $t['name'] ); ?></div>
                <div class="testimonial-role"><?php echo esc_html( $t['role'] ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="wicm-container fade-in">
        <h2><?php esc_html_e( 'Ready to Start Your Musical Journey?', 'wicm-developer' ); ?></h2>
        <p><?php esc_html_e( 'Book a free trial lesson and discover the joy of making music with expert guidance.', 'wicm-developer' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-outline btn-lg"><?php esc_html_e( 'Get Started Today', 'wicm-developer' ); ?></a>
    </div>
</section>

<!-- CONTACT -->
<section class="contact-section wicm-section" id="contact">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2><?php esc_html_e( 'Contact Us', 'wicm-developer' ); ?></h2>
            <p><?php esc_html_e( "We'd love to hear from you", 'wicm-developer' ); ?></p>
        </div>

        <div class="contact-grid">
            <div class="contact-info fade-in">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php esc_html_e( 'Address', 'wicm-developer' ); ?></h4>
                        <p><?php esc_html_e( 'Pointe-Claire, QC, Canada', 'wicm-developer' ); ?></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php esc_html_e( 'Email', 'wicm-developer' ); ?></h4>
                        <p><a href="mailto:info@westislandmusicschool.com">info@westislandmusicschool.com</a></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php esc_html_e( 'Hours', 'wicm-developer' ); ?></h4>
                        <p><?php esc_html_e( 'Mon-Fri: 9:00 AM - 9:00 PM', 'wicm-developer' ); ?></p>
                        <p><?php esc_html_e( 'Saturday: 9:00 AM - 5:00 PM', 'wicm-developer' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="contact-form fade-in">
                <h3><?php esc_html_e( 'Send Us a Message', 'wicm-developer' ); ?></h3>
                <form>
                    <div class="form-group">
                        <label for="contact-name"><?php esc_html_e( 'Your Name *', 'wicm-developer' ); ?></label>
                        <input type="text" id="contact-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email"><?php esc_html_e( 'Email *', 'wicm-developer' ); ?></label>
                        <input type="email" id="contact-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-phone"><?php esc_html_e( 'Phone', 'wicm-developer' ); ?></label>
                        <input type="tel" id="contact-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="contact-message"><?php esc_html_e( 'Message', 'wicm-developer' ); ?></label>
                        <textarea id="contact-message" name="message" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Send Message', 'wicm-developer' ); ?></button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
