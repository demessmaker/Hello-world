<?php get_header(); ?>

<!-- HERO -->
<section class="hero-section" id="hero">
    <div class="hero-content">
        <h1 class="fade-in">Making Music Magical</h1>
        <p class="hero-subtitle fade-in">Music lessons for all ages in the West Island</p>
        <div class="hero-buttons fade-in">
            <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-primary btn-lg">Book Your Free Trial Lesson</a>
            <a href="#programs" class="btn btn-outline btn-lg">Discover Our Programs</a>
        </div>
    </div>
</section>

<!-- PROGRAMS -->
<section class="programs-section wicm-section" id="programs">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2>Our Programs</h2>
            <p>Discover your musical journey with our expert instructors</p>
        </div>

        <div class="programs-grid">
            <?php
            $programs = array(
                array(
                    'icon'     => '🎹',
                    'name'     => 'Piano',
                    'image'    => 'https://images.unsplash.com/photo-1552422535-c45813c61732?w=800&h=500&fit=crop',
                    'desc'     => 'From classical to contemporary, learn piano at your own pace with personalized instruction for all skill levels.',
                    'features' => array( 'Classical & Contemporary', 'Music Theory', 'Sight Reading', 'Performance Skills' ),
                ),
                array(
                    'icon'     => '🎸',
                    'name'     => 'Guitar',
                    'image'    => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=800&h=500&fit=crop',
                    'desc'     => 'Electric, acoustic, or classical - master the guitar with customized lessons tailored to your musical goals.',
                    'features' => array( 'Electric & Acoustic', 'Chord Progressions', 'Fingerpicking', 'Song Writing' ),
                ),
                array(
                    'icon'     => '🥁',
                    'name'     => 'Drums',
                    'image'    => 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=800&h=500&fit=crop',
                    'desc'     => 'Build rhythm, coordination, and technique with dynamic drum lessons that get you playing your favorite songs.',
                    'features' => array( 'Rhythm Fundamentals', 'Hand Coordination', 'Multiple Styles', 'Band Integration' ),
                ),
                array(
                    'icon'     => '🎤',
                    'name'     => 'Voice',
                    'image'    => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800&h=500&fit=crop',
                    'desc'     => 'Unlock your vocal potential with training in breath control, range expansion, and performance techniques.',
                    'features' => array( 'Breath Control', 'Range Expansion', 'Performance Coaching', 'Multiple Genres' ),
                ),
                array(
                    'icon'     => '🎻',
                    'name'     => 'Violin',
                    'image'    => 'https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?w=800&h=500&fit=crop',
                    'desc'     => 'Embrace the elegance of string music with patient, step-by-step violin instruction for beginners to advanced.',
                    'features' => array( 'Classical Training', 'Proper Technique', 'Orchestra Prep', 'Solo Performance' ),
                ),
            );

            foreach ( $programs as $program ) :
            ?>
            <div class="program-card fade-in">
                <img src="<?php echo esc_url( $program['image'] ); ?>" alt="<?php echo esc_attr( $program['name'] ); ?> lessons at West Island Music School" class="program-card-image" loading="lazy" width="800" height="500">
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
                    <a href="<?php echo esc_url( home_url( '/programs/' ) ); ?>" class="btn btn-outline-dark btn-sm">Learn More</a>
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
            <h2>About West Island Music</h2>
            <p>Your one-stop destination for quality music education</p>
        </div>

        <div class="about-grid">
            <div class="about-card fade-in">
                <div class="about-card-icon">📖</div>
                <h3>Our Story</h3>
                <p>For over two decades, West Island Music has been nurturing musical talent in the Montreal community. What started as a small studio has grown into a comprehensive music education center, serving students of all ages and skill levels.</p>
            </div>
            <div class="about-card fade-in">
                <div class="about-card-icon">🎯</div>
                <h3>Our Mission</h3>
                <p>We believe everyone has musical potential waiting to be discovered. Our mission is to provide personalized, high-quality music education in a warm, encouraging environment where students can explore their creativity and develop lifelong skills.</p>
            </div>
            <div class="about-card fade-in">
                <div class="about-card-icon">🏢</div>
                <h3>Our Facilities</h3>
                <p>Our modern studios feature professional-grade equipment, soundproofed rooms, and advanced air purification systems to ensure a comfortable learning environment.</p>
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
                <div class="stat-label">Years Experience</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">500+</div>
                <div class="stat-label">Students Taught</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">15+</div>
                <div class="stat-label">Instruments Offered</div>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="why-section wicm-section">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2>Why Choose Us</h2>
            <p>What makes West Island Music School the right choice for your musical journey</p>
        </div>

        <div class="why-grid">
            <div class="why-card fade-in">
                <div class="why-card-icon">🎓</div>
                <h3>Expert Teachers</h3>
                <p>Learn from conservatory-trained teachers with years of performance and teaching experience.</p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">🎯</div>
                <h3>Personalized Curriculum</h3>
                <p>Lessons tailored to your goals, whether classical, jazz, pop, or music theory.</p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">👨‍👩‍👧‍👦</div>
                <h3>All Ages Welcome</h3>
                <p>Programs for children as young as 4, teens, adults, and seniors. Beginners to advanced.</p>
            </div>
            <div class="why-card fade-in">
                <div class="why-card-icon">📅</div>
                <h3>Flexible Scheduling</h3>
                <p>Convenient lesson times including evenings and weekends to fit your busy schedule.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section wicm-section" id="testimonials">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2>What Our Students Say</h2>
            <p>Join hundreds of satisfied families</p>
        </div>

        <div class="testimonials-grid">
            <?php
            $testimonials = array(
                array(
                    'quote' => 'The warm environment and supportive teachers helped me build confidence I never knew I had. My vocal range has expanded tremendously!',
                    'name'  => 'Amanda Walsh',
                    'role'  => 'Voice Student',
                ),
                array(
                    'quote' => "Top notch instruction quality. We've been customers for 15 years and both my children have flourished under their guidance.",
                    'name'  => 'Donna Burgess',
                    'role'  => 'Parent',
                ),
                array(
                    'quote' => 'The skills and lasting joy my daughter has developed through her piano lessons here are priceless. Highly recommended!',
                    'name'  => 'Damien Holtz',
                    'role'  => 'Parent',
                ),
                array(
                    'quote' => 'My son was selected for the Montreal Jazz Festival Blues Camp thanks to the exceptional training he received here.',
                    'name'  => 'John McGuinness',
                    'role'  => 'Parent',
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
        <h2>Ready to Start Your Musical Journey?</h2>
        <p>Book a free trial lesson and discover the joy of making music with expert guidance.</p>
        <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-outline btn-lg">Get Started Today</a>
    </div>
</section>

<!-- CONTACT -->
<section class="contact-section wicm-section" id="contact">
    <div class="wicm-container">
        <div class="wicm-section-header fade-in">
            <h2>Contact Us</h2>
            <p>We'd love to hear from you</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info fade-in">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4>Address</h4>
                        <p>Pointe-Claire, QC, Canada</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4>Email</h4>
                        <p><a href="mailto:info@westislandmusicschool.com">info@westislandmusicschool.com</a></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4>Hours</h4>
                        <p>Mon-Fri: 9:00 AM - 9:00 PM</p>
                        <p>Saturday: 9:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="contact-form fade-in">
                <h3>Send Us a Message</h3>
                <form>
                    <div class="form-group">
                        <label for="contact-name">Your Name *</label>
                        <input type="text" id="contact-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email *</label>
                        <input type="email" id="contact-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-phone">Phone</label>
                        <input type="tel" id="contact-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message</label>
                        <textarea id="contact-message" name="message" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
