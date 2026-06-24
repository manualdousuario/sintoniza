<?php $this->layout('layout', ['title' => $this->__('dashboard.add_podcast'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><?= $this->__('dashboard.add_podcast') ?></h2>
        <a href="/dashboard" class="btn btn-sm btn-secondary"><?= $this->__('general.subscriptions') ?></a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-4" role="alert"><?= $this->e($error) ?></div>
    <?php endif ?>

    <?php if ($searchEnabled): ?>
        <form method="get" action="/dashboard/add" class="mt-4 mb-4">
            <div class="input-group">
                <input type="text" name="q" class="form-control" value="<?= $this->e($query) ?>" placeholder="<?= $this->__('dashboard.search_placeholder') ?>" autofocus>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> <?= $this->__('dashboard.search') ?></button>
            </div>
        </form>
    <?php endif ?>

    <?php if ($query !== ''): ?>
        <?php if (empty($results)): ?>
            <div class="alert alert-warning"><?= $this->__('dashboard.no_results') ?></div>
        <?php else: ?>
            <ul class="list-group mb-4">
                <?php foreach ($results as $feed):
                    $feedUrl = $feed['url'] ?? '';
                    if ($feedUrl === '') continue;
                    $image = $feed['image'] ?? $feed['artwork'] ?? '';
                    $title = $feed['title'] ?? $feedUrl;
                ?>
                    <li class="list-group-item p-3">
                        <div class="episode_info d-flex gap-3 align-items-center">
                            <?php if ($image): ?>
                                <div class="thumbnail"><img class="rounded border h-auto" src="<?= $this->e($image) ?>" width="80" /></div>
                            <?php endif ?>
                            <div class="data flex-grow-1">
                                <h2 class="fs-5 mb-1"><?= $this->e($title) ?></h2>
                                <?php if (!empty($feed['author'])): ?>
                                    <small class="d-block text-muted"><?= $this->e($feed['author']) ?></small>
                                <?php endif ?>
                                <?php if (!empty($feed['description'])): ?>
                                    <small class="d-block"><?= $this->format_description($feed['description']) ?></small>
                                <?php endif ?>
                            </div>
                            <form method="post" action="/dashboard/subscribe">
                                <input type="hidden" name="url" value="<?= $this->e($feedUrl) ?>">
                                <button type="submit" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> <?= $this->__('dashboard.subscribe') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    <?php endif ?>

    <hr>

    <h3 class="fs-5 mt-4"><?= $this->__('dashboard.add_by_url') ?></h3>
    <form method="post" action="/dashboard/subscribe" class="mt-3">
        <div class="input-group">
            <input type="url" name="url" class="form-control" placeholder="https://exemplo.com/feed.xml" required>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= $this->__('dashboard.subscribe') ?></button>
        </div>
    </form>

</div>
