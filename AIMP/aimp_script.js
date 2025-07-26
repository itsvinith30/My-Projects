let btn = document.querySelector("#btn");
let content = document.querySelector("#content");
let voice = document.querySelector("#voice");
let player = document.getElementById('player');
const MUSIC_FOLDER = 'songs/';

// Playback controls
const playPauseBtn = document.getElementById('play-pause-btn');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const progressBar = document.getElementById('progress-bar');
const progressContainer = document.getElementById('progress-container');
const currentTimeElement = document.getElementById('current-time');
const durationElement = document.getElementById('duration');

// Recognition constants
const WAKE_WORD_REGEX = /(hey|hi|hey there)\s(melody|melodi|mélodie)/i;
const MIN_CONFIDENCE = 0.6;
let currentSongIndex = -1;
let songQueue = [];
let storedPlaybackTime = null;
let voiceTimeout = null;
let isWakeWordListening = false;

// Initialize player
window.onload = function() {
    document.getElementById('music-container').style.display = 'block';
    player.volume = 0.8;

    // Initialize queue from storage or server list
    let storedQueue = localStorage.getItem('songQueue');
    if (storedQueue) {
        const parsedQueue = JSON.parse(storedQueue);
        songQueue = [...new Set([...parsedQueue, ...INITIAL_SONGS])];
        const currentSong = player.src.split('/').pop()?.split('.')?.slice(0, -1)?.join('-') || '';
        currentSongIndex = songQueue.indexOf(currentSong);
        if (currentSongIndex === -1) currentSongIndex = 0;
    } else {
        songQueue = [...INITIAL_SONGS];
        currentSongIndex = songQueue.length > 0 ? 0 : -1;
    }

    player.addEventListener('timeupdate', updateProgress);
    player.addEventListener('play', () => {
        document.getElementById('playback-controller').style.display = 'flex';
        document.querySelector('.pause-icon').style.display = 'block';
        document.querySelector('.play-icon').style.display = 'none';
    });
    player.addEventListener('pause', () => {
        document.querySelector('.pause-icon').style.display = 'none';
        document.querySelector('.play-icon').style.display = 'block';
    });

    playPauseBtn.addEventListener('click', togglePlayPause);
    prevBtn.addEventListener('click', playPrevious);
    nextBtn.addEventListener('click', playNext);
    
    progressContainer.addEventListener('click', (e) => {
        const rect = progressContainer.getBoundingClientRect();
        const pos = (e.clientX - rect.left) / rect.width;
        player.currentTime = pos * player.duration;
    });

    enableNoiseSuppression();
    startWakeWordDetection();
    updateWakeWordIndicator();
};

// Improved noise suppression
async function enableNoiseSuppression() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            audio: {
                noiseSuppression: true,
                echoCancellation: true,
                autoGainControl: true
            }
        });
        wakeWordRecognition.stream = stream;
    } catch (error) {
        console.log("Audio enhancements not available:", error);
    }
}

function updateWakeWordIndicator() {
    const indicator = document.getElementById('wake-word-indicator');
    indicator.classList.toggle('active', isWakeWordListening);
}

// Enhanced time formatting
function formatTime(seconds) {
    if (isNaN(seconds) || seconds === Infinity) return "0:00";
    const minutes = Math.floor(seconds / 60);
    return `${minutes}:${Math.floor(seconds % 60).toString().padStart(2, '0')}`;
}

function updateProgress() {
    const progressPercent = (player.currentTime / player.duration) * 100;
    progressBar.style.width = `${progressPercent}%`;
    currentTimeElement.textContent = formatTime(player.currentTime);
    
    if (player.duration !== Infinity && !isNaN(player.duration)) {
        durationElement.textContent = formatTime(player.duration);
    }
}

// Improved speech synthesis
function speak(text) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 1;
    utterance.pitch = 1;
    utterance.volume = 0.8;
    utterance.lang = "hi-IN";
    window.speechSynthesis.speak(utterance);
}

btn.addEventListener('click', activateFullVoiceRecognition);

// Enhanced voice activation
function activateFullVoiceRecognition() {
    if (isWakeWordListening) stopWakeWordDetection();
    
    if (!player.paused) {
        storedPlaybackTime = player.currentTime;
        player.pause();
    }
    
    recognition.start();
    btn.style.display = "none";
    voice.style.display = "block";
    document.getElementById('playback-controller').style.display = 'none';

    voiceTimeout = setTimeout(resetVoiceInterface, 5000);
}

// Recognition initialization
let speechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let recognition = new speechRecognition();
recognition.continuous = false;
recognition.lang = 'en-IN';
recognition.interimResults = false;
recognition.maxAlternatives = 3;

recognition.onresult = (event) => {
    clearTimeout(voiceTimeout);
    const result = event.results[0][0];
    content.textContent = result.transcript;
    takeCommand(result.transcript.toLowerCase());
    resetVoiceInterface();
};

function resetVoiceInterface() {
    voice.style.display = "none";
    btn.style.display = "flex";
    
    if (storedPlaybackTime !== null && player.paused) {
        player.currentTime = storedPlaybackTime;
        player.play();
        storedPlaybackTime = null;
    }
    
    if (!player.paused) {
        document.getElementById('playback-controller').style.display = 'flex';
    }
    
    recognition.abort();
    if (!isWakeWordListening) startWakeWordDetection();
}

// Enhanced command handling
function takeCommand(message) {
    const lowerMsg = message.toLowerCase();
    
    if (lowerMsg.startsWith('play')) {
        searchAndPlay(message.slice(4).trim());
    }
    else if (lowerMsg.includes('pause')) {
        player.pause();
        speak("Music paused");
        storedPlaybackTime = null;
    }
    else if (lowerMsg.includes('resume') || lowerMsg.includes('continue')) {
        player.play();
        speak("Resuming music");
        storedPlaybackTime = null;
    }
    else if (lowerMsg.includes('volume up')) {
        player.volume = Math.min(player.volume + 0.2, 1);
        speak(`Volume set to ${Math.round(player.volume * 100)}%`);
    }
    else if (lowerMsg.includes('volume down')) {
        player.volume = Math.max(player.volume - 0.2, 0);
        speak(`Volume set to ${Math.round(player.volume * 100)}%`);
    }
    else if (/(hello|hi|hey)/.test(lowerMsg)) {
        speak("Hello! Which song would you like to play?");
    }
    else if (/(who are you|hu r u)/.test(lowerMsg)) {
        speak("I am your virtual music assistant Melody");
    }
    else if (lowerMsg.includes('time')) {
        speak(new Date().toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'}));
    }
}

// Improved song search
function searchAndPlay(query) {
    const formattedQuery = query.replace(/\s+/g, '-').toLowerCase();
    const extensions = ['mp3', 'wav', 'ogg'];
    let found = false;

    extensions.forEach(ext => {
        const filePath = `${MUSIC_FOLDER}${formattedQuery}.${ext}`;
        fetch(filePath).then(response => {
            if (response.ok && !found) {
                found = true;
                player.src = filePath;
                player.play();
                const displayName = formatDisplayName(query);
                document.getElementById('now-playing').textContent = displayName;
                speak(`Playing ${displayName}`);
                
                if (!songQueue.includes(formattedQuery)) {
                    songQueue.push(formattedQuery);
                    currentSongIndex = songQueue.length - 1;
                } else {
                    currentSongIndex = songQueue.indexOf(formattedQuery);
                }
                localStorage.setItem('songQueue', JSON.stringify(songQueue));
            }
        }).catch(() => {
            if (!found) {
                speak("Song not found");
                songQueue = songQueue.filter(song => song !== formattedQuery);
                localStorage.setItem('songQueue', JSON.stringify(songQueue));
            }
        });
    });

    setTimeout(() => !found && speak("Song not found"), 1500);
}

// Player controls
function togglePlayPause() {
    player.paused ? player.play() : player.pause();
}

function playNext() {
    if (songQueue.length === 0) return;
    currentSongIndex = (currentSongIndex + 1) % songQueue.length;
    searchAndPlay(songQueue[currentSongIndex]);
}

function playPrevious() {
    if (songQueue.length === 0) return;
    if (player.currentTime > 5) {
        player.currentTime = 0;
    } else {
        currentSongIndex = (currentSongIndex - 1 + songQueue.length) % songQueue.length;
        searchAndPlay(songQueue[currentSongIndex]);
    }
}

// Wake word detection system
let wakeWordRecognition = new speechRecognition();
wakeWordRecognition.continuous = true;
wakeWordRecognition.interimResults = true;
wakeWordRecognition.lang = 'en-US';
wakeWordRecognition.maxAlternatives = 3;

wakeWordRecognition.onresult = (event) => {
    for (let i = event.resultIndex; i < event.results.length; ++i) {
        const result = event.results[i];
        if (result.isFinal && result[0].confidence >= MIN_CONFIDENCE) {
            const alternatives = Array.from(result);
            
            for (const alt of alternatives) {
                if (WAKE_WORD_REGEX.test(alt.transcript)) {
                    console.log('Wake word detected:', alt.transcript, 'Confidence:', alt.confidence);
                    document.getElementById('wake-word-indicator').classList.add('detected');
                    setTimeout(() => {
                        document.getElementById('wake-word-indicator').classList.remove('detected');
                    }, 1000);

                    stopWakeWordDetection();
                    speak("I'm listening");
                    activateFullVoiceRecognition();
                    return;
                }
            }
        }
    }
};

wakeWordRecognition.onend = () => {
    if (isWakeWordListening) startWakeWordDetection();
};

function startWakeWordDetection() {
    try {
        wakeWordRecognition.start();
        isWakeWordListening = true;
        updateWakeWordIndicator();
    } catch (error) {
        setTimeout(startWakeWordDetection, 1000);
    }
}

function stopWakeWordDetection() {
    try {
        wakeWordRecognition.stop();
        isWakeWordListening = false;
        updateWakeWordIndicator();
    } catch (error) {
        console.error("Error stopping detection:", error);
    }
}

// Keyboard shortcut
document.addEventListener('keydown', (e) => {
    if (e.shiftKey && e.key.toLowerCase() === 'w') {
        isWakeWordListening ? stopWakeWordDetection() : startWakeWordDetection();
        speak(`Wake word detection ${isWakeWordListening ? 'deactivated' : 'activated'}`);
    }
});

// Utility function
function formatDisplayName(query) {
    return query.replace(/-/g, ' ')
               .replace(/\b\w/g, c => c.toUpperCase());
}

player.addEventListener('ended', () => {
    document.getElementById('now-playing').textContent = '';
    currentTimeElement.textContent = '0:00';
    durationElement.textContent = '0:00';
    speak("Music finished");
    playNext();
});