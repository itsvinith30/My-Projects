<?php
// index.php (Main Dashboard)
require_once 'header.php'; // Use the new shared header

// Ensure only admin and teacher can see this page
if ($_SESSION["role"] != 'admin' && $_SESSION["role"] != 'teacher') {
    // If another role (like student) is logged in, redirect them
    if (isset($_SESSION["role"]) && $_SESSION["role"] == 'student') {
        header("location: student_dashboard.php");
    } else {
        header("location: login.php");
    }
    exit;
}

// --- Dashboard Analytics (for Admin only) ---
if ($_SESSION['role'] == 'admin') {
    // Today's Attendance Stats
    $today = date('Y-m-d');
    $sql_today = "SELECT status, COUNT(record_id) as count FROM attendance_records WHERE date = ? GROUP BY status";
    $today_stats = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
    if ($stmt = $conn->prepare($sql_today)) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (isset($today_stats[$row['status']])) {
                $today_stats[$row['status']] = $row['count'];
            }
        }
        $stmt->close();
    }
    $today_data_json = json_encode(array_values($today_stats));
    $today_labels_json = json_encode(array_keys($today_stats));

    // This Week's Class Performance
    $start_of_week = date('Y-m-d', strtotime('monday this week'));
    $end_of_week = date('Y-m-d', strtotime('sunday this week'));
    $sql_weekly = "SELECT c.class_name, 
                      COUNT(ar.record_id) AS total_records,
                      SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) AS present_count
                   FROM classes c
                   LEFT JOIN attendance_records ar ON c.class_id = ar.class_id 
                   AND ar.date BETWEEN ? AND ?
                   GROUP BY c.class_id
                   ORDER BY c.class_name";
    
    $weekly_labels = [];
    $weekly_data = [];
    if($stmt = $conn->prepare($sql_weekly)) {
        $stmt->bind_param("ss", $start_of_week, $end_of_week);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $weekly_labels[] = $row['class_name'];
            $percentage = ($row['total_records'] > 0) ? ($row['present_count'] / $row['total_records']) * 100 : 0;
            $weekly_data[] = round($percentage, 2);
        }
        $stmt->close();
    }
    $weekly_labels_json = json_encode($weekly_labels);
    $weekly_data_json = json_encode($weekly_data);
}
?>
<head>
    <title>Dashboard - Attendance Tracker</title>
    <!-- Include Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<div class="main-content">
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <p>You are logged in as a(n) <strong><?php echo htmlspecialchars(ucfirst($_SESSION["role"])); ?></strong>.</p>
        <p>Select an option from the navigation bar to get started.</p>

        <?php if ($_SESSION['role'] == 'admin'): ?>
            <hr>
            <h3>Dashboard Analytics</h3>
            <div class="charts-container">
                <div class="chart-card">
                    <h4>Today's Attendance Overview</h4>
                    <canvas id="todayAttendanceChart"></canvas>
                </div>
                <div class="chart-card">
                    <h4>Weekly Class Performance (%)</h4>
                    <canvas id="weeklyPerformanceChart"></canvas>
                </div>
            </div>

            <script>
            // Chart for Today's Attendance
            const todayCtx = document.getElementById('todayAttendanceChart').getContext('2d');
            new Chart(todayCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo $today_labels_json; ?>,
                    datasets: [{
                        label: 'Attendance',
                        data: <?php echo $today_data_json; ?>,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Chart for Weekly Performance
            const weeklyCtx = document.getElementById('weeklyPerformanceChart').getContext('2d');
            new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo $weekly_labels_json; ?>,
                    datasets: [{
                        label: 'Present Percentage',
                        data: <?php echo $weekly_data_json; ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + "%"
                                }
                            }
                        }
                    }
                }
            });
            </script>
        <?php endif; ?>
    </div>
</div>

