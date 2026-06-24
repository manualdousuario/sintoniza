<?php $this->layout('layout', ['title' => $this->__('general.subscriptions'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><?= $this->__('general.subscriptions') ?></h2>
        <a href="/dashboard/add" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> <?= $this->__('dashboard.add_podcast') ?></a>
        <a href="/subscriptions/<?= $this->e($userName) ?>.opml" target="_blank" class="btn btn-sm btn-secondary"><i class="bi bi-rss-fill"></i></a>
    </div>

    <?php if ($okToken): ?>
        <div class="alert alert-success mt-4" role="alert">Você está logado, pode fechar isso e voltar para o aplicativo.</div>
    <?php endif ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success mt-4" role="alert"><?= $this->e($success) ?></div>
    <?php endif ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-4" role="alert"><?= $this->e($error) ?></div>
    <?php endif ?>

    <?php if (empty($subscriptions)): ?>
        <div class="alert alert-warning"><?= $this->__('dashboard.no_info') ?></div>
    <?php else: ?>
        <ul class="list-group mb-4">
            <?php foreach ($subscriptions as $row):
                $image_url = !empty($row->image_url) ? '<div class="thumbnail"><img class="rounded border h-auto" src="' . $this->e($row->image_url) . '" width="80" /></div>' : '';
                $title = $row->title ?? str_replace(['http://', 'https://'], '', $row->url);
            ?>
                <li class="list-group-item p-3">
                    <div class="episode_info d-flex gap-3 align-items-start">
                        <?= $image_url ?>
                        <div class="data flex-grow-1">
                            <h2 class="fs-5"><a class="link-dark" href="/subscription/<?= $row->id ?>"><?= $this->e($title) ?></a></h2>
                            <small class="d-block"><?= $this->format_description($row->description) ?></small>
                            <small><strong><?= $this->__('dashboard.last_update') ?></strong>: <time datetime="<?= date(DATE_ISO8601, $row->last_change) ?>" class="text-nowrap"><?= date('d/m/Y \à\s H:i', $row->last_change) ?></time></small>
                        </div>
                        <form method="post" action="/dashboard/unsubscribe" onsubmit="return confirm('<?= $this->__('dashboard.unsubscribe_confirm') ?>');">
                            <input type="hidden" name="id" value="<?= $row->id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap" title="<?= $this->__('dashboard.unsubscribe') ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>

        <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/dashboard?page=<?= $page - 1 ?>">&laquo;</a>
                        </li>
                    <?php endif ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/dashboard?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor ?>

                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/dashboard?page=<?= $page + 1 ?>">&raquo;</a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        <?php endif ?>
    <?php endif ?>

</div>
