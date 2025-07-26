<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: log_index.php");
    exit;
}
$music_files = [];
$music_dir = 'songs/';
$allowed_ext = ['mp3', 'wav', 'ogg'];

if (is_dir($music_dir)) {
    $files = scandir($music_dir);
    foreach ($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed_ext)) {
            $basename = pathinfo($file, PATHINFO_FILENAME);
            // Prevent duplicates for different file formats
            if (!in_array($basename, $music_files)) {
                $music_files[] = $basename;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Activated Music Player</title>
    <link rel="shortcut icon" href="logo.png" type="image/x-icon">
    <link rel="stylesheet" href="aimp_style.css">
</head>
<body>
<div class="user-info-container">
        <span class="logged-in-user">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <button id="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>
    <img src="ai.png" alt="logo" id="logo">
    <h1>I'm <span id="name">Melody</span>, Your <span id="va">Virtual Music player</span> </h1>
    <img src="voice.gif" alt="" id="voice">
    <button id="btn"><img src="mic.svg" alt="mic"><span id="content">CLICK HERE TO TALK TO ME</span></button>
    
    <!-- Wake Word Indicator -->
    <div id="wake-word-indicator">
        <div class="ripple"></div>
        <span class="wake-word-text">Say "Hey Melody" to activate</span>
    </div>

    <!-- Music Container -->
    <div id="music-container" style="display:none;">
        <audio id="player" controls style="display:none;"></audio>
    </div>

    <!-- Playback Controller -->
    <div id="playback-controller" style="display: none;">
        <div class="music-status">
            Playing: <span id="now-playing"></span>
        </div>
        <div class="time-info">
            <span id="current-time">0:00</span>
            <div class="progress-container" id="progress-container">
                <div id="progress-bar"></div>
            </div>
            <span id="duration">0:00</span>
        </div>
        <div class="controls">
            <button id="shuffle-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="16 3 21 3 21 8"></polyline>
                    <line x1="4" y1="20" x2="21" y2="3"></line>
                    <polyline points="21 16 21 21 16 21"></polyline>
                    <line x1="15" y1="15" x2="21" y2="21"></line>
                    <line x1="4" y1="4" x2="9" y2="9"></line>
                </svg>
            </button>
            <button id="prev-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="19 20 9 12 19 4 19 20"></polygon>
                    <line x1="5" y1="19" x2="5" y2="5"></line>
                </svg>
            </button>
            <button id="play-pause-btn">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pause-icon">
                    <rect x="6" y="4" width="4" height="16"></rect>
                    <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="play-icon" style="display:none;">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </button>
            <button id="next-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 4 15 12 5 20 5 4"></polygon>
                    <line x1="19" y1="5" x2="19" y2="19"></line>
                </svg>
            </button>
            <button id="repeat-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17 1 21 5 17 9"></polyline>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <polyline points="7 23 3 19 7 15"></polyline>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </button>
        </div>
    </div>
    <script>
        const INITIAL_SONGS = <?php echo json_encode($music_files); ?>;
    </script>
    <script src="aimp_script.js"></script>
</body>
</html>