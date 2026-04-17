<?php $this->layout('layout', ['title' => $this->__('general.administration'), 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fs-3 m-0"><i class="bi bi-shield-lock me-2"></i><?= $this->__('general.administration') ?></h2>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-primary"><?= $stats['users'] ?></div>
                <div class="text-muted small"><i class="bi bi-people me-1"></i><?= $this->__('admin.users') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-success"><?= $stats['subscriptions'] ?></div>
                <div class="text-muted small"><i class="bi bi-bookmark-check me-1"></i><?= $this->__('admin.active_subscriptions') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-warning"><?= $stats['feeds'] ?></div>
                <div class="text-muted small"><i class="bi bi-rss me-1"></i><?= $this->__('admin.feeds') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-info"><?= $stats['episodes'] ?></div>
                <div class="text-muted small"><i class="bi bi-play-circle me-1"></i><?= $this->__('general.episodes') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-graph-up me-2"></i><?= $this->__('admin.last_7_days') ?>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <div class="fs-1 fw-bold text-success"><?= $stats['subs_7d'] ?></div>
                    <div class="text-muted"><?= $this->__('admin.new_subscriptions') ?></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-bar-chart me-2"></i><?= $this->__('admin.top_feeds') ?>
                </div>
                <?php if (empty($topFeeds)): ?>
                    <div class="card-body text-muted"><?= $this->__('admin.no_data') ?></div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topFeeds as $i => $feed): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="text-truncate me-2" style="max-width: 80%;">
                                    <span class="text-muted me-2 small"><?= $i + 1 ?>.</span>
                                    <?= $this->e($feed->title ?: $feed->feed_url) ?>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= $feed->subscribers ?></span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>
    </div>

</div>
