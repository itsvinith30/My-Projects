<?php
// This is the dashboard for logged-in counselors.
// We need a database connection to fetch data.
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

$counselor_id = $_SESSION['user_id'];

// --- Fetch Counselor's Profile ---
$profile = null;
$sql_profile = "SELECT u.full_name, cp.specialization, cp.profile_image_url 
                FROM users u
                JOIN counselor_profiles cp ON u.user_id = cp.user_id
                WHERE u.user_id = ?";
$stmt_profile = $db->prepare($sql_profile);
$stmt_profile->bind_param("i", $counselor_id);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
if ($result_profile) {
    $profile = $result_profile->fetch_assoc();
}

// --- Fetch Counselor's Current Availability ---
$availability = [];
$sql_avail = "SELECT day_of_week, start_time, end_time FROM counselor_availability WHERE counselor_id = ?";
$stmt_avail = $db->prepare($sql_avail);
$stmt_avail->bind_param("i", $counselor_id);
$stmt_avail->execute();
$result_avail = $stmt_avail->get_result();
if ($result_avail) {
    while ($row = $result_avail->fetch_assoc()) {
        $availability[$row['day_of_week']] = ['start' => $row['start_time'], 'end' => $row['end_time']];
    }
}


// --- Fetch Pending Appointments ---
$pending_appointments = [];
$sql_pending = "SELECT a.appointment_id, u.full_name as patient_name, a.requested_at, a.scheduled_datetime, a.application_form_data
                FROM appointments a
                JOIN users u ON a.patient_id = u.user_id
                WHERE a.counselor_id = ? AND a.status = 'pending'
                ORDER BY a.requested_at ASC";
$stmt_pending = $db->prepare($sql_pending);
$stmt_pending->bind_param("i", $counselor_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
if ($result_pending) {
    while($row = $result_pending->fetch_assoc()) {
        $pending_appointments[] = $row;
    }
}

// --- Fetch Approved Appointments ---
$approved_appointments = [];
$sql_approved = "SELECT a.appointment_id, u.full_name as patient_name, a.scheduled_datetime, a.meeting_details
                 FROM appointments a
                 JOIN users u ON a.patient_id = u.user_id
                 WHERE a.counselor_id = ? AND a.status = 'approved'
                 ORDER BY a.scheduled_datetime ASC";
$stmt_approved = $db->prepare($sql_approved);
$stmt_approved->bind_param("i", $counselor_id);
$stmt_approved->execute();
$result_approved = $stmt_approved->get_result();
if ($result_approved) {
    while($row = $result_approved->fetch_assoc()) {
        $approved_appointments[] = $row;
    }
}


$db->close();
?>

<div class="container dashboard">

    <?php 
    // --- Display Success/Error Messages ---
    if (isset($_GET['status'])): 
        $message = '';
        $alert_type = 'success';
        switch ($_GET['status']) {
            case 'profileupdated':
                $message = "Your profile has been updated successfully.";
                break;
            case 'passwordchanged':
                $message = "Your password has been changed successfully.";
                break;
            case 'availabilityupdated':
                $message = "Your availability has been saved successfully.";
                break;
        }
    ?>
        <div class="alert <?php echo $alert_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Counselor Profile Section -->
    <div class="profile-form-container">
        <h2>My Profile</h2>
        <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="profile-photo-area">
                <img id="profilePicPreview" src="uploads/<?php echo htmlspecialchars($profile['profile_image_url']); ?>" alt="Profile Preview">
                <div>
                     <label for="profile_photo" class="file-input-label">Select Image</label>
                     <input type="file" id="profile_photo" name="profile_photo" accept="image/png, image/jpeg, image/gif">
                     <p><small>Select a new image to update your photo.</small></p>
                </div>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" name="specialization" value="<?php echo htmlspecialchars($profile['specialization']); ?>" required>
            </div>
            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>

    <hr>
    
    <!-- Counselor Availability Section -->
    <div class="availability-form-container">
        <h2>My Weekly Availability</h2>
        <p>Select the days you are available and set your working hours. Patients will only be able to book appointments during these times.</p>
        <form action="actions/update_availability.php" method="POST">
            <div class="availability-schedule">
                <?php 
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day): 
                    $is_checked = isset($availability[$day]);
                    $start_time = $is_checked ? date('H:i', strtotime($availability[$day]['start'])) : '09:00';
                    $end_time = $is_checked ? date('H:i', strtotime($availability[$day]['end'])) : '17:00';
                ?>
                <div class="day-row">
                    <div class="day-label">
                        <input type="checkbox" id="day_<?php echo $day; ?>" name="days[]" value="<?php echo $day; ?>" <?php if($is_checked) echo 'checked'; ?>>
                        <label for="day_<?php echo $day; ?>"><?php echo $day; ?></label>
                    </div>
                    <div class="time-inputs">
                        <label for="start_<?php echo $day; ?>">From</label>
                        <input type="time" id="start_<?php echo $day; ?>" name="start_time[<?php echo $day; ?>]" value="<?php echo $start_time; ?>">
                        <label for="end_<?php echo $day; ?>">To</label>
                        <input type="time" id="end_<?php echo $day; ?>" name="end_time[<?php echo $day; ?>]" value="<?php echo $end_time; ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn">Save Availability</button>
        </form>
    </div>

    <hr>

    <!-- Security Section -->
    <div class="security-section">
        <h2>Security</h2>
        <form action="actions/change_password.php" method="POST">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>
    </div>

    <hr>

    <h2>Appointment Requests</h2>
    <p>Review new requests and manage your scheduled appointments.</p>

    <div class="appointment-list">
        <?php if (empty($pending_appointments)): ?>
            <p>You have no new appointment requests.</p>
        <?php else: ?>
            <?php foreach ($pending_appointments as $appt): 
                $app_data = json_decode($appt['application_form_data'], true);
            ?>
                <div class="appointment-item status-pending">
                    <div>
                        <strong>Patient:</strong> <?php echo htmlspecialchars($app_data['patient_name'] ?? 'N/A'); ?><br>
                        <strong>Requested for:</strong> <?php echo (new DateTime($appt['scheduled_datetime']))->format('l, F j, Y \a\t g:i A'); ?><br>
                        <p><strong>Reason:</strong> "<?php echo htmlspecialchars($app_data['reason'] ?? 'No reason provided.'); ?>"</p>
                    </div>
                    <div class="appointment-actions">
                        <button class="btn btn-success" onclick="openApproveModal(<?php echo $appt['appointment_id']; ?>)">Approve</button>
                        <button class="btn btn-danger" onclick="openRejectModal(<?php echo $appt['appointment_id']; ?>)">Reject</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <hr>
    
    <h2>Upcoming Approved Appointments</h2>
    <div class="appointment-list">
        <?php if (empty($approved_appointments)): ?>
            <p>You have no upcoming appointments.</p>
        <?php else: ?>
            <?php foreach ($approved_appointments as $appt): ?>
                <div class="appointment-item status-approved">
                     <div>
                        <strong>Patient:</strong> <?php echo htmlspecialchars($appt['patient_name']); ?><br>
                        <strong>Scheduled for:</strong> <?php echo (new DateTime($appt['scheduled_datetime']))->format('l, F j, Y \a\t g:i A'); ?><br>
                        <strong>Meeting Details Sent:</strong> <a href="<?php echo htmlspecialchars($appt['meeting_details']); ?>" target="_blank"><?php echo htmlspecialchars($appt['meeting_details']); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeApproveModal()">&times;</span>
        <h3>Approve Appointment</h3>
        <p>The patient has requested a specific time. Please confirm the meeting details below.</p>
        <form action="actions/approve_appointment.php" method="POST">
            <input type="hidden" id="approveAppointmentId" name="appointment_id">
            <div class="form-group">
                <label for="meeting_details">Meeting Link & Instructions</label>
                <textarea name="meeting_details" rows="4" placeholder="e.g., Please join using this Google Meet link: https://meet.google.com/..." required></textarea>
            </div>
            <button type="submit" class="btn">Confirm & Send Notification</button>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeRejectModal()">&times;</span>
        <h3>Reject Appointment</h3>
        <form action="actions/reject_appointment.php" method="POST">
            <input type="hidden" id="rejectAppointmentId" name="appointment_id">
            <div class="form-group">
                <label for="rejection_reason">Reason for Rejection (optional, will be sent to patient)</label>
                <textarea name="rejection_reason" rows="4" placeholder="e.g., My schedule is currently full. Please try booking again in a few weeks."></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Confirm Rejection</button>
        </form>
    </div>
</div>

