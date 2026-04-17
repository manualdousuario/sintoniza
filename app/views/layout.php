<?php
use Sintoniza\Library\Language;

$fullTitle          = isset($title) ? TITLE . ' | ' . $title : TITLE;
$__lang             = Language::getInstance();
$__currentLang      = $__lang->getCurrentLanguage();
$__availableLangs   = $__lang->getAvailableLanguages();
?><!DOCTYPE html>
<html lang="<?= Language::getInstance()->getCurrentLanguage() ?>">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $this->e($fullTitle) ?></title>
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="<?= $this->e($fullTitle) ?>" />
    <meta name="description" content="<?= $this->e($this->__('general.site_description')) ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= BASE_URL ?>" />
    <meta property="og:title" content="<?= $this->e($fullTitle . ' - ' . $this->__('general.podcast_sync')) ?>" />
    <meta property="og:description" content="<?= $this->e($this->__('general.site_description')) ?>" />
    <meta property="og:image" content="/assets/opengraph.png" />
    <style><?= file_get_contents(APP_PATH . '/public/assets/css/styles.css') ?></style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom shadow">
        <div class="container">
            <div class="d-block d-md-flex">
                <div class="me-3">
                    <i class="bi bi-broadcast text-white fs-5 me-2"></i>
                    <a href="/" class="fs-4 fw-bold text-white text-decoration-none"><?= TITLE ?></a>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        title="<?= $this->e($this->__('general.language')) ?>">
                        <i class="bi bi-translate"></i>
                        <span class="d-none d-md-inline"><?= $this->e($__availableLangs[$__currentLang] ?? $__currentLang) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($__availableLangs as $__code => $__label): ?>
                            <li>
                                <form method="POST" action="/language" class="m-0">
                                    <input type="hidden" name="language" value="<?= $this->e($__code) ?>">
                                    <button type="submit" class="dropdown-item<?= $__code === $__currentLang ? ' active' : '' ?>">
                                        <?= $this->e($__label) ?>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
                <?php if ($isAdmin ?? false): ?>
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-lock"></i> Admin
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/admin"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/users"><i class="bi bi-people me-2"></i><?= $this->__('admin.users') ?></a></li>
                            <li><a class="dropdown-item" href="/admin/subscriptions"><i class="bi bi-rss me-2"></i><?= $this->__('admin.subscriptions') ?></a></li>
                            <li><a class="dropdown-item" href="/admin/register-user"><i class="bi bi-person-plus me-2"></i><?= $this->__('admin.register_user') ?></a></li>
                        </ul>
                    </div>
                <?php endif ?>
                <?php if (!($logged ?? false)): ?>
                    <a href="/login" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-box-arrow-in-right"></i> <?= $this->__('general.login') ?>
                    </a>
                    <a href="/register" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-person-plus"></i> <?= $this->__('general.register') ?>
                    </a>
                <?php else: ?>
                    <a href="/dashboard" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-mic-fill"></i> <?= $this->__('general.subscriptions') ?>
                    </a>
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            alt="<?= $this->__('general.profile') ?>" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/dashboard/profile/latest-updates">
                                <i class="bi bi-clock-history me-2"></i><?= $this->__('general.latest_updates') ?>
                            </a></li>
                            <li><a class="dropdown-item" href="/dashboard/profile/devices">
                                <i class="bi bi-phone me-2"></i><?= $this->__('general.devices') ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/dashboard/profile">
                                <i class="bi bi-gear me-2"></i><?= $this->__('general.profile') ?>
                            </a></li>
                        </ul>
                    </div>
                    <a href="/logout" class="btn btn-sm btn-outline-secondary me-2"
                        alt="<?= $this->__('general.logout') ?>"><i class="bi bi-door-closed"></i></a>
                <?php endif ?>
            </div>
        </div>
    </nav>

    <main>
        <?= $this->section('content') ?>
    </main>

    <footer class="py-4 border-top mt-auto">
        <div class="container">
            <p class="m-0"><?= $this->__('footer.with_love_by') ?> <a class="link-secondary"
                    href="https://altendorfme.com/" target="_blank">altendorfme</a>
        </div>
    </footer>

    <script><?= file_get_contents(APP_PATH . '/public/assets/js/scripts.js') ?></script>
</body>

</html>
