<div class="container">
    <?php
    $lastPosition = 0;
    foreach ($actions as $action) {
        if ($action->action === 'play' && isset($action->position) && (int) $action->position > 0) {
            $lastPosition = (int) $action->position;
            break;
        }
    }
    $podcastUrl = $subscription->subscription_url ?? $subscription->feed_url ?? '';
    ?>
    <p>
        <a href="/subscription/<?php echo $subscription->subscription_id; ?>"
            class="btn btn-danger"><?php echo __('general.back'); ?></a>
    </p>

    <div class="row mb-4">
        <?php if (!empty($episode->image_url)): ?>
            <div class="col-12 col-md-2">
                <img class="rounded w-100 h-auto border" width="150" height="150"
                    src="<?php echo htmlspecialchars($episode->image_url); ?>">
            </div>
        <?php endif; ?>
        <div class="col-12 col-md-10">
            <h2 class="fs-3">
                <?php echo htmlspecialchars($episode->title ?? basename(strtok($episode->media_url, '?'))); ?></h2>
            <p class="text-muted mb-1">
                <?php echo __('general.duration'); ?>:
                <?php echo $episode->duration ? gmdate('H:i:s', (int) $episode->duration) : '—'; ?>
                <?php if ($episode->pubdate): ?>
                    &middot; <?php echo date('d/m/Y', strtotime($episode->pubdate)); ?>
                <?php endif; ?>
            </p>
            <a href="<?php echo htmlspecialchars($episode->media_url); ?>" target="_blank"
                class="btn btn-sm btn-secondary">
                <i class="bi bi-cloud-arrow-down-fill"></i> <?php echo __('general.download'); ?>
            </a>
            <?php if (!empty($episode->description)): ?>
                <div class="mt-3"><?php echo format_description($episode->description); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <button id="btn-play-pause"
                    class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:52px;height:52px" disabled aria-label="Play">
                    <i class="bi bi-play-fill fs-4" id="icon-play-pause"></i>
                </button>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="player-time-current">--:--</span>
                        <span id="player-time-total">--:--</span>
                    </div>
                    <input type="range" class="form-range" id="player-progress" min="0" max="1000" value="0" step="1"
                        disabled>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2" id="player-status">Carregando áudio...</p>
        </div>
    </div>

    <h3 class="fs-4 mb-3"><?php echo __('general.history'); ?></h3>

    <?php if (empty($actions)): ?>
        <div class="alert alert-secondary"><?php echo __('dashboard.no_info'); ?></div>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($actions as $row):
                if ($row->action === 'play') {
                    $actionBadge = '<div class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . __('actions.played') . '</div>';
                } elseif ($row->action === 'download') {
                    $actionBadge = '<div class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . __('actions.downloaded') . '</div>';
                } elseif ($row->action === 'delete') {
                    $actionBadge = '<div class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . __('actions.deleted') . '</div>';
                } else {
                    $actionBadge = '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . __('actions.unavailable') . '</div>';
                }

                $deviceBadge = $row->device_name
                    ? '<div class="badge text-bg-primary rounded-pill">' . htmlspecialchars($row->device_name) . '</div>'
                    : '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> Indisponível</div>';
                ?>
                <li class="list-group-item p-3">
                    <div class="meta">
                        <?php echo $actionBadge; ?>
                        <?php echo __('actions.from'); ?>         <?php echo $deviceBadge; ?>
                        <?php echo __('actions.on'); ?>
                        <small>
                            <time datetime="<?php echo date(DATE_ISO8601, $row->changed); ?>">
                                <?php echo date('d/m/Y', $row->changed); ?>         <?php echo __('actions.at'); ?>
                                <?php echo date('H:i', $row->changed); ?>
                            </time>
                        </small>
                        <?php if ($row->action === 'play' && !empty($row->position)): ?>
                            &middot; <small><?php echo gmdate('H:i:s', (int) $row->position); ?></small>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <script src="//cdn.jsdelivr.net/npm/howler@2.2.4/dist/howler.min.js"></script>
    <script>
        (function () {
            const EPISODE_URL = <?php echo json_encode($episode->media_url); ?>;
            const PODCAST_URL = <?php echo json_encode($podcastUrl); ?>;
            const START_POS = <?php echo json_encode($lastPosition); ?>;
            const TOTAL_DUR = <?php echo json_encode((int) ($episode->duration ?? 0)); ?>;
            const SYNC_EVERY = 15000;

            const btnPlay = document.getElementById('btn-play-pause');
            const iconPlay = document.getElementById('icon-play-pause');
            const progressEl = document.getElementById('player-progress');
            const timeCurrent = document.getElementById('player-time-current');
            const timeTotal = document.getElementById('player-time-total');
            const statusEl = document.getElementById('player-status');

            let syncTimer = null;
            let rafTimer = null;
            let startedAt = START_POS;
            let seekPending = false;

            function fmt(s) {
                if (!isFinite(s) || s < 0) return '--:--';
                s = Math.floor(s);
                const h = Math.floor(s / 3600);
                const m = Math.floor((s % 3600) / 60);
                const sec = s % 60;
                if (h > 0) return h + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
                return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
            }

            function syncAction(position) {
                const total = TOTAL_DUR > 0 ? TOTAL_DUR : Math.floor(sound.duration() || 0);
                const body = JSON.stringify([{
                    podcast: PODCAST_URL,
                    episode: EPISODE_URL,
                    action: 'play',
                    timestamp: new Date().toISOString().replace(/\.\d{3}/, ''),
                    position: Math.floor(position),
                    started: Math.floor(startedAt),
                    total: total
                }]);

                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/api/2/episodes/current.json', new Blob([body], { type: 'application/json' }));
                } else {
                    fetch('/api/2/episodes/current.json', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: body,
                        keepalive: true
                    }).catch(() => { });
                }
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
                const pos = sound.seek() || 0;
                const dur = sound.duration() || 0;
                timeCurrent.textContent = fmt(pos);
                if (dur > 0) progressEl.value = Math.round((pos / dur) * 1000);
                rafTimer = requestAnimationFrame(updateProgress);
            }

            function stopRaf() {
                if (rafTimer) { cancelAnimationFrame(rafTimer); rafTimer = null; }
            }

            const sound = new Howl({
                src: [EPISODE_URL],
                html5: true,
                preload: true,
                onload: function () {
                    const dur = sound.duration();
                    timeTotal.textContent = fmt(dur > 0 ? dur : TOTAL_DUR);
                    progressEl.disabled = false;
                    btnPlay.disabled = false;
                    statusEl.textContent = START_POS > 0
                        ? 'Retomando do ponto ' + fmt(START_POS)
                        : 'Pronto';
                    if (START_POS > 0) sound.seek(START_POS);
                },
                onloaderror: function () {
                    statusEl.textContent = 'Erro ao carregar o áudio.';
                },
                onplay: function () {
                    iconPlay.className = 'bi bi-pause-fill fs-4';
                    btnPlay.setAttribute('aria-label', 'Pause');
                    statusEl.textContent = 'Reproduzindo';
                    startSyncTimer();
                    rafTimer = requestAnimationFrame(updateProgress);
                },
                onpause: function () {
                    iconPlay.className = 'bi bi-play-fill fs-4';
                    btnPlay.setAttribute('aria-label', 'Play');
                    statusEl.textContent = 'Pausado';
                    stopSyncTimer();
                    stopRaf();
                    syncAction(sound.seek() || 0);
                },
                onstop: function () {
                    iconPlay.className = 'bi bi-play-fill fs-4';
                    btnPlay.setAttribute('aria-label', 'Play');
                    statusEl.textContent = 'Parado';
                    stopSyncTimer();
                    stopRaf();
                },
                onend: function () {
                    iconPlay.className = 'bi bi-play-fill fs-4';
                    btnPlay.setAttribute('aria-label', 'Play');
                    statusEl.textContent = 'Concluído';
                    progressEl.value = 1000;
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
                const dur = sound.duration();
                if (!dur) return;
                seekPending = true;
                sound.seek((progressEl.value / 1000) * dur);
                timeCurrent.textContent = fmt((progressEl.value / 1000) * dur);
            });

            window.addEventListener('beforeunload', function () {
                if (sound.playing() || (sound.seek() || 0) > 0) {
                    syncAction(sound.seek() || 0);
                }
                stopSyncTimer();
                stopRaf();
            });
        }());
    </script>

</div>