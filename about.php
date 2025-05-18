<?php
$pageTitle = "CineBook - About Us";

// Include header (which handles authentication and includes the navbars and side menu)
include 'includes/header.php';

?>

<link rel="stylesheet" href="css/about.css">

<!-- About Section -->
<section id="our-story" class="about-section">
    <div class="section-header">
        <h2>Our Story</h2>
        <div class="section-divider"></div>
    </div>
    
    <div class="about-intro">
        <p>Founded in 2010, CineBook began with a simple mission: to create cinema experiences that go beyond just watching a movie. We believe that great films deserve to be experienced in environments designed with attention to every detail - from the moment you book your ticket to the final credits.</p>
        <p>Today, CineBook operates premium cinemas across 25 locations nationwide, serving over 3 million moviegoers annually. Our commitment to innovation, comfort, and superior audiovisual technology has made us the preferred choice for film enthusiasts and casual viewers alike.</p>
    </div>

    <div class="about-grid">
        <div class="about-card">
            <div class="about-card-img">
                <i class="fas fa-film"></i>
            </div>
            <div class="about-card-content">
                <h3>Premium Experience</h3>
                <p>Our theaters feature the latest 4K laser projection technology, immersive sound systems, and carefully designed viewing environments to ensure the perfect cinematic experience for every film.</p>
            </div>
        </div>

        <div class="about-card">
            <div class="about-card-img">
                <i class="fas fa-chair"></i>
            </div>
            <div class="about-card-content">
                <h3>Luxury Seating</h3>
                <p>Sink into our premium reclining seats with ample legroom, adjustable headrests, and convenient side tables. Every seat is designed to provide optimal comfort and the perfect viewing angle.</p>
            </div>
        </div>

        <div class="about-card">
            <div class="about-card-img">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <div class="about-card-content">
                <h3>Exceptional Service</h3>
                <p>Our staff is trained to provide attentive, personalized service from ticket purchase to post-movie departure. We believe the human touch makes all the difference in creating memorable experiences.</p>
            </div>
        </div>
    </div>
</section>

<!-- Timeline Section -->
<section class="timeline-section">
    <div class="section-header light">
        <h2>Our Journey</h2>
        <div class="section-divider"></div>
    </div>
    
    <div class="timeline">
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>2010</h3>
                <p>Founded with our first cinema location featuring just 4 screens.</p>
            </div>
        </div>
        
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>2013</h3>
                <p>Expanded to 5 locations and introduced our first premium auditorium with recliner seating.</p>
            </div>
        </div>
        
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>2016</h3>
                <p>Launched online reservation system and mobile app for a seamless booking experience.</p>
            </div>
        </div>
        
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>2019</h3>
                <p>Introduced Dolby Atmos sound systems and 4K laser projection across all locations.</p>
            </div>
        </div>
        
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>2022</h3>
                <p>Reached 25 locations nationwide and launched our exclusive membership program.</p>
            </div>
        </div>
        
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <h3>Today</h3>
                <p>Continuing to innovate with enhanced digital experiences and sustainable operations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stats-container">
        <div class="stat-item">
            <div class="stat-number">25+</div>
            <div class="stat-label">Locations</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number">150+</div>
            <div class="stat-label">Screens</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number">3M+</div>
            <div class="stat-label">Annual Guests</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number">500+</div>
            <div class="stat-label">Films Shown Yearly</div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="team-container">
        <div class="section-header">
            <h2>Our Leadership Team</h2>
            <div class="section-divider"></div>
        </div>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="team-member-img">
                    <img src="images/team/team1.jpg" alt="Adlerson Furio" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='CEO';">
                    <div class="team-overlay">
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="team-member-info">
                    <h3>Adlerson Furio</h3>
                    <p>Chief Executive Officer</p>
                    <div class="team-bio">
                        <p>With over 15 years of experience in entertainment and hospitality, Adlerson leads our vision to transform cinema experiences nationwide.</p>
                    </div>
                </div>
            </div>

            <div class="team-member">
                <div class="team-member-img">
                    <img src="images/team/team2.jpg" alt="Mark Andrei Florendo" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='COO';">
                    <div class="team-overlay">
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="team-member-info">
                    <h3>Mark Andrei Florendo</h3>
                    <p>Chief Operations Officer</p>
                    <div class="team-bio">
                        <p>Mark oversees day-to-day operations across all locations, ensuring consistent quality and exceptional customer experiences.</p>
                    </div>
                </div>
            </div>

            <div class="team-member">
                <div class="team-member-img">
                    <img src="images/team/team3.jpg" alt="John Paul Amora" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='Programming';">
                    <div class="team-overlay">
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="team-member-info">
                    <h3>John Paul Amora</h3>
                    <p>Head of Programming</p>
                    <div class="team-bio">
                        <p>John curates our film selection, balancing blockbusters with independent and international cinema to provide diverse viewing options.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonial-section">
    <div class="section-header light">
        <h2>What Our Guests Say</h2>
        <div class="section-divider"></div>
    </div>
    
    <div class="testimonial-container">
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"CineBook has completely changed how I experience movies. The comfort of the seating and the quality of the sound and picture are unmatched."</p>
            </div>
            <div class="testimonial-author">
                <div class="testimonial-avatar">
                    <img src="images/testimonials/avatar1.jpg" alt="Sarah" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='S';">
                </div>
                <div class="testimonial-info">
                    <h4>Sarah L.</h4>
                    <p>Loyal Member Since 2018</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"The online booking system is incredibly easy to use, and I love being able to select my exact seat. The staff is always friendly and helpful."</p>
            </div>
            <div class="testimonial-author">
                <div class="testimonial-avatar">
                    <img src="images/testimonials/avatar2.jpg" alt="Michael" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='M';">
                </div>
                <div class="testimonial-info">
                    <h4>Michael T.</h4>
                    <p>Regular Customer</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"From blockbusters to indie films, CineBook always offers an amazing selection. I appreciate the care they put into curating diverse films."</p>
            </div>
            <div class="testimonial-author">
                <div class="testimonial-avatar">
                    <img src="images/testimonials/avatar3.jpg" alt="Elena" onerror="this.src=''; this.onerror=null; this.style.backgroundColor='#e50914'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontWeight='bold'; this.style.height='100%'; this.innerHTML='E';">
                </div>
                <div class="testimonial-info">
                    <h4>Elena R.</h4>
                    <p>Film Enthusiast</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>