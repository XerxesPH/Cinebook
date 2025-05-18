document.addEventListener("DOMContentLoaded", function () {
  const confirmationModal = document.getElementById("confirmationModal");
  const closeConfirmationModalBtn = document.getElementById(
    "closeConfirmationModal"
  );
  const doneButton = document.getElementById("doneButton");

  // Initialize global variables to store reservation information
  window.reservationDetails = {
    movieId: null,
    cinemaId: null,
    scheduleId: null,
    paymentMethodId: null,
    seats: [],
    totalAmount: 0,
    showDate: null,
    showTime: null,
  };

   // Unified modal closing function
  function closeConfirmationAndReset() {
    if (confirmationModal) {
      confirmationModal.style.display = "none";
    }
    if (typeof window.resetBookingState === "function") {
      window.resetBookingState();
    }
  }

  // Check if elements exist
  if (!confirmationModal) {
    console.error("Confirmation modal not found");
    return;
  }

  // Add event listeners for the confirmation modal
   // Add event listeners for modal close buttons
  if (closeConfirmationModalBtn) {
    closeConfirmationModalBtn.addEventListener("click", closeConfirmationAndReset);
  }


  if (doneButton) {
    doneButton.addEventListener("click", function () {
      // Check if the reservation has already been completed
      if (window.reservationComplete) {
        // If so, just close the modal without trying to save again
        closeConfirmationAndReset();
        return;
      }
      
      // Otherwise proceed with saving the reservation
      saveReservationToDatabase()
        .then(closeConfirmationAndReset)
        .catch((error) => {
          console.error("Error saving reservation:", error);
          alert("There was an error saving your reservation. Please try again.");
        });
    });
  }

   // Close modal when clicking outside the modal content
  window.addEventListener("click", function (event) {
    if (event.target === confirmationModal) {
      closeConfirmationAndReset();
    }
  });

  // Function to save reservation to database
  function saveReservationToDatabase() {
    return new Promise((resolve, reject) => {
      // Get all the reservation details
      const reservationData = {
        movie_id: window.reservationDetails.movieId,
        cinema_id: window.reservationDetails.cinemaId,
        schedule_id: window.reservationDetails.scheduleId,
        payment_method_id: window.reservationDetails.paymentMethodId,
        seats: window.reservationDetails.seats,
        total_amount: window.reservationDetails.totalAmount,
        show_date: window.reservationDetails.showDate,
        show_time: window.reservationDetails.showTime,
      };

      console.log("Sending reservation data:", reservationData);

      // Send data to server
      fetch("./api/process_reservation.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(reservationData),
      })
        .then((response) => {
          if (!response.ok) {
            return response.text().then((text) => {
              console.error("Server responded with error:", text);
              throw new Error(
                `Server responded with status: ${response.status}`
              );
            });
          }
          return response.text(); // First get the raw text response
        })
        .then((text) => {
          try {
            // Try to parse the response as JSON
            const data = JSON.parse(text);
            console.log("Reservation response:", data);

            if (data && data.success) {
              // Show success notification
              alert(
                "Reservation confirmed! Your reservation code is: " +
                  data.reservation_code
              );
              resolve(data);
            } else {
              // If the API returned a proper error message
              reject(
                new Error(
                  data && data.message ? data.message : "Unknown error occurred"
                )
              );
            }
          } catch (e) {
            // If the response isn't valid JSON, log the raw response and reject
            console.error("Error parsing server response:", e);
            console.log("Raw server response:", text);
            reject(new Error("Invalid response from server"));
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          reject(error);
        });
    });
  }

  // Process payment with new card with improved error handling
  function processNewCardPayment() {
    const paymentForm = document.getElementById("payment-form");
    const paymentModal = document.getElementById("paymentModal");

    if (!paymentForm) {
      console.error("Payment form not found");
      return;
    }

    const submitButton = paymentForm.querySelector('button[type="submit"]');
    if (!submitButton) {
      console.error("Submit button not found in payment form");
      return;
    }

    const originalText = submitButton.textContent;

    submitButton.disabled = true;
    submitButton.textContent = "Processing...";

    // Get form values
    const cardName = document.getElementById("card-name")?.value || "";
    const cardNumber = document.getElementById("card-number")?.value || "";
    const expiryDate = document.getElementById("expiry-date")?.value || "";
    const cvv = document.getElementById("cvv")?.value || "";

    // For now, we'll just simulate it with a fake payment_method_id
    const temporaryPaymentMethodId = "new_card_" + Date.now();
    const movieId = document.getElementById("movie-id")?.value || "1";
    const cinemaId = document.getElementById("cinema-id")?.value || "1";

    // Use selectedScreeningId as schedule_id
    const scheduleId =
      selectedScreeningId || document.getElementById("screening-id")?.value;

    const totalPriceDisplay = document.getElementById("total-price");
    const totalAmount = parseFloat(
      totalPriceDisplay ? totalPriceDisplay.textContent : 0
    );

    // Gather all reservation details with corrected parameter names
    const reservationDetails = {
      movie_id: movieId,
      cinema_id: cinemaId,
      schedule_id: scheduleId,
      payment_method_id: temporaryPaymentMethodId,
      seats: selectedSeats,
      total_amount: totalAmount,
      show_date: window.selectedDate,
      start_time: window.selectedTime,
    };

    // Send the reservation to the server with improved error handling
    fetch("./api/process_reservation.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(reservationDetails),
    })
      .then((response) => {
        if (!response.ok) {
          return response.text().then((text) => {
            console.error("Server responded with error:", text);
            throw new Error("Server error: " + response.status);
          });
        }
        return response.text().then((text) => {
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error("Error parsing JSON:", e);
            console.log("Raw server response:", text);
            throw new Error("Invalid JSON response from server");
          }
        });
      })
      .then((data) => {
        paymentForm.reset();
        if (paymentModal) paymentModal.style.display = "none";
        submitButton.disabled = false;
        submitButton.textContent = originalText;

        if (data && data.success) {
          // Show confirmation with the reservation code
          window.reservationComplete = true; // Mark the reservation as complete
          showConfirmationModal(data.reservation_code);
        } else {
          alert("Reservation failed: " + (data?.message || "Unknown error"));
        }
      })
      .catch((error) => {
        paymentForm.reset();
        if (paymentModal) paymentModal.style.display = "none";
        submitButton.disabled = false;
        submitButton.textContent = originalText;

        console.error("Error saving reservation:", error);
        alert(
          "An error occurred while processing your reservation. Please try again."
        );
      });
  }
});
