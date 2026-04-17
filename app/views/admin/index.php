<?php $this->layout('layout', ['title' => $this->__('general.administration'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><i class="bi bi-shield-lock me-2"></i><?= $this->__('general.administration') ?></h2>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label"><i class="bi bi-people me-1"></i><?= $this->__('admin.users') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['subscriptions'] ?></div>
                <div class="stat-label"><i class="bi bi-bookmark-check me-1"></i><?= $this->__('admin.active_subscriptions') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['feeds'] ?></div>
                <div class="stat-label"><i class="bi bi-rss me-1"></i><?= $this->__('admin.feeds') ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['episodes'] ?></div>
                <div class="stat-label"><i class="bi bi-play-circle me-1"></i><?= $this->__('general.episodes') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">
                    <i class="bi bi-graph-up me-2"></i><?= $this->__('admin.last_7_days') ?>
                </div>
                <div class="stat-card card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <div class="stat-value"><?= $stats['subs_7d'] ?></div>
                    <div class="stat-label"><?= $this->__('admin.new_subscriptions') ?></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card h-100">
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
