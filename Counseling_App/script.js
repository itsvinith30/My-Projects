// --- Modal Handling ---

// Get modal elements
const bookingModal = document.getElementById('bookingModal');
const approveModal = document.getElementById('approveModal');
const rejectModal = document.getElementById('rejectModal');

// Open Booking Modal
function openBookingModal(counselorId, counselorName) {
    // Reset the form fields from previous openings
    const form = document.getElementById('bookingForm');
    if(form) form.reset();
    
    const timeSlotsContainer = document.getElementById('timeSlotsContainer');
    if(timeSlotsContainer) timeSlotsContainer.innerHTML = '<p>Please select a date to see available times.</p>';
    
    const submitBtn = document.getElementById('submitBookingBtn');
    if(submitBtn) submitBtn.disabled = true;

    // Set counselor-specific info
    document.getElementById('modalCounselorId').value = counselorId;
    document.getElementById('modalCounselorName').textContent = counselorName;
    
    // Set the minimum date for the date picker to today
    const datePicker = document.getElementById('appointment_date');
    const today = new Date().toISOString().split('T')[0];
    if(datePicker) datePicker.setAttribute('min', today);

    if(bookingModal) bookingModal.style.display = 'block';
}

// Close Booking Modal
function closeBookingModal() {
    if(bookingModal) bookingModal.style.display = 'none';
}

// Open Approve Modal
function openApproveModal(appointmentId) {
    if(approveModal) {
        document.getElementById('approveAppointmentId').value = appointmentId;
        approveModal.style.display = 'block';
    }
}

// Close Approve Modal
function closeApproveModal() {
    if(approveModal) approveModal.style.display = 'none';
}

// Open Reject Modal
function openRejectModal(appointmentId) {
    if(rejectModal) {
        document.getElementById('rejectAppointmentId').value = appointmentId;
        rejectModal.style.display = 'block';
    }
}

// Close Reject Modal
function closeRejectModal() {
    if(rejectModal) rejectModal.style.display = 'none';
}

// Close modals if user clicks outside of the modal content
window.onclick = function(event) {
    if (event.target == bookingModal) {
        closeBookingModal();
    }
    if (event.target == approveModal) {
        closeApproveModal();
    }
    if (event.target == rejectModal) {
        closeRejectModal();
    }
}


// --- DYNAMIC FEATURES (Run after page loads) ---
document.addEventListener('DOMContentLoaded', function() {

    // --- Theme Toggle Logic ---
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Check for saved theme in localStorage
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        body.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
            themeToggle.checked = true;
        }
    }

    // Listen for toggle change
    themeToggle.addEventListener('change', function() {
        if (this.checked) {
            body.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            body.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
    });


    // --- Auto-hide alert messages ---
    const alertMessage = document.querySelector('.alert');
    if (alertMessage) {
        setTimeout(() => {
            alertMessage.classList.add('hidden');
            setTimeout(() => {
                alertMessage.remove();
            }, 500);
        }, 5000);
    }
    
    // --- Counselor Profile Photo Preview ---
    const profilePhotoInput = document.getElementById('profile_photo');
    const profilePicPreview = document.getElementById('profilePicPreview');

    if (profilePhotoInput && profilePicPreview) {
        profilePhotoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // --- Time Slot Availability Logic ---
    const datePicker = document.getElementById('appointment_date');
    const timeSlotsContainer = document.getElementById('timeSlotsContainer');
    const hiddenTimeSlotInput = document.getElementById('selected_time_slot');
    const submitBtn = document.getElementById('submitBookingBtn');

    if(datePicker && timeSlotsContainer && hiddenTimeSlotInput && submitBtn) {
        datePicker.addEventListener('change', function() {
            const selectedDate = this.value;
            const counselorId = document.getElementById('modalCounselorId').value;
            
            timeSlotsContainer.innerHTML = '<p>Loading available times...</p>';
            hiddenTimeSlotInput.value = '';
            submitBtn.disabled = true;

            if (selectedDate && counselorId) {
                fetch(`actions/get_availability.php?counselor_id=${counselorId}&date=${selectedDate}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            timeSlotsContainer.innerHTML = `<p style="color: red;">${data.error}</p>`;
                        } else {
                            displayTimeSlots(data, selectedDate);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching availability:', error);
                        timeSlotsContainer.innerHTML = '<p style="color: red;">Could not fetch time slots. Please try again.</p>';
                    });
            }
        });
    }

    function displayTimeSlots(slots, date) {
        if (slots.length === 0) {
            timeSlotsContainer.innerHTML = '<p>No available time slots for this day.</p>';
            return;
        }

        timeSlotsContainer.innerHTML = '';
        slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-slot-btn';
            button.textContent = slot;
            button.dataset.slot = slot;

            button.addEventListener('click', function() {
                const allButtons = timeSlotsContainer.querySelectorAll('.time-slot-btn');
                allButtons.forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
                hiddenTimeSlotInput.value = `${date} ${slot}:00`;
                submitBtn.disabled = false;
            });

            timeSlotsContainer.appendChild(button);
        });
    }
});

