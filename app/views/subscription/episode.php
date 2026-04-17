<?php $this->layout('layout', ['title' => $title, 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

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
        <a href="/subscription/<?= $subscription->subscription_id ?>"
            class="btn btn-danger"><?= $this->__('general.back') ?></a>
    </p>

    <div class="row mb-4">
        <?php if (!empty($episode->image_url)): ?>
            <div class="col-12 col-md-2">
                <img class="rounded w-100 h-auto border" width="150" height="150"
                    src="<?= $this->e($episode->image_url) ?>">
            </div>
        <?php endif ?>
        <div class="col-12 col-md-10">
            <h2 class="fs-3">
                <?= $this->e($episode->title ?? basename(strtok($episode->media_url, '?'))) ?>
            </h2>
            <p class="text-muted mb-1">
                <?= $this->__('general.duration') ?>:
                <?= $episode->duration ? gmdate('H:i:s', (int) $episode->duration) : '—' ?>
                <?php if ($episode->pubdate): ?>
                    &middot; <?= date('d/m/Y', strtotime($episode->pubdate)) ?>
                <?php endif ?>
            </p>
            <div class="card mb-4 shadow-sm" id="audio-player"
                data-episode-url="<?= $this->e($episode->media_url) ?>"
                data-podcast-url="<?= $this->e($podcastUrl) ?>"
                data-start-pos="<?= $lastPosition ?>"
                data-total-dur="<?= (int) ($episode->duration ?? 0) ?>">
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
                            <input type="range" class="form-range" id="player-progress" min="0" max="1000" value="0"
                                step="1" disabled>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2" id="player-status">Carregando áudio...</p>
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

    <h3 class="fs-4 mb-3"><?= $this->__('general.history') ?></h3>

    <?php if (empty($actions)): ?>
        <div class="alert alert-secondary"><?= $this->__('dashboard.no_info') ?></div>
    <?php else: ?>
        <ul class="list-group">
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
