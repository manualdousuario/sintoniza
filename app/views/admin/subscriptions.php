<?php $this->layout('layout', ['title' => $this->__('admin.subscriptions'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<?php
$buildUrl = function (array $overrides = []) use ($search, $page): string {
    $params = array_filter([
        'q'      => $search !== '' ? $search : null,
        'page'   => $page > 1 ? $page : null,
    ], fn($v) => $v !== null);
    $params = array_merge($params, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return '/admin/subscriptions' . ($params ? '?' . http_build_query($params) : '');
};
?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><?= $this->__('admin.subscriptions') ?></h2>
        <span class="badge bg-secondary"><?= $total ?></span>
    </div>

    <form method="get" action="/admin/subscriptions" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-9">
                <label for="feed-search" class="form-label small mb-1"><?= $this->__('admin.search_by_url_or_name') ?></label>
                <input type="search" class="form-control" id="feed-search" name="q"
                    value="<?= $this->e($search) ?>"
                    placeholder="<?= $this->e($this->__('admin.search_by_url_or_name_placeholder')) ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> <?= $this->__('admin.filter') ?>
                </button>
                <?php if ($search !== ''): ?>
                    <a href="/admin/subscriptions" class="btn btn-outline-secondary" title="<?= $this->e($this->__('admin.clear_filters')) ?>">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif ?>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th><?= $this->__('admin.name') ?></th>
                        <th><?= $this->__('admin.feed_url') ?></th>
                        <th class="text-end"><?= $this->__('admin.subscribers') ?></th>
                        <th><?= $this->__('admin.last_fetch') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeds as $f): ?>
                        <tr>
                            <td class="text-muted small"><?= $f->id ?></td>
                            <td class="text-truncate" style="max-width: 260px;">
                                <?php if (!empty($f->url)): ?>
                                    <a href="<?= $this->e($f->url) ?>" target="_blank" rel="noopener" class="link-secondary">
                                        <?= $this->e($f->title ?: $f->feed_url) ?>
                                    </a>
                                <?php else: ?>
                                    <?= $this->e($f->title ?: $f->feed_url) ?>
                                <?php endif ?>
                            </td>
                            <td class="text-truncate small text-muted" style="max-width: 320px;">
                                <a href="<?= $this->e($f->feed_url) ?>" target="_blank" rel="noopener" class="link-secondary">
                                    <?= $this->e($f->feed_url) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-primary rounded-pill"><?= (int) $f->subscribers ?></span>
                            </td>
                            <td class="small text-muted">
                                <?php if ((int) $f->last_fetch > 0): ?>
                                    <?= date('Y-m-d H:i', (int) $f->last_fetch) ?>
                                <?php else: ?>
                                    &mdash;
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($feeds)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4"><?= $this->__('admin.no_subscriptions_found') ?></td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $this->e($buildUrl(['page' => $page - 1])) ?>">&laquo;</a>
                    </li>
                <?php endif ?>

                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $this->e($buildUrl(['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor ?>

                <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $this->e($buildUrl(['page' => $page + 1])) ?>">&raquo;</a>
                    </li>
                <?php endif ?>
            </ul>
        </nav>
    <?php endif ?>

</div>
