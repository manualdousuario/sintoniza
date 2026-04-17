<?php $this->layout('layout', ['title' => $title, 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <a href="/dashboard" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i></a>
        <?php if (isset($subscription->title)): ?>
            <h2 class="page-title flex-grow-1"><?= $this->e($subscription->title) ?></h2>
        <?php else: ?>
            <h2 class="page-title flex-grow-1"><?= $this->__('general.subscriptions') ?></h2>
        <?php endif ?>
    </div>

    <?php if (isset($subscription->title, $subscription->image_url)): ?>
        <div class="row mb-4">
            <div class="col-12 col-md-2">
                <img class="rounded w-100 h-auto border" width="150" height="150"
                    src="<?= $this->e($subscription->image_url) ?>">
            </div>
            <div class="col-12 col-md-10">
                <?php if (!empty($subscription->url)): ?>
                    <p><a href="<?= $this->e($subscription->url) ?>" class="link-dark" target="_blank"><?= $this->e($subscription->url) ?></a></p>
                <?php endif ?>
                <?php if (!empty($subscription->description)): ?>
                    <p><?= $this->format_description($subscription->description) ?></p>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>

    <h3 class="fs-5 fw-bold mb-3"><?= $this->__('general.episodes') ?></h3>

    <?php if (empty($episodes)): ?>
        <div class="alert alert-warning"><?= $this->__('dashboard.no_info') ?></div>
    <?php else: ?>
        <ul class="list-group mb-4">
            <?php foreach ($episodes as $ep):
                $epTitle = $ep->title ?? basename(strtok($ep->media_url, '?'));
                $epDuration = $ep->duration ? gmdate('H:i:s', (int) $ep->duration) : '—';
                $epDate = $ep->pubdate ? date('d/m/Y', strtotime($ep->pubdate)) : '';
                $epImage = !empty($ep->image_url)
                    ? '<div class="thumbnail"><img class="rounded border" src="' . $this->e($ep->image_url) . '" width="80" height="80" /></div>'
                    : '';

                if ($ep->last_action === 'play') {
                    $badge = '<span class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . $this->__('actions.played') . '</span>';
                } elseif ($ep->last_action === 'download') {
                    $badge = '<span class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . $this->__('actions.downloaded') . '</span>';
                } elseif ($ep->last_action === 'delete') {
                    $badge = '<span class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . $this->__('actions.deleted') . '</span>';
                } else {
                    $badge = '';
                }
            ?>
                <li class="list-group-item p-3">
                    <div class="episode_info d-flex gap-3">
                        <?= $epImage ?>
                        <div class="data flex-grow-1">
                            <h4 class="fs-6 mb-1">
                                <a class="link-dark"
                                    href="/subscription/<?= $subscription->subscription_id ?>/episode/<?= $ep->id ?>">
                                    <?= $this->e($epTitle) ?>
                                </a>
                            </h4>
                            <small class="text-muted">
                                <?= $this->__('general.duration') ?>: <?= $epDuration ?>
                                <?php if ($epDate): ?> &middot; <?= $epDate ?><?php endif ?>
                            </small>
                            <?php if ($badge): ?>
                                <div class="mt-1"><?= $badge ?></div>
                            <?php endif ?>
                        </div>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>

        <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="/subscription/<?= $subscription->subscription_id ?>?page=<?= $page - 1 ?>">&laquo;</a>
                        </li>
                    <?php endif ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link"
                                href="/subscription/<?= $subscription->subscription_id ?>?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor ?>

                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="/subscription/<?= $subscription->subscription_id ?>?page=<?= $page + 1 ?>">&raquo;</a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        <?php endif ?>
    <?php endif ?>

</div>
