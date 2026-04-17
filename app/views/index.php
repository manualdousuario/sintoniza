<?php $this->layout('layout', ['logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'home']) ?>

<div class="hero-section mb-4 px-3">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="hero-title">
                    <?= $this->__('home.hero_title_1') ?><br>
                    <span><?= $this->__('home.hero_title_2') ?></span>
                </h1>
                <p class="hero-subtitle">
                    <?= TITLE ?> <?= $this->__('home.hero_subtitle') ?>
                </p>
                <div class="hero-cta d-flex flex-wrap gap-3">
                    <a href="/register" class="btn-primary-custom">
                        <i class="bi bi-person-plus me-2"></i><?= $this->__('general.register') ?>
                    </a>
                    <a href="/login" class="btn-outline-custom">
                        <i class="bi bi-box-arrow-in-right me-2"></i><?= $this->__('general.login') ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-5 offset-lg-1 d-none d-lg-block">
                <div class="hero-visual">
                    <div class="device-card">
                        <div class="device-card-header">
                            <div class="device-dot" style="background:#f87171"></div>
                            <div class="device-dot" style="background:#fbbf24"></div>
                            <div class="device-dot" style="background:#34d399"></div>
                            <span style="color:#64748b;font-size:0.78rem;margin-left:0.5rem;"><?= TITLE ?> —
                                <?= $this->__('home.syncing_header') ?></span>
                        </div>
                        <?php
                        $syncItems = [
                            ['icon' => 'bi-phone', 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.15)', 'label' => 'AntennaPod (Android)', 'status' => $this->__('home.synced'), 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-laptop', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.15)', 'label' => 'gPodder (Desktop)', 'status' => $this->__('home.synced'), 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-display', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.15)', 'label' => 'Cardo (Windows)', 'status' => $this->__('home.synced'), 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-display', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.15)', 'label' => 'YourPods (iOS)', 'status' => $this->__('home.synced'), 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-tablet', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.15)', 'label' => 'Kasts (Linux)', 'status' => $this->__('home.syncing'), 'statusClass' => 'text-warning', 'statusBg' => 'rgba(251,191,36,0.12)'],
                        ];
                        foreach ($syncItems as $item): ?>
                            <div class="sync-item">
                                <div class="sync-icon" style="background:<?= $item['bg'] ?>">
                                    <i class="bi <?= $item['icon'] ?>"
                                        style="color:<?= $item['color'] ?>"></i>
                                </div>
                                <span class="sync-label"><?= $item['label'] ?></span>
                                <span class="sync-status <?= $item['statusClass'] ?>"
                                    style="background:<?= $item['statusBg'] ?>">
                                    <?= $item['status'] ?>
                                </span>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <div class="mt-3 p-3 rounded-3"
                        style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <div class="d-flex align-items-center justify-content-between">
                            <span style="color:#64748b;font-size:0.78rem;"><i class="bi bi-clock me-1"></i><?= $this->__('home.last_sync_label') ?></span>
                            <span style="color:#34d399;font-size:0.78rem;font-weight:600;"><?= $this->__('home.just_now') ?></span>
                        </div>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;"><?= $this->__('home.hero_stat_subscriptions') ?></span>
                            <span style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;"><?= $this->__('home.hero_stat_episodes') ?></span>
                            <span style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;"><?= $this->__('home.hero_stat_devices') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="mb-4">
        <div class="text-center mb-4">
            <h2 class="section-title"><?= sprintf($this->__('home.why_use'), TITLE) ?></h2>
        </div>
        <div class="row g-3">
            <?php
            $features = [
                ['icon' => 'bi-arrow-repeat', 'color' => '#6366f1', 'bg' => '#eef2ff', 'title' => $this->__('home.feature_realtime_title'), 'desc' => $this->__('home.feature_realtime_desc')],
                ['icon' => 'bi-shield-check', 'color' => '#10b981', 'bg' => '#f0fdf4', 'title' => $this->__('home.feature_privacy_title'), 'desc' => $this->__('home.feature_privacy_desc')],
                ['icon' => 'bi-plug', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'title' => $this->__('home.feature_gpodder_title'), 'desc' => $this->__('home.feature_gpodder_desc')],
                ['icon' => 'bi-phone', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'title' => $this->__('home.feature_multidevice_title'), 'desc' => $this->__('home.feature_multidevice_desc')],
                ['icon' => 'bi-graph-up', 'color' => '#ef4444', 'bg' => '#fef2f2', 'title' => $this->__('home.feature_history_title'), 'desc' => $this->__('home.feature_history_desc')],
                ['icon' => 'bi-code-slash', 'color' => '#0ea5e9', 'bg' => '#f0f9ff', 'title' => $this->__('home.feature_opensource_title'), 'desc' => $this->__('home.feature_opensource_desc')],
            ];
            foreach ($features as $f): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:<?= $f['bg'] ?>">
                            <i class="bi <?= $f['icon'] ?>" style="color:<?= $f['color'] ?>"></i>
                        </div>
                        <div class="feature-title"><?= $f['title'] ?></div>
                        <p class="feature-desc"><?= $f['desc'] ?></p>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <div class="mb-4 py-4 px-4 rounded-4"
        style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;">
        <div class="row align-items-center g-4">
            <div class="col-lg-4">
                <div class="section-label"><?= $this->__('home.how_it_works') ?></div>
                <h2 class="section-title"><?= $this->__('home.setup_title') ?></h2>
                <p class="section-subtitle" style="font-size:0.92rem;"><?= $this->__('home.setup_subtitle') ?></p>
            </div>
            <div class="col-lg-8">
                <div class="d-flex flex-column gap-3">
                    <?php
                    $steps = [
                        ['n' => '1', 'title' => $this->__('home.step_1_title'), 'desc' => $this->__('home.step_1_desc')],
                        ['n' => '2', 'title' => $this->__('home.step_2_title'), 'desc' => $this->__('home.step_2_desc')],
                        ['n' => '3', 'title' => $this->__('home.step_3_title'), 'desc' => $this->__('home.step_3_desc')],
                    ];
                    foreach ($steps as $s): ?>
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 border">
                            <div class="step-number flex-shrink-0"><?= $s['n'] ?></div>
                            <div>
                                <div style="font-weight:700;color:#1e293b;font-size:0.95rem;"><?= $s['title'] ?></div>
                                <div style="font-size:0.85rem;color:#64748b;margin-top:0.2rem;"><?= $s['desc'] ?></div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="text-center mb-4">
            <h2 class="section-title"><?= $this->__('home.compatible_apps') ?></h2>
        </div>
        <div class="row g-3">
            <?php
            $clients = [
                ['name' => 'AntennaPod', 'version' => '3.5.0', 'url' => 'https://github.com/AntennaPod/AntennaPod', 'icon' => 'bi-phone-fill', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'platforms' => [['icon' => 'bi-android2', 'label' => 'Android']]],
                ['name' => 'Cardo', 'version' => '1.90', 'url' => 'https://cardo-podcast.github.io/', 'icon' => 'bi-display-fill', 'color' => '#10b981', 'bg' => '#f0fdf4', 'platforms' => [['icon' => 'bi-windows', 'label' => 'Windows'], ['icon' => 'bi-apple', 'label' => 'macOS'], ['icon' => 'bi-ubuntu', 'label' => 'Linux']]],
                ['name' => 'Kasts', 'version' => '21.88', 'url' => 'https://invent.kde.org/multimedia/kasts', 'icon' => 'bi-collection-play-fill', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'platforms' => [['icon' => 'bi-windows', 'label' => 'Windows'], ['icon' => 'bi-android2', 'label' => 'Android'], ['icon' => 'bi-ubuntu', 'label' => 'Linux']]],
                ['name' => 'YourPods', 'version' => '2.0.2', 'url' => 'https://apps.apple.com/us/app/yourpods-podcast-player/id6757721236', 'icon' => 'bi-play-circle', 'color' => '#1f1c5d', 'bg' => '#cbfbf3', 'platforms' => [['icon' => 'bi-apple', 'label' => 'iOS']]],
            ];
            foreach ($clients as $c): ?>
                <div class="col-sm-3">
                    <a href="<?= $c['url'] ?>" target="_blank" class="text-decoration-none">
                        <div class="client-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="client-icon" style="background:<?= $c['bg'] ?>">
                                    <i class="bi <?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i>
                                </div>
                                <div>
                                    <div class="client-name"><?= $c['name'] ?></div>
                                    <div class="client-version">v<?= $c['version'] ?></div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($c['platforms'] as $p): ?>
                                    <span class="platform-badge"><i class="bi <?= $p['icon'] ?>"></i><?= $p['label'] ?></span>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
    </div>

</div>
