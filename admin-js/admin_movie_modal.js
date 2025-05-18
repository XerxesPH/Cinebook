// Modal functionality
document.addEventListener("DOMContentLoaded", function () {
  // Get modal elements
  const modal = document.getElementById("addMovieModal");
  const addMovieBtn = document.getElementById("addMovieBtn");
  const closeModal = document.querySelector(".close-modal");
  const cancelBtn = document.querySelector(".cancel-btn");
  
  // These will be updated in the functions when elements are cloned
  let hoursInput = document.getElementById("movie-duration-hours");
  let minutesInput = document.getElementById("movie-duration-minutes");
  let totalDurationInput = document.getElementById("movie-duration");
  let showStartDate, showEndDate;

  // Store the original form HTML for later restoration
  let originalFormHTML = '';
  const originalForm = document.getElementById("add-movie-form");
  if (originalForm) {
    originalFormHTML = originalForm.innerHTML;
  }

  // Open modal when Add Movie button is clicked
  if (addMovieBtn) {
    addMovieBtn.addEventListener("click", function () {
      // Force complete modal reset
      if (!modal) return;
      
      // Reset title
      const modalTitle = modal.querySelector("h2");
      if (modalTitle) {
        modalTitle.textContent = "Add New Movie";
      }
      
      // Get the form element
      const form = document.getElementById("add-movie-form");
      if (form) {
        // Check if form has been edited for movie editing
        if (form.getAttribute('data-edited') === 'true') {
          // Restore original form structure completely
          form.innerHTML = originalFormHTML;
          form.removeAttribute('data-edited');
          
          // Re-initialize all form elements and event listeners
          initializeFormElements();
        }
        
        // Reset form inputs
        form.reset();
        
        // Set correct action
        form.setAttribute("action", "./admin_files/add_movie.php");
      }
      
      // Show the modal
      modal.style.display = "block";
      
      // Ensure file upload button is working
      initFileUpload();
    });
  }

  // Close modal when X is clicked
  if (closeModal) {
    closeModal.addEventListener("click", function () {
      modal.style.display = "none";
      resetForm();
    });
  }

  // Close modal when Cancel button is clicked
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      modal.style.display = "none";
      resetForm();
    });
  }

  // Close modal when clicking outside of it
  window.addEventListener("click", function (event) {
    if (event.target == modal) {
      modal.style.display = "none";
      resetForm();
    }
  });

  // Initialize all form elements and their event listeners
  function initializeFormElements() {
    // Set default values for dates
    let releaseDate = document.getElementById("release-date");
    let showStartDate = document.getElementById("show-start-date");
    let showEndDate = document.getElementById("show-end-date");

    // Initialize date values if needed
    if (releaseDate && !releaseDate.value) {
      releaseDate.value = getCurrentDate();
    }

    if (showStartDate && !showStartDate.value) {
      showStartDate.value = getCurrentDate();
    }

    if (showEndDate && !showEndDate.value) {
      const defaultEndDate = new Date();
      defaultEndDate.setDate(defaultEndDate.getDate() + 6);
      showEndDate.value = formatDate(defaultEndDate);
    }

    // Set default end date when start date changes
    if (showStartDate && showEndDate) {
      // Remove existing event listeners by cloning
      const newStartDate = showStartDate.cloneNode(true);
      showStartDate.parentNode.replaceChild(newStartDate, showStartDate);
      showStartDate = newStartDate; // Update reference
      
      const newEndDate = showEndDate.cloneNode(true);
      showEndDate.parentNode.replaceChild(newEndDate, showEndDate);
      showEndDate = newEndDate; // Update reference
      
      // Add fresh event listeners
      showStartDate.addEventListener("change", function () {
        const startDate = new Date(this.value);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);
        showEndDate.value = formatDate(endDate);
        updateMovieAvailability();
      });

      showEndDate.addEventListener("change", function () {
        updateMovieAvailability();
      });
    }

    // Initialize file upload
    initFileUpload();

    // Duration calculation - remove existing listeners by cloning
    if (hoursInput && minutesInput && totalDurationInput) {
      const newHoursInput = hoursInput.cloneNode(true);
      hoursInput.parentNode.replaceChild(newHoursInput, hoursInput);
      hoursInput = newHoursInput; // Update global reference
      
      const newMinutesInput = minutesInput.cloneNode(true);
      minutesInput.parentNode.replaceChild(newMinutesInput, minutesInput);
      minutesInput = newMinutesInput; // Update global reference
      
      // Add fresh event listeners
      hoursInput.addEventListener("change", updateTotalDuration);
      hoursInput.addEventListener("input", updateTotalDuration);
      minutesInput.addEventListener("change", updateTotalDuration);
      minutesInput.addEventListener("input", updateTotalDuration);
    }

    // Form submission handling
    const addMovieForm = document.getElementById("add-movie-form");
    if (addMovieForm) {
      addMovieForm.addEventListener("submit", function (event) {
        event.preventDefault();

        // Validate form
        if (this.checkValidity()) {
          // Validate that at least one showtime is selected
          const showtimes = document.querySelectorAll(
            'input[name="showtimes[]"]:checked'
          );
          if (showtimes.length === 0) {
            alert("Please select at least one showtime");
            return;
          }

          // Validate that end date is after start date
          const startDate = new Date(showStartDate.value);
          const endDate = new Date(showEndDate.value);
          if (endDate <= startDate) {
            alert("End date must be after start date");
            return;
          }

          // Update availability based on dates before submission
          updateMovieAvailability();

          // Get form data
          const formData = new FormData(this);

          // Send the data to the server using fetch API
          fetch("admin_files/add_movie_handler.php", {
            method: "POST",
            body: formData,
          })
            .then((response) => {
              // First check if the response is OK
              if (!response.ok) {
                // Try to parse as JSON first
                return response.text().then((text) => {
                  try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    throw new Error(data.message || "Server error");
                  } catch (e) {
                    // If not valid JSON, it's probably an HTML error
                    console.error("Server response:", text);
                    throw new Error(
                      "Server returned an error. Check console for details."
                    );
                  }
                });
              }
              // If response is OK, try to parse as JSON
              return response.json().catch((err) => {
                console.error("Failed to parse JSON:", err);
                return response.text().then((text) => {
                  console.error("Response text:", text);
                  throw new Error("Invalid JSON response from server");
                });
              });
            })
            .then((data) => {
              if (data.success) {
                // Show success message
                alert(data.message || "Movie added successfully!");

                // Close modal
                modal.style.display = "none";

                // Reset form
                resetForm();

                // Reload page to show new movie
                window.location.reload();
              } else {
                throw new Error(data.message || "Failed to add movie");
              }
            })
            .catch((error) => {
              console.error("Error:", error);
              alert(error.message || "An error occurred while adding the movie.");
            });
        } else {
          alert("Please fill in all required fields");
        }
      });
    }
  }

  // Initialize file upload button and preview functionality
  function initFileUpload() {
    const moviePoster = document.getElementById("movie-poster");
    const posterPreview = document.getElementById("poster-preview");
    const uploadBtn = document.querySelector(".upload-btn");
    const fileName = document.querySelector(".file-name");

    if (moviePoster && posterPreview && uploadBtn) {
      // Remove existing event listeners first to prevent duplicates
      const newUploadBtn = uploadBtn.cloneNode(true);
      uploadBtn.parentNode.replaceChild(newUploadBtn, uploadBtn);
      
      // Remove existing event listeners from file input
      const newMoviePoster = moviePoster.cloneNode(true);
      moviePoster.parentNode.replaceChild(newMoviePoster, moviePoster);
      
      // Open file dialog when button is clicked (add fresh event listener)
      newUploadBtn.addEventListener("click", function() {
        console.log("Upload button clicked");
        newMoviePoster.click();
      });

      // Display preview when file is selected (add fresh event listener)
      newMoviePoster.addEventListener("change", function(event) {
        console.log("File selected", event.target.files);
        const file = event.target.files[0];
        if (file) {
          // Validate file is an image
          if (!file.type.startsWith("image/")) {
            alert("Please select an image file");
            return;
          }

          // Validate file size (max 5MB)
          if (file.size > 5 * 1024 * 1024) {
            alert("File size exceeds 5MB limit");
            return;
          }

          const reader = new FileReader();
          reader.onload = function(e) {
            posterPreview.src = e.target.result;
          };
          reader.readAsDataURL(file);

          // Update file name display
          if (fileName) {
            fileName.textContent = file.name;
          }
        }
      });
    } else {
      console.error("File upload elements not found:", {
        moviePoster: !!moviePoster,
        posterPreview: !!posterPreview,
        uploadBtn: !!uploadBtn,
        fileName: !!fileName
      });
    }
  }

  // Function to automatically set movie availability based on show dates
  function updateMovieAvailability() {
    // Get fresh references to elements in case they were updated
    const start = document.getElementById("show-start-date");
    const end = document.getElementById("show-end-date");
    
    if (!start || !end) {
      console.error("Show date elements not found");
      return;
    }
    
    const startDate = new Date(start.value);
    const endDate = new Date(end.value);
    const today = new Date();

    // Get current week boundaries (Monday to Sunday)
    const currentWeekStart = new Date(today);
    currentWeekStart.setDate(
      today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)
    ); // Monday
    currentWeekStart.setHours(0, 0, 0, 0);

    const currentWeekEnd = new Date(currentWeekStart);
    currentWeekEnd.setDate(currentWeekStart.getDate() + 6); // Sunday
    currentWeekEnd.setHours(23, 59, 59, 999);

    // Remove time components for proper comparison
    const startWithoutTime = new Date(
      startDate.getFullYear(),
      startDate.getMonth(),
      startDate.getDate()
    );
    const endWithoutTime = new Date(
      endDate.getFullYear(),
      endDate.getMonth(),
      endDate.getDate()
    );

    // A movie is available if and only if its showing period overlaps with the current week
    const isAvailable =
      startWithoutTime <= currentWeekEnd && endWithoutTime >= currentWeekStart;

    const availableInput = document.getElementById("is-available");
    if (availableInput) {
      availableInput.value = isAvailable ? "1" : "0";
    }

    // For debugging - can be removed in production
    console.log(
      "Current week:",
      formatDate(currentWeekStart),
      "to",
      formatDate(currentWeekEnd)
    );
    console.log(
      "Show dates:",
      formatDate(startWithoutTime),
      "to",
      formatDate(endWithoutTime)
    );
    console.log("Is available:", isAvailable);
  }

  // Helper function to get current date in YYYY-MM-DD format
  function getCurrentDate() {
    const today = new Date();
    return formatDate(today);
  }

  // Helper function to format date as YYYY-MM-DD
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  function updateTotalDuration() {
    // Get fresh references to elements
    const hours = document.getElementById("movie-duration-hours");
    const minutes = document.getElementById("movie-duration-minutes");
    const totalDuration = document.getElementById("movie-duration");
    
    if (hours && minutes && totalDuration) {
      const hoursValue = parseInt(hours.value) || 0;
      const minutesValue = parseInt(minutes.value) || 0;
      
      // Calculate total minutes
      const totalMinutes = (hoursValue * 60) + minutesValue;
      
      // Update hidden field
      totalDuration.value = totalMinutes;
      
      console.log("Total duration updated:", totalMinutes, "minutes");
    }
  }
  
  // Add event listeners to update total duration when values change
  if (hoursInput) {
    hoursInput.addEventListener("change", updateTotalDuration);
    hoursInput.addEventListener("input", updateTotalDuration);
  }
  
  if (minutesInput) {
    minutesInput.addEventListener("change", updateTotalDuration);
    minutesInput.addEventListener("input", updateTotalDuration);
  }
  
  // Initialize total duration on page load
  updateTotalDuration();
  
  // Add validation to the form submission handler to ensure minutes is between 0-59
  // This section should be added to your existing form submit handler
  const validateDuration = function() {
    const minutes = parseInt(minutesInput.value);
    if (minutes < 0 || minutes > 59) {
      alert("Minutes must be between 0 and 59");
      return false;
    }
    return true;
  };

  // Reset form and all its elements
  function resetForm() {
    const form = document.getElementById("add-movie-form");
    if (form) {
      form.reset();
      
      // Reset the poster preview
      const posterPreview = document.getElementById("poster-preview");
      if (posterPreview) {
        posterPreview.src = "images/placeholder-poster.jpg";
      }
      
      // Reset the file name display
      const fileName = document.querySelector(".file-name");
      if (fileName) {
        fileName.textContent = "No file chosen";
      }
      
      // Reset duration values
      if (hoursInput && minutesInput && totalDurationInput) {
        hoursInput.value = 2;
        minutesInput.value = 0;
        updateTotalDuration();
      }
      
      // Re-initialize file upload to ensure proper event handlers
      setTimeout(initFileUpload, 0);
      
      // Update availability based on default dates
      updateMovieAvailability();
    }
  }

  // Call initially to set everything up
  initializeFormElements();
  updateMovieAvailability();
}); // End of DOMContentLoaded event listener
