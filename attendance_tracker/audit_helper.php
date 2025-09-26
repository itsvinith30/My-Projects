<?php
// audit_helper.php
// Contains functions for logging user actions to the audit trail.

/**
 * Logs an action to the audit_logs table.
 *
 * @param string $action_type A short code for the type of action (e.g., 'LOGIN_SUCCESS').
 * @param string $log_message A detailed description of the action.
 */
function log_action($action_type, $log_message) {
    global $conn; // Use the global database connection from db_connect.php

    $user_id = $_SESSION['id'] ?? null;
    $user_role = $_SESSION['role'] ?? 'Guest';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $sql = "INSERT INTO audit_logs (user_id, user_role, action_type, log_message, ip_address) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issss", $user_id, $user_role, $action_type, $log_message, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}
?>
