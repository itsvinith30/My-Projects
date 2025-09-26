<?php
// system_helpers.php
// Contains helper functions, like loading global application settings.

// Global variable to hold all application settings
$app_settings = [];

/**
 * Loads all settings from the database into the global $app_settings variable.
 * This function is called from db_connect.php after a successful connection.
 * @param mysqli $conn The database connection object.
 */
function load_settings($conn) {
    global $app_settings;

    $sql = "SELECT setting_key, setting_value FROM settings";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $app_settings[$row['setting_key']] = $row['setting_value'];
        }
        $result->free();
    }

    // Set the application's default timezone
    if (isset($app_settings['timezone']) && !empty($app_settings['timezone'])) {
        date_default_timezone_set($app_settings['timezone']);
    } else {
        // Fallback to UTC if no timezone is set
        date_default_timezone_set('UTC');
    }
}

// Note: This function is called in `db_connect.php` immediately after this file is included.
?>

