<?php $this->layout('layout', ['title' => $title, 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

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
    $playerI18n = [
        'loading'    => $this->__('player.loading'),
        'resuming'   => $this->__('player.resuming'),
        'ready'      => $this->__('player.ready'),
        'load_error' => $this->__('player.load_error'),
        'playing'    => $this->__('player.playing'),
        'paused'     => $this->__('player.paused'),
        'stopped'    => $this->__('player.stopped'),
        'ended'      => $this->__('player.ended'),
        'play'       => $this->__('player.play'),
        'pause'      => $this->__('player.pause'),
    ];
    ?>

    <div class="page-header d-flex gap-2 align-items-center">
        <a href="/subscription/<?= $subscription->subscription_id ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="page-title flex-grow-1"><i class="bi bi-play-circle me-2"></i><?= $this->e($episode->title ?? basename(strtok($episode->media_url, '?'))) ?></h2>
    </div>

    <div class="row mb-4">
        <?php if (!empty($episode->image_url)): ?>
            <div class="col-12 col-md-2">
                <img class="rounded w-100 h-auto border" width="150" height="150"
                    src="<?= $this->e($episode->image_url) ?>">
            </div>
        <?php endif ?>
        <div class="col-12 col-md-10">
            <p class="text-muted mb-1">
                <?= $this->__('general.duration') ?>:
                <?= $episode->duration ? gmdate('H:i:s', (int) $episode->duration) : '—' ?>
                <?php if ($episode->pubdate): ?>
                    &middot; <?= date('d/m/Y', strtotime($episode->pubdate)) ?>
                <?php endif ?>
            </p>
            <div class="card mb-4" id="audio-player"
                data-episode-url="<?= $this->e($episode->media_url) ?>"
                data-podcast-url="<?= $this->e($podcastUrl) ?>"
                data-start-pos="<?= $lastPosition ?>"
                data-total-dur="<?= (int) ($episode->duration ?? 0) ?>"
                data-i18n="<?= $this->e(json_encode($playerI18n, JSON_UNESCAPED_UNICODE)) ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <button id="btn-play-pause"
                            class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:52px;height:52px" disabled aria-label="<?= $this->e($playerI18n['play']) ?>">
                            <i class="bi bi-play-fill fs-4" id="icon-play-pause"></i>
                        </button>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span id="player-time-current">--:--</span>
                                <span id="player-time-total">--:--</span>
                            </div>
                            <input type="range" class="form-range" id="player-progress" min="0" max="1000" value="0"
                                step="1" disabled>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2" id="player-status"><?= $this->e($playerI18n['loading']) ?></p>
                </div>
            </div>
            <a href="<?= $this->e($episode->media_url) ?>" target="_blank"
                class="btn btn-sm btn-secondary">
                <i class="bi bi-cloud-arrow-down-fill"></i> <?= $this->__('general.download') ?>
            </a>
            <?php if (!empty($episode->description)): ?>
                <div class="mt-3"><?= $this->format_description($episode->description) ?></div>
            <?php endif ?>
        </div>
    </div>

    <h3 class="fs-5 fw-bold mb-3"><?= $this->__('general.history') ?></h3>

    <?php if (empty($actions)): ?>
        <div class="alert alert-primary mb-4"><?= $this->__('dashboard.no_info') ?></div>
    <?php else: ?>
        <ul class="list-group mb-4">
            <?php foreach ($actions as $row):
                if ($row->action === 'play') {
                    $actionBadge = '<div class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . $this->__('actions.played') . '</div>';
                } elseif ($row->action === 'download') {
                    $actionBadge = '<div class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . $this->__('actions.downloaded') . '</div>';
                } elseif ($row->action === 'delete') {
                    $actionBadge = '<div class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . $this->__('actions.deleted') . '</div>';
                } else {
                    $actionBadge = '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . $this->__('actions.unavailable') . '</div>';
                }

                $deviceBadge = $row->device_name
                    ? '<div class="badge text-bg-primary rounded-pill">' . $this->e($row->device_name) . '</div>'
                    : '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> Indisponível</div>';
            ?>
                <li class="list-group-item p-3">
                    <div class="meta">
                        <?= $actionBadge ?>
                        <?= $this->__('actions.from') ?> <?= $deviceBadge ?>
                        <?= $this->__('actions.on') ?>
                        <small>
                            <time datetime="<?= date(DATE_ISO8601, $row->changed) ?>">
                                <?= date('d/m/Y', $row->changed) ?> <?= $this->__('actions.at') ?>
                                <?= date('H:i', $row->changed) ?>
                            </time>
                        </small>
                        <?php if ($row->action === 'play' && !empty($row->position)): ?>
                            &middot; <small><?= gmdate('H:i:s', (int) $row->position) ?></small>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

</div>
