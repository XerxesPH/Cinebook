document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let currentSlide = 0;
    let movieCurrentSlide = 0;
    
    // Hero Carousel
    const heroCarousel = document.querySelector('.hero-carousel .carousel-container');
    const heroItems = document.querySelectorAll('.hero-carousel .carousel-item');
    const heroPrevBtn = document.querySelector('.hero-carousel .prev');
    const heroNextBtn = document.querySelector('.hero-carousel .next');
    
    // Movies Carousel
    const moviesCarousel = document.querySelector('.movies-carousel .movies-container');
    const movieItems = document.querySelectorAll('.movie-card');
    const moviePrevBtn = document.querySelector('.movies-carousel .prev');
    const movieNextBtn = document.querySelector('.movies-carousel .next');
    
    // Book Now Buttons
    const bookNowButtons = document.querySelectorAll('.book-now-btn');
    
    // Hero Carousel Navigation
    if (heroItems.length > 0) {
        // Set initial slide
        updateHeroCarousel();
        
        // Auto slide every 5 seconds
        setInterval(function() {
            nextHeroSlide();
        }, 5000);
        
        // Manual navigation
        heroPrevBtn?.addEventListener('click', prevHeroSlide);
        heroNextBtn?.addEventListener('click', nextHeroSlide);
    }
    
    // Movies Carousel Navigation
    if (movieItems.length > 0) {
        // Manual navigation
        moviePrevBtn?.addEventListener('click', prevMovieSlide);
        movieNextBtn?.addEventListener('click', nextMovieSlide);
    }
    
    // Close modals when clicking on close button or outside the modal
    const closeButtons = document.querySelectorAll('.modal .close-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Open seat selection modal for book now buttons
    bookNowButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the movie title
            const movieTitle = this.closest('.carousel-item').querySelector('h3').textContent;
            
            // Display movie info and proceed to seat selection
            if (typeof window.displaySeatSelection === 'function') {
                window.displaySeatSelection(movieTitle);
                document.getElementById('seatModal').style.display = 'block';
                
                if (typeof window.resetSelectedSeats === 'function') {
                    window.resetSelectedSeats();
                } else {
                    console.error('resetSelectedSeats function is not available');
                }
            } else {
                console.error('displaySeatSelection function is not available');
                alert('Seat selection is not available at the moment. Please try again later.');
            }
        });
    });
    
    // Movie info buttons
    const infoButtons = document.querySelectorAll('.info-btn');
    infoButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.closest('.movie-card').getAttribute('data-movie-id');
            
            // Display movie info modal
            if (typeof window.showMovieInfo === 'function') {
                window.showMovieInfo(movieId);
            } else {
                console.error('showMovieInfo function is not available');
                alert('Movie information is not available at the moment. Please try again later.');
            }
        });
    });
    
    // Hero Carousel Functions
    function updateHeroCarousel() {
        heroCarousel.style.transform = `translateX(-${currentSlide * 100}%)`;
    }
    
    function nextHeroSlide() {
        currentSlide = (currentSlide + 1) % heroItems.length;
        updateHeroCarousel();
    }
    
    function prevHeroSlide() {
        currentSlide = (currentSlide - 1 + heroItems.length) % heroItems.length;
        updateHeroCarousel();
    }
    
    // Movies Carousel Functions
    function updateMoviesCarousel() {
        const itemWidth = movieItems[0]?.offsetWidth + 20 || 0; // Width + gap
        if (itemWidth) {
            moviesCarousel.style.transform = `translateX(-${movieCurrentSlide * itemWidth}px)`;
        }
    }
    
    function nextMovieSlide() {
        const maxSlide = Math.max(0, movieItems.length - getVisibleMovies());
        movieCurrentSlide = Math.min(movieCurrentSlide + 1, maxSlide);
        updateMoviesCarousel();
    }
    
    function prevMovieSlide() {
        movieCurrentSlide = Math.max(0, movieCurrentSlide - 1);
        updateMoviesCarousel();
    }
    
    function getVisibleMovies() {
        const containerWidth = moviesCarousel?.parentElement?.offsetWidth || 0;
        const itemWidth = movieItems[0]?.offsetWidth + 20 || 0; // Width + gap
        return Math.floor(containerWidth / itemWidth) || 1;
    }
    
    // Responsive adjustment when window resizes
    window.addEventListener('resize', updateMoviesCarousel);
});