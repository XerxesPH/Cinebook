document.addEventListener('DOMContentLoaded', function() {
    // Handle Edit Movie buttons
    const editButtons = document.querySelectorAll('.movies-content .edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            openEditMovieModal(movieId);
        });
    });

    // Handle Delete Movie buttons
    const deleteButtons = document.querySelectorAll('.movies-content .delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            confirmDeleteMovie(movieId);
        });
    });

    // Function to open edit movie modal and load movie data
    function openEditMovieModal(movieId) {
        // Get the add movie modal (we'll reuse it for editing)
        const modal = document.getElementById('addMovieModal');
        
        if (!modal) {
            console.error('Modal element not found! Make sure add-movie-modal.php is included.');
            return;
        }
        
        // Change title to indicate we're editing
        const modalTitle = modal.querySelector('h2');
        if (modalTitle) {
            modalTitle.textContent = 'Edit Movie';
        }
        
        // Show the modal
        modal.style.display = 'block';
        
        // Show loading state in the modal
        const form = modal.querySelector('form');
        if (form) {
            form.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading movie data...</div>';
        }
        
        // Fetch movie data from server
        fetch(`./admin_files/get_movie.php?id=${movieId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(movie => {
                // Update the form with movie data
                updateMovieForm(form, movie);
                
                // Set form action to update instead of create
                form.setAttribute('action', './admin_files/update_movie.php');
                
                // Add hidden input for movie ID
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'movie_id';
                idInput.value = movieId;
                form.appendChild(idInput);
            })
            .catch(error => {
                console.error('Error fetching movie data:', error);
                form.innerHTML = `<div class="error-message">Error loading movie data: ${error.message}</div>`;
            });
    }

    // Function to update the movie form with fetched data
    function updateMovieForm(form, movie) {
        // Mark the form as edited
        form.setAttribute('data-edited', 'true');
        
        // Generate the form HTML with movie data
        form.innerHTML = `
            <div class="form-group">
                <label for="movieTitle">Movie Title</label>
                <input type="text" id="movieTitle" name="title" value="${movie.title}" required>
            </div>
            <div class="form-group">
                <label for="movieDescription">Description</label>
                <textarea id="movieDescription" name="description" rows="4" required>${movie.description}</textarea>
            </div>
            <div class="form-group">
                <label for="movieGenre">Genre</label>
                <input type="text" id="movieGenre" name="genre" value="${movie.genre}" required>
            </div>
            <div class="form-group">
                <label for="movieDuration">Duration (minutes)</label>
                <input type="number" id="movieDuration" name="duration" value="${movie.duration}" required>
            </div>
            <div class="form-group">
                <label for="movieReleaseDate">Release Date</label>
                <input type="date" id="movieReleaseDate" name="release_date" value="${movie.release_date}" required>
            </div>
            <div class="form-group">
                <label for="movieCinema">Cinema</label>
                <select id="movieCinema" name="cinema_id" required>
                    ${generateCinemaOptions(movie.cinema_id)}
                </select>
            </div>
            <div class="form-group">
                <label for="movieStartShowing">Start Showing</label>
                <input type="date" id="movieStartShowing" name="start_showing" value="${movie.start_showing}" required>
            </div>
            <div class="form-group">
                <label for="movieEndShowing">End Showing</label>
                <input type="date" id="movieEndShowing" name="end_showing" value="${movie.end_showing}" required>
            </div>
            <div class="form-group">
                <label for="movieImage">Poster Image</label>
                <div class="current-image">
                    <img src="${movie.image}" alt="${movie.title}" width="100">
                    <p>Current poster. Upload new image only if you want to change it.</p>
                </div>
                <input type="file" id="movieImage" name="poster_image">
                <input type="hidden" name="current_image" value="${movie.image}">
            </div>
            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeMovieModal()">Cancel</button>
                <button type="submit" class="save-btn">Update Movie</button>
            </div>
        `;

        // Set up the form submission handler
        form.addEventListener('submit', handleMovieFormSubmit);
    }

    // Generate cinema options for the select dropdown
    function generateCinemaOptions(selectedCinemaId) {
        // This would ideally be populated from your cinemas data
        // For now, just return a placeholder option
        return `<option value="${selectedCinemaId}" selected>Cinema ${selectedCinemaId}</option>`;
        
        // In a real implementation, you would do something like:
        /*
        return cinemas.map(cinema => {
            const selected = cinema.id === selectedCinemaId ? 'selected' : '';
            return `<option value="${cinema.id}" ${selected}>Cinema ${cinema.name}</option>`;
        }).join('');
        */
    }

    // Function to handle movie form submission
    function handleMovieFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // Send form data to server
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Movie updated successfully!', 'success');
                
                // Close modal and reload page to reflect changes
                closeMovieModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Show error message
                showNotification('Error: ' + data.message, 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error: ' + error.message, 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        });
    }

    // Function to confirm and delete a movie
    function confirmDeleteMovie(movieId) {
        // Create confirmation dialog
        const confirmDialog = document.createElement('div');
        confirmDialog.className = 'confirm-dialog';
        confirmDialog.innerHTML = `
            <div class="confirm-dialog-content">
                <h3>Delete Movie</h3>
                <p>Are you sure you want to delete this movie? This action cannot be undone.</p>
                <div class="confirm-actions">
                    <button class="cancel-btn">Cancel</button>
                    <button class="delete-confirm-btn">Delete</button>
                </div>
            </div>
        `;
        
        // Add dialog to page
        document.body.appendChild(confirmDialog);
        
        // Handle dialog buttons
        confirmDialog.querySelector('.cancel-btn').addEventListener('click', function() {
            document.body.removeChild(confirmDialog);
        });
        
        confirmDialog.querySelector('.delete-confirm-btn').addEventListener('click', function() {
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            // Send delete request to server
            deleteMovie(movieId, confirmDialog);
        });
    }

    // Function to send delete request to server
    function deleteMovie(movieId, dialogElement) {
        // Create form data with movie ID
        const formData = new FormData();
        formData.append('movie_id', movieId);
        
        // Send delete request
        fetch('./admin_files/delete_movie.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Remove dialog
            document.body.removeChild(dialogElement);
            
            if (data.success) {
                // Show success notification
                showNotification('Movie deleted successfully!', 'success');
                
                // Remove movie row from table or reload page
                const movieRow = document.querySelector(`.edit-btn[data-id="${movieId}"]`).closest('tr');
                if (movieRow) {
                    movieRow.style.backgroundColor = '#ffcccc';
                    setTimeout(() => {
                        movieRow.style.transition = 'opacity 0.5s';
                        movieRow.style.opacity = '0';
                        setTimeout(() => {
                            movieRow.remove();
                        }, 500);
                    }, 300);
                } else {
                    // Reload page if row can't be found
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                // Show error notification
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            // Remove dialog
            document.body.removeChild(dialogElement);
            
            console.error('Error:', error);
            showNotification('Error: ' + error.message, 'error');
        });
    }

    // Function to close movie modal
    function closeMovieModal() {
        const modal = document.getElementById('addMovieModal');
        if (modal) {
            // Just hide the modal - don't try to reset anything here
            // The Add Movie button click handler will handle full reset when needed
            modal.style.display = 'none';
        }
    }
    
    // Make closeMovieModal function available globally
    window.closeMovieModal = closeMovieModal;

    // Function to show notification
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add notification to page
        document.body.appendChild(notification);
        
        // Show notification with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});