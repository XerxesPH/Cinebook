document.addEventListener("DOMContentLoaded", function () {
  // Make the showMovieInfo function global so it can be accessed from other scripts
  window.showMovieInfo = displayMovieInfo;

  // Movie Info Modal Elements
  const movieModal = document.getElementById("movieModal");
  const closeMovieBtn = movieModal.querySelector(".close-btn");
  const movieInfoButtons = document.querySelectorAll(".info-btn");
  const movieCards = document.querySelectorAll(".movie-card");

  // New booking elements
  const bookNowBtn = document.getElementById("bookNowBtn");
  const bookingCinema = document.getElementById("bookingCinema");
  const bookingDate = document.getElementById("bookingDate");
  const bookingTime = document.getElementById("bookingTime");
  const selectedCinemaName = document.getElementById("selectedCinemaName");
  

  // Trailer element
  const trailerContainer = document.getElementById("trailerContainer");

  // Booking data object
  let bookingData = {
    movieId: null,
    cinemaId: null,
    cinemaName: null,
    date: null,
    time: null,
    screeningId: null,
  };

  // Base URL for API calls - adjust if needed
  // This is a more robust way to determine the base URL
  const baseURL = getBaseURL();

  // Function to determine base URL
  function getBaseURL() {
    const pathParts = window.location.pathname.split("/");
    const projectFolder = pathParts.find(
      (part) => part === "Cinema_Reservation"
    );

    if (projectFolder) {
      // If we're in a '/Cinema_Reservation/' folder structure
      return "/" + projectFolder;
    } else {
      // We might be at the root level
      return "";
    }
  }

  // Close movie modal when clicking on close button or outside the modal
  closeMovieBtn.addEventListener("click", function () {
    movieModal.style.display = "none";
    resetBookingSelections();
  });

  window.addEventListener("click", function (event) {
    if (event.target === movieModal) {
      movieModal.style.display = "none";
      resetBookingSelections();
    }
  });

  // Open movie info modal when clicking on a movie card
  movieCards.forEach((card) => {
    card.addEventListener("click", function () {
      // Get movie info from data attributes
      const movieId = this.getAttribute("data-movie-id");
      const movieTitle = this.getAttribute("data-movie-title") || null;

      if (movieId) {
        // If we have the title, we can use it to ensure all cinemas showing this title are included
        displayMovieInfo(movieId, movieTitle);
        movieModal.style.display = "block";
      }
    });
  });

  // Open movie info modal when clicking on "More Info" button
  movieInfoButtons.forEach((button) => {
    button.addEventListener('click', function () {
        // Find the parent movie card
        const movieCard = this.closest('.movie-card');
        
        // Get movie info from data attributes of the parent movie card
        const movieId = movieCard.getAttribute('data-movie-id');
        const movieTitle = movieCard.getAttribute('data-movie-title') || null;

        if (movieId) {
            displayMovieInfo(movieId, movieTitle);
            movieModal.style.display = 'block';
        }
    });
});

  // Book Now buttons in hero carousel
  const heroBookButtons = document.querySelectorAll(
    ".hero-carousel .book-now-btn"
  );
  heroBookButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const movieSlide = this.closest(".carousel-item");
      const movieIndex = Array.from(movieSlide.parentNode.children).indexOf(
        movieSlide
      );
      // Get movie ID from the corresponding movie in the movies array
      const movieItems = document.querySelectorAll(".movie-card");
      if (movieIndex < movieItems.length) {
        const movieCard = movieItems[movieIndex];
        const movieId = movieCard.getAttribute("data-movie-id");
        const movieTitle = movieCard.getAttribute("data-movie-title") || null;

        if (movieId) {
          displayMovieInfo(movieId, movieTitle);
          movieModal.style.display = "block";
        }
      }
    });
  });

  // Reset booking selections
  function resetBookingSelections() {
    bookingData = {
      movieId: null,
      cinemaId: null,
      cinemaName: null,
      date: null,
      time: null,
      screeningId: null
    };

    // Reset UI elements
    bookingCinema.textContent = "Not selected";
    bookingDate.textContent = "Not selected";
    bookingTime.textContent = "Not selected";
    selectedCinemaName.textContent = "None selected";

    // Disable book now button
    bookNowBtn.disabled = true;
    bookNowBtn.textContent = "Select a date & time to book";

    // Reset any selected dates/times
    const selectedDates = document.querySelectorAll(".date-item.selected");
    selectedDates.forEach((date) => date.classList.remove("selected"));

    const selectedTimes = document.querySelectorAll(".time-item.selected");
    selectedTimes.forEach((time) => time.classList.remove("selected"));

    // Reset trailer container
    trailerContainer.innerHTML = "";
  }

  // Update booking summary
  function updateBookingSummary() {
    if (bookingData.cinemaName) {
      bookingCinema.textContent = bookingData.cinemaName;
      selectedCinemaName.textContent = bookingData.cinemaName;
    } else {
      bookingCinema.textContent = "Not selected";
      selectedCinemaName.textContent = "None selected";
    }

    if (bookingData.date) {
      bookingDate.textContent = formatDate(bookingData.date);
    } else {
      bookingDate.textContent = "Not selected";
    }

    if (bookingData.time) {
      bookingTime.textContent = bookingData.time;
    } else {
      bookingTime.textContent = "Not selected";
    }

    // Don't enable button if it's a coming soon movie
    if (bookNowBtn.classList.contains("coming-soon")) {
      // Keep it disabled for coming soon movies
      return;
    }

    // Enable/disable book now button
    const canProceed = bookingData.cinemaId && bookingData.date && bookingData.time && bookingData.screeningId;
    console.log("Booking data validation:", {
      cinemaId: !!bookingData.cinemaId,
      date: !!bookingData.date,
      time: !!bookingData.time,
      screeningId: !!bookingData.screeningId,
      canProceed: canProceed
    });

    if (canProceed) {
      bookNowBtn.disabled = false;
      bookNowBtn.textContent = "Book Now";
    } else {
      bookNowBtn.disabled = true;
      bookNowBtn.textContent = "Select a date & time to book";
    }
  }

  // Add this function to check if a time slot should be disabled
  function shouldDisableTime(timeStr, date, movieDuration) {
    console.log("Checking if time should be disabled:", timeStr, date);
    // Default movie duration to 120 minutes if not provided
    movieDuration = movieDuration || 120;
    
    // Get current date and time
    const now = new Date();
    
    // Create a date object for the movie showtime
    let hours = 0;
    let minutes = 0;
    
    // Handle different time formats (10:00 AM, 10:00, etc.)
    if (timeStr.includes(':')) {
      // Check if it's AM/PM format
      if (timeStr.includes('AM') || timeStr.includes('PM')) {
        const timeParts = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (timeParts) {
          hours = parseInt(timeParts[1]);
          minutes = parseInt(timeParts[2]);
          const period = timeParts[3].toUpperCase();
          
          // Convert to 24-hour format
          if (period === 'PM' && hours < 12) {
            hours += 12;
          } else if (period === 'AM' && hours === 12) {
            hours = 0;
          }
        }
      } else {
        // Handle 24-hour format
        const timeParts = timeStr.split(':');
        hours = parseInt(timeParts[0]);
        minutes = parseInt(timeParts[1]);
      }
    }
    
    const showDateTime = new Date(date);
    showDateTime.setHours(hours, minutes, 0, 0);
    
    // Calculate movie end time (add duration to start time)
    const movieEndTime = new Date(showDateTime);
    movieEndTime.setMinutes(movieEndTime.getMinutes() + movieDuration);
    
    // Check if date is in the past (before today)
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to start of day
    
    const showDate = new Date(date);
    showDate.setHours(0, 0, 0, 0); // Reset time to start of day
    
    // If the date is in the past, disable
    if (showDate < today) {
      console.log(`Date ${date} is in the past`);
      return true;
    }
    
    // If date is today, check if current time is past the showtime
    if (showDate.getTime() === today.getTime()) {
      // Simply check if the showtime has already passed (current time is later than showtime)
      const isPastShowtime = now > showDateTime;
      
      console.log(`Time slot: ${timeStr} on ${date}`);
      console.log(`Start time: ${showDateTime.toLocaleTimeString()}`);
      console.log(`Current time: ${now.toLocaleTimeString()}`);
      console.log(`Is past showtime: ${isPastShowtime}`);
      
      return isPastShowtime;
    }
    
    return false;
  }

  // Display Movie Info in Modal
  function displayMovieInfo(movieId, movieTitle = null) {
    // Reset booking selections
    resetBookingSelections();

    // Save movieId to booking data
    bookingData.movieId = movieId;

    // Show loading state
    document.getElementById("movieTitle").textContent = "Loading...";
    document.getElementById("movieSynopsis").textContent =
      "Loading movie details...";
    document.getElementById("moviePoster").src =
      baseURL + "/images/default-movie.jpg";
    document.getElementById("movieCinemas").innerHTML = "<li>Loading...</li>";
    document.getElementById("movieDates").innerHTML =
      '<div class="date-item">Loading...</div>';

    console.log(
      "Fetching movie details for ID:",
      movieId,
      "Title:",
      movieTitle
    );

    // Build the URL with both ID and title (if available)
    // Fix: Ensure we're using the correct path to the get_movie.php script
    let fetchUrl = `${baseURL}/movie_details/get_movie.php?id=${movieId}`;
    if (movieTitle) {
      fetchUrl += `&title=${encodeURIComponent(movieTitle)}`;
    }

    console.log("Fetch URL:", fetchUrl); // Debug log

    // Get movie data via AJAX
    fetch(fetchUrl)
      .then((response) => {
        console.log("Response status:", response.status); // Debug log

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then((movie) => {
        console.log("Movie data received:", movie); // Debug log
        
        // Check the structure of show_dates
        if (movie.show_dates) {
          console.log("Show dates format example:", movie.show_dates[0]);
        }

        // Populate modal with movie details
        document.getElementById("movieTitle").textContent =
          movie.title || "Title Not Available";

        // Set poster image with error handling
        const posterImg = document.getElementById("moviePoster");
        posterImg.onerror = function () {
          this.src = baseURL + "/images/default-movie.jpg";
          this.onerror = null;
        };

        // Handle image path correctly
        let imagePath = movie.image || "/images/default-movie.jpg";

        // Make sure image path is correct regardless of base URL
        if (
          imagePath &&
          !imagePath.startsWith("http") &&
          !imagePath.startsWith(baseURL)
        ) {
          imagePath = baseURL + imagePath;
        }

        posterImg.src = imagePath;
        posterImg.alt = movie.title || "Movie Poster";

        document.getElementById("movieSynopsis").textContent =
          movie.synopsis || "No synopsis available.";

        // Check if the movie is coming soon based on its show dates
        let isComingSoon = false;
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time to start of day
        
        // First check the page context
        const currentUrl = window.location.href;
        const isNowShowingPage = currentUrl.includes('category=now-showing');
        const isComingSoonPage = currentUrl.includes('category=coming-soon');
        const isAllMoviesPage = currentUrl.includes('movies.php') && !currentUrl.includes('category=');
        const isIndexPage = currentUrl.endsWith('index.php') || currentUrl.endsWith('/');
        
        console.log("Page context:", {
          url: currentUrl,
          isNowShowingPage: isNowShowingPage,
          isComingSoonPage: isComingSoonPage,
          isAllMoviesPage: isAllMoviesPage,
          isIndexPage: isIndexPage
        });
        
        // Determine if the movie is coming soon based on context and dates
        if (isNowShowingPage || isIndexPage) {
          // On now showing page or index page, never mark as coming soon
          isComingSoon = false;
        } else if (isComingSoonPage) {
          // On coming soon page, always mark as coming soon
          isComingSoon = true;
        } else {
          // On other pages (all movies or unknown), check movie dates
          
          // First check explicit status if available
          if (movie.status === 'coming_soon' || movie.status === 'Coming Soon') {
            isComingSoon = true;
          }
          // Then check start/end dates if available
          else if (movie.start_showing && movie.end_showing) {
            const startDate = new Date(movie.start_showing);
            isComingSoon = startDate > today;
            
            console.log("Movie date check:", {
              title: movie.title,
              startDate: startDate.toISOString(),
              today: today.toISOString(),
              isComingSoon: isComingSoon
            });
          }
          // Finally check individual show dates if available
          else if (movie.show_dates && movie.show_dates.length > 0) {
            // Count how many dates are in the future vs. current
            let futureDates = 0;
            let currentDates = 0;
            
            movie.show_dates.forEach(dateObj => {
              const showDate = new Date(dateObj.date);
              if (showDate >= today) {
                futureDates++;
                if (showDate.getTime() === today.getTime()) {
                  currentDates++;
                }
              }
            });
            
            // If ALL dates are in the future and none are today, it's coming soon
            isComingSoon = futureDates > 0 && futureDates === movie.show_dates.length && currentDates === 0;
            
            console.log("Show dates check:", {
              title: movie.title, 
              totalDates: movie.show_dates.length,
              futureDates: futureDates,
              currentDates: currentDates,
              isComingSoon: isComingSoon
            });
          } else {
            // Default to not coming soon if we can't determine
            isComingSoon = false;
          }
        }
        
        // Clean up any previous coming soon elements
        const existingLabel = document.querySelector('.coming-soon-label');
        if (existingLabel) {
          existingLabel.remove();
        }
        
        // Update UI based on coming soon status
        if (isComingSoon) {
          // Disable booking for coming soon movies
          bookNowBtn.disabled = true;
          bookNowBtn.textContent = "Coming Soon - Cannot Book Yet";
          bookNowBtn.classList.add("coming-soon");
          
          // Add a coming soon banner or label
          const movieInfoContainer = document.querySelector('.movie-info-container');
          if (movieInfoContainer) {
            const comingSoonLabel = document.createElement('div');
            comingSoonLabel.className = 'coming-soon-label';
            comingSoonLabel.textContent = 'COMING SOON';
            movieInfoContainer.prepend(comingSoonLabel);
          }
        } else {
          // Remove coming soon styling if it exists
          bookNowBtn.classList.remove("coming-soon");
        }

        // Function to create a YouTube embed URL
        function createYouTubeEmbedUrl(trailerUrl) {
          // Check if it's already an embed URL
          if (trailerUrl.includes("youtube.com/embed/")) {
            return trailerUrl;
          }

          // Extract video ID from different YouTube URL formats
          let videoId = null;

          // Check for standard YouTube watch URL
          const watchMatch = trailerUrl.match(
            /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/
          );

          if (watchMatch && watchMatch[1]) {
            videoId = watchMatch[1];
          }

          // If we found a valid video ID, return the embed URL
          if (videoId) {
            return `https://www.youtube.com/embed/${videoId}?rel=0`;
          }

          // If no valid ID found, return null or original URL
          return null;
        }

        // Modify the trailer embedding code in displayMovieInfo function
        if (movie.trailer_url) {
    // Clear previous trailers
    trailerContainer.innerHTML = '';

    const embedUrl = createYouTubeEmbedUrl(movie.trailer_url);

    if (embedUrl) {
        const trailerIframe = document.createElement("iframe");
        trailerIframe.width = "100%";
        trailerIframe.height = "315";
        trailerIframe.src = embedUrl;
        trailerIframe.title = "Movie Trailer";
        trailerIframe.frameBorder = "0";
        trailerIframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
        trailerIframe.allowFullscreen = true;
        trailerContainer.appendChild(trailerIframe);
    } else {
        // Fallback if URL can't be converted
        const trailerLink = document.createElement("a");
        trailerLink.href = movie.trailer_url;
        trailerLink.textContent = "Watch Trailer";
        trailerLink.target = "_blank";
        trailerContainer.appendChild(trailerLink);
    }
}

        // Display cinemas where the movie is showing
        const cinemasList = document.getElementById("movieCinemas");
        cinemasList.innerHTML = "";

        if (movie.cinemas && movie.cinemas.length > 0) {
          movie.cinemas.forEach((cinema) => {
            const cinemaItem = document.createElement("li");
            cinemaItem.textContent = cinema.name;
            cinemaItem.setAttribute("data-cinema-id", cinema.id);
            cinemaItem.setAttribute("data-cinema-name", cinema.name);
            cinemaItem.addEventListener("click", function () {
              // Set cinema for booking
              bookingData.cinemaId = cinema.id;
              bookingData.cinemaName = cinema.name;

              // Update UI
              document.querySelectorAll("#movieCinemas li").forEach((li) => {
                li.classList.remove("selected");
              });
              this.classList.add("selected");

              // Update booking summary
              updateBookingSummary();
            });
            cinemasList.appendChild(cinemaItem);
          });

          // Auto-select first cinema if available
          if (movie.cinemas.length > 0) {
            const firstCinema = movie.cinemas[0];
            bookingData.cinemaId = firstCinema.id;
            bookingData.cinemaName = firstCinema.name;
            cinemasList.querySelector("li").classList.add("selected");
            updateBookingSummary();
          }
        } else {
          const noCinemaItem = document.createElement("li");
          noCinemaItem.textContent = "Not currently showing in any cinema";
          cinemasList.appendChild(noCinemaItem);
        }

        // Display show dates
        const datesSlider = document.getElementById("movieDates");
        datesSlider.innerHTML = "";

        if (isComingSoon) {
          // For coming soon movies, show when it will be available
          const comingSoonInfo = document.createElement("div");
          comingSoonInfo.className = "coming-soon-info";
          
          // Format the release date if available
          let releaseMessage = "This movie is coming soon!";
          if (movie.start_showing) {
            const releaseDate = new Date(movie.start_showing);
            const formattedDate = releaseDate.toLocaleDateString("en-US", {
              weekday: "long",
              month: "long",
              day: "numeric"
            });
            releaseMessage = `This movie will be released on ${formattedDate}`;
          }
          
          comingSoonInfo.innerHTML = `
            <div class="coming-soon-icon"><i class="fas fa-clock"></i></div>
            <h3>This movie is coming soon!</h3>
            <p>${releaseMessage}</p>
            <p class="coming-soon-note">Bookings will be available closer to the release date.</p>
          `;
          
          datesSlider.appendChild(comingSoonInfo);
        }
        else if (movie.show_dates && movie.show_dates.length > 0) {
          // Group dates by cinema
          const cinemaDateMap = {};
          movie.show_dates.forEach((showDate) => {
            if (!cinemaDateMap[showDate.cinema_id]) {
              cinemaDateMap[showDate.cinema_id] = [];
            }
            cinemaDateMap[showDate.cinema_id].push(showDate);
          });

          // Filter dates for the selected cinema or first cinema
          const selectedCinemaId = bookingData.cinemaId || movie.cinemas[0].id;
          const relevantDates = cinemaDateMap[selectedCinemaId] || [];

          relevantDates.forEach((date) => {
            const dateItem = document.createElement("div");
            dateItem.className = "date-item";
            dateItem.setAttribute("data-date", date.date);
            
            // Check if date is in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const showDate = new Date(date.date);
            showDate.setHours(0, 0, 0, 0);
            
            const isPastDate = showDate < today;
            
            if (isPastDate) {
              dateItem.classList.add("past-date");
              dateItem.title = "This date has already passed";
            }

            // Add click handler for date selection
            dateItem.addEventListener("click", function () {
              // Don't allow selecting past dates
              if (isPastDate) {
                return;
              }
              
              // Reset previous date selection
              document.querySelectorAll(".date-item").forEach((item) => {
                item.classList.remove("selected");
              });

              // Select this date
              this.classList.add("selected");

              // Set date in booking data
              bookingData.date = date.date;

              // Update booking summary
              updateBookingSummary();
            });

            const dateSpan = document.createElement("span");
            dateSpan.className = "date";
            dateSpan.textContent = formatDate(date.date);
            dateItem.appendChild(dateSpan);

            const timesDiv = document.createElement("div");
            timesDiv.className = "times";

            // Check if we have the new format with times_with_ids
            if (date.times_with_ids) {
              // Use the new format with screening IDs
              date.times_with_ids.forEach((timeData) => {
                const timeItem = document.createElement("span");
                timeItem.className = "time-item";
                timeItem.textContent = timeData.time;
                timeItem.setAttribute("data-time", timeData.time);
                timeItem.setAttribute("data-screening-id", timeData.schedule_id);
                
                // Check if this time should be disabled
                const disabled = shouldDisableTime(timeData.time, date.date, movie.duration || 120);
                
                if (disabled) {
                  timeItem.classList.add("disabled");
                  timeItem.title = "This showtime is no longer available";
                  // Disable the click handler by setting a different one that does nothing
                  timeItem.addEventListener("click", function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                  });
                } else {
                  // Add click handler for time selection
                  timeItem.addEventListener("click", function (e) {
                    e.stopPropagation(); // Prevent triggering the date click

                    // Reset previous time selection
                    document.querySelectorAll(".time-item").forEach((item) => {
                      item.classList.remove("selected");
                    });

                    // Select this time
                    this.classList.add("selected");

                    // Make sure the parent date is selected
                    if (!this.closest(".date-item").classList.contains("selected")) {
                      document.querySelectorAll(".date-item").forEach((item) => {
                        item.classList.remove("selected");
                      });
                      this.closest(".date-item").classList.add("selected");
                      bookingData.date = this.closest(".date-item").getAttribute("data-date");
                    }

                    // Set time in booking data
                    bookingData.time = timeData.time;
                    
                    // Store the screening ID
                    bookingData.screeningId = timeData.schedule_id;

                    // Update booking summary
                    updateBookingSummary();
                  });
                }

                timesDiv.appendChild(timeItem);
              });
            } else if (date.times) {
              // Fallback for old format without screening IDs
              date.times.forEach((time) => {
                const timeItem = document.createElement("span");
                timeItem.className = "time-item";
                timeItem.textContent = time;
                timeItem.setAttribute("data-time", time);
                
                // Check if this time should be disabled
                const disabled = shouldDisableTime(time, date.date, movie.duration || 120);
                
                if (disabled) {
                  timeItem.classList.add("disabled");
                  timeItem.title = "This showtime is no longer available";
                  // Disable the click handler by setting a different one that does nothing
                  timeItem.addEventListener("click", function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                  });
                } else {
                  // Add click handler for time selection
                  timeItem.addEventListener("click", function (e) {
                    e.stopPropagation();
                    
                    // Reset previous time selection
                    document.querySelectorAll(".time-item").forEach((item) => {
                      item.classList.remove("selected");
                    });

                    // Select this time
                    this.classList.add("selected");

                    // Make sure the parent date is selected
                    if (!this.closest(".date-item").classList.contains("selected")) {
                      document.querySelectorAll(".date-item").forEach((item) => {
                        item.classList.remove("selected");
                      });
                      this.closest(".date-item").classList.add("selected");
                      bookingData.date = this.closest(".date-item").getAttribute("data-date");
                    }

                    // Set time in booking data
                    bookingData.time = time;
                    
                    // For old format, we need to look up the screening ID separately
                    // For now, we'll just set it to null and possibly fetch it later
                    bookingData.screeningId = null;

                    // Update booking summary
                    updateBookingSummary();
                  });
                }

                timesDiv.appendChild(timeItem);
              });
            }

            dateItem.appendChild(timesDiv);
            datesSlider.appendChild(dateItem);
          });
        } else {
          const noDateItem = document.createElement("div");
          noDateItem.className = "date-item";
          noDateItem.textContent = "No show dates available";
          datesSlider.appendChild(noDateItem);
        }

        // Add event listener to Book Now button in modal
        bookNowBtn.onclick = function () {
          console.log("Book Now clicked. Booking data:", bookingData);
          
          if (bookingData.cinemaId && bookingData.date && bookingData.time && bookingData.screeningId) {
            // Close the movie info modal
            movieModal.style.display = "none";

            // Check if user is logged in before proceeding to seat selection
            checkLoginStatus(function (isLoggedIn) {
              if (isLoggedIn) {
                // User is logged in, display seat selection
                if (typeof window.displaySeatSelection === "function") {
                  console.log("Calling displaySeatSelection with:", 
                    bookingData.cinemaId, 
                    bookingData.movieId, 
                    bookingData.date, 
                    bookingData.time, 
                    bookingData.screeningId);
                    
                  window.displaySeatSelection(
                    bookingData.cinemaId,
                    bookingData.movieId,
                    bookingData.date,
                    bookingData.time,
                    bookingData.screeningId
                  );
                  document.getElementById("seatModal").style.display = "block";
                  // Reset selected seats if the function exists
                  if (typeof window.resetSelectedSeats === "function") {
                    window.resetSelectedSeats();
                  }
                } else {
                  console.error("displaySeatSelection function not found");
                  alert("Seat selection is not available at this time.");
                }
              } else {
                // User is not logged in, show login/register modal instead of redirecting
                
                // Store booking intent in session storage
                sessionStorage.setItem('redirect_after_login', 'booking');
                
                // Store booking data in session storage for later use
                const bookingInfo = {
                  movieId: bookingData.movieId,
                  cinemaId: bookingData.cinemaId,
                  date: bookingData.date,
                  time: bookingData.time,
                  screeningId: bookingData.screeningId
                };
                sessionStorage.setItem('booking_data', JSON.stringify(bookingInfo));
                
                // Show the login modal
                if (typeof window.showLoginModal === "function") {
                  window.showLoginModal('login');
                } else {
                  // Fallback to redirect if login modal function is not available
                  window.location.href =
                    baseURL +
                    "/login.php?redirect=" +
                    encodeURIComponent(
                      window.location.pathname + "?movie=" + bookingData.movieId
                    );
                }
              }
            });
          } else {
            console.warn("Cannot proceed, missing required booking data:", {
              cinemaId: bookingData.cinemaId,
              date: bookingData.date,
              time: bookingData.time,
              screeningId: bookingData.screeningId
            });
            alert("Please select a cinema, date, and time to book.");
          }
        };
      })
      .catch((error) => {
        console.error("Error fetching movie details:", error);
        // Display fallback/error message
        document.getElementById("movieTitle").textContent =
          "Movie Information Unavailable";
        document.getElementById("movieSynopsis").textContent =
          "Sorry, we couldn't load the movie details. Please try again later. Error: " +
          error.message;
        document.getElementById("moviePoster").src =
          baseURL + "/images/default-movie.jpg";
        document.getElementById("movieCinemas").innerHTML =
          "<li>Unable to load cinema information</li>";
        document.getElementById("movieDates").innerHTML =
          '<div class="date-item">Unable to load show dates</div>';
      });
  }

  // Helper function to check if user is logged in
  function checkLoginStatus(callback) {
    // First check if login status is available from window object
    if (typeof window.userLoggedIn !== 'undefined') {
      console.log("Login status from window object:", window.userLoggedIn);
      // If logged in, process the callback immediately
      if (window.userLoggedIn === true) {
        callback(true);
        return;
      }
    }

    // If not available in window object or not logged in, fallback to AJAX check
    fetch(baseURL + "/check_login.php")
      .then((response) => response.json())
      .then((data) => {
        console.log("Login status from AJAX:", data.loggedIn);
        // Update the global variable with the AJAX result
        window.userLoggedIn = !!data.loggedIn;
        callback(data.loggedIn === true);
      })
      .catch((error) => {
        console.error("Error checking login status:", error);
        callback(false); // Assume not logged in on error
      });
  }

  // Helper function to format date
  function formatDate(dateString) {
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) {
        return dateString; // Return original string if date is invalid
      }
      return date.toLocaleDateString("en-US", {
        weekday: "short",
        month: "short",
        day: "numeric",
      });
    } catch (e) {
      console.error("Error formatting date:", e);
      return dateString;
    }
  }
});
