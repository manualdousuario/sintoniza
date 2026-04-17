(function () {
    var playerEl = document.getElementById('audio-player');
    if (!playerEl) return;

    var EPISODE_URL = playerEl.dataset.episodeUrl;
    var PODCAST_URL = playerEl.dataset.podcastUrl;
    var START_POS   = parseInt(playerEl.dataset.startPos, 10)  || 0;
    var TOTAL_DUR   = parseInt(playerEl.dataset.totalDur, 10)  || 0;
    var SYNC_EVERY  = 15000;
    var DEVICE_ID   = 'web';

    var I18N = {};
    try { I18N = JSON.parse(playerEl.dataset.i18n || '{}'); } catch (e) {}
    function t(key, arg) {
        var s = I18N[key] || '';
        return arg != null ? s.replace('%s', arg) : s;
    }

    var devicePromise = null;
    function ensureDevice() {
        if (devicePromise) return devicePromise;
        devicePromise = fetch('/api/2/devices/' + DEVICE_ID + '.json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ caption: 'Web', type: 'desktop' })
        }).catch(function () {});
        return devicePromise;
    }

    var btnPlay    = document.getElementById('btn-play-pause');
    var iconPlay   = document.getElementById('icon-play-pause');
    var progressEl = document.getElementById('player-progress');
    var timeCurrent = document.getElementById('player-time-current');
    var timeTotal   = document.getElementById('player-time-total');
    var statusEl    = document.getElementById('player-status');

    var syncTimer  = null;
    var rafTimer   = null;
    var startedAt  = START_POS;
    var seekPending = false;

    ensureDevice();

    function fmt(s) {
        if (!isFinite(s) || s < 0) return '--:--';
        s = Math.floor(s);
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        if (h > 0) return h + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
        return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }

    function sendAction(body) {
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/2/episodes/current.json', new Blob([body], { type: 'application/json' }));
        } else {
            fetch('/api/2/episodes/current.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: body,
                keepalive: true
            }).catch(function () {});
        }
    }

    function syncAction(position) {
        var total = TOTAL_DUR > 0 ? TOTAL_DUR : Math.floor(sound.duration() || 0);
        var body  = JSON.stringify([{
            podcast:   PODCAST_URL,
            episode:   EPISODE_URL,
            device:    DEVICE_ID,
            action:    'play',
            timestamp: new Date().toISOString().replace(/\.\d{3}/, ''),
            position:  Math.floor(position),
            started:   Math.floor(startedAt),
            total:     total
        }]);

        ensureDevice().then(function () {
            sendAction(body);
        });
    }

    function startSyncTimer() {
        stopSyncTimer();
        syncTimer = setInterval(function () {
            syncAction(sound.seek() || 0);
        }, SYNC_EVERY);
    }

    function stopSyncTimer() {
        if (syncTimer) { clearInterval(syncTimer); syncTimer = null; }
    }

    function updateProgress() {
        if (!sound.playing()) return;
        var pos = sound.seek() || 0;
        var dur = sound.duration() || 0;
        timeCurrent.textContent = fmt(pos);
        if (dur > 0) progressEl.value = Math.round((pos / dur) * 1000);
        rafTimer = requestAnimationFrame(updateProgress);
    }

    function stopRaf() {
        if (rafTimer) { cancelAnimationFrame(rafTimer); rafTimer = null; }
    }

    var sound = new Howl({
        src:     [EPISODE_URL],
        html5:   true,
        preload: true,
        onload: function () {
            var dur = sound.duration();
            timeTotal.textContent = fmt(dur > 0 ? dur : TOTAL_DUR);
            progressEl.disabled   = false;
            btnPlay.disabled      = false;
            statusEl.textContent  = START_POS > 0
                ? t('resuming', fmt(START_POS))
                : t('ready');
            if (START_POS > 0) sound.seek(START_POS);
        },
        onloaderror: function () {
            statusEl.textContent = t('load_error');
        },
        onplay: function () {
            iconPlay.className   = 'bi bi-pause-fill fs-4';
            btnPlay.setAttribute('aria-label', t('pause'));
            statusEl.textContent = t('playing');
            startSyncTimer();
            rafTimer = requestAnimationFrame(updateProgress);
        },
        onpause: function () {
            iconPlay.className   = 'bi bi-play-fill fs-4';
            btnPlay.setAttribute('aria-label', t('play'));
            statusEl.textContent = t('paused');
            stopSyncTimer();
            stopRaf();
            syncAction(sound.seek() || 0);
        },
        onstop: function () {
            iconPlay.className   = 'bi bi-play-fill fs-4';
            btnPlay.setAttribute('aria-label', t('play'));
            statusEl.textContent = t('stopped');
            stopSyncTimer();
            stopRaf();
        },
        onend: function () {
            iconPlay.className      = 'bi bi-play-fill fs-4';
            btnPlay.setAttribute('aria-label', t('play'));
            statusEl.textContent    = t('ended');
            progressEl.value        = 1000;
            timeCurrent.textContent = timeTotal.textContent;
            stopSyncTimer();
            stopRaf();
            syncAction(sound.duration() || TOTAL_DUR);
        },
        onseek: function () {
            if (seekPending) {
                seekPending = false;
                startedAt = sound.seek() || 0;
            }
        }
    });

    btnPlay.addEventListener('click', function () {
        if (sound.playing()) {
            sound.pause();
        } else {
            startedAt = sound.seek() || 0;
            sound.play();
        }
    });

    progressEl.addEventListener('input', function () {
        var dur = sound.duration();
        if (!dur) return;
        timeCurrent.textContent = fmt((progressEl.value / 1000) * dur);
    });

    progressEl.addEventListener('change', function () {
        var dur = sound.duration();
        if (!dur) return;
        seekPending = true;
        sound.seek((progressEl.value / 1000) * dur);
    });

    window.addEventListener('beforeunload', function () {
        if (sound.playing() || (sound.seek() || 0) > 0) {
            syncAction(sound.seek() || 0);
        }
        stopSyncTimer();
        stopRaf();
    });
}());
