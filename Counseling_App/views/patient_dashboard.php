<?php
// This is the dashboard for logged-in patients.
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

$patient_id = $_SESSION['user_id'];

// --- Fetch Counselors for Display (including their profile image) ---
$counselors = [];
$sql_counselors = "SELECT u.user_id, u.full_name, cp.specialization, cp.profile_image_url
                   FROM users u 
                   JOIN counselor_profiles cp ON u.user_id = cp.user_id 
                   WHERE u.user_role = 'counselor'";
$result_counselors = $db->query($sql_counselors);
if ($result_counselors) {
    while($row = $result_counselors->fetch_assoc()) {
        $counselors[] = $row;
    }
}

// --- Fetch Patient's Appointments ---
$appointments = [];
$sql_appointments = "SELECT a.status, a.scheduled_datetime, a.meeting_details, u.full_name as counselor_name, a.requested_at
                     FROM appointments a
                     JOIN users u ON a.counselor_id = u.user_id
                     WHERE a.patient_id = ?
                     ORDER BY a.requested_at DESC";
$stmt_appointments = $db->prepare($sql_appointments);
$stmt_appointments->bind_param("i", $patient_id);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
if ($result_appointments) {
    while($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
}

$db->close();
?>

<div class="container dashboard">
    <h2>Find a Counselor</h2>
    <p>Browse our available counselors and book an appointment when you're ready.</p>
    
    <div class="counselor-list">
        <?php if (empty($counselors)): ?>
            <p>No counselors are available at this time.</p>
        <?php else: ?>
            <?php foreach ($counselors as $counselor): ?>
                <div class="counselor-card">
                    <img src="uploads/<?php echo htmlspecialchars($counselor['profile_image_url'] ?? 'default_avatar.png'); ?>" alt="<?php echo htmlspecialchars($counselor['full_name']); ?>">
                    <h3><?php echo htmlspecialchars($counselor['full_name']); ?></h3>
                    <p><?php echo htmlspecialchars($counselor['specialization']); ?></p>
                    <button class="btn btn-primary" onclick="openBookingModal(<?php echo $counselor['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($counselor['full_name'])); ?>')">Book Appointment</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>
    
    <h2>My Appointments</h2>
    <div class="appointment-list">
        <?php if (empty($appointments)): ?>
            <p>You have not booked any appointments yet.</p>
        <?php else: ?>
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-item status-<?php echo htmlspecialchars($appt['status']); ?>">
                    <div>
                        <strong>Counselor:</strong> <?php echo htmlspecialchars($appt['counselor_name']); ?><br>
                        <strong>Status:</strong> <span style="text-transform: capitalize;"><?php echo htmlspecialchars($appt['status']); ?></span><br>
                        
                        <?php if ($appt['status'] === 'approved' && !empty($appt['scheduled_datetime'])): ?>
                            <strong>Date & Time:</strong> <?php echo (new DateTime($appt['scheduled_datetime']))->format('l, F j, Y \a\t g:i A'); ?> <br>
                            <strong>Meeting Link:</strong> <a href="<?php echo htmlspecialchars($appt['meeting_details']); ?>" target="_blank">Join Meeting</a>
                        <?php elseif ($appt['status'] === 'pending' && !empty($appt['scheduled_datetime'])): ?>
                             <strong>Requested for:</strong> <?php echo (new DateTime($appt['scheduled_datetime']))->format('l, F j, Y \a\t g:i A'); ?>
                        <?php else: ?>
                            <strong>Requested on:</strong> <?php echo (new DateTime($appt['requested_at']))->format('F j, Y'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- (Booking Modal remains unchanged) -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeBookingModal()">&times;</span>
        <h3>Book an appointment with <span id="modalCounselorName"></span></h3>
        <form id="bookingForm" action="actions/book_appointment.php" method="POST">
            
            <input type="hidden" id="modalCounselorId" name="counselor_id">
            
            <div class="form-group">
                <label for="appointment_date">Select a Date</label>
                <input type="date" id="appointment_date" name="appointment_date" required>
            </div>

            <div class="form-group">
                <label>Available Time Slots</label>
                <div id="timeSlotsContainer" class="time-slots-container">
                    <p>Please select a date to see available times.</p>
                </div>
                <input type="hidden" id="selected_time_slot" name="scheduled_datetime" required>
            </div>

            <hr>

            <div class="form-group">
                <label for="patient_name">Your Full Name</label>
                <input type="text" name="patient_name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="patient_dob">Date of Birth</label>
                <input type="date" name="patient_dob" required>
            </div>
            <div class="form-group">
                <label for="reason">Primary reason for seeking counseling?</label>
                <textarea name="reason" rows="4" required></textarea>
            </div>
            
            <button type="submit" class="btn" id="submitBookingBtn" disabled>Submit Application</button>
        </form>
    </div>
</div>

