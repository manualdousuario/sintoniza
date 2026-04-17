<?php $this->layout('layout', ['title' => $this->__('admin.subscriptions'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<?php
$buildUrl = function (array $overrides = []) use ($search, $active, $page): string {
    $params = array_filter([
        'q'      => $search !== '' ? $search : null,
        'active' => $active !== null ? (string) $active : null,
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
            <div class="col-12 col-md-7">
                <label for="feed-search" class="form-label small mb-1"><?= $this->__('admin.search_by_url_or_name') ?></label>
                <input type="search" class="form-control" id="feed-search" name="q"
                    value="<?= $this->e($search) ?>"
                    placeholder="<?= $this->e($this->__('admin.search_by_url_or_name_placeholder')) ?>">
            </div>
            <div class="col-8 col-md-3">
                <label for="feed-active" class="form-label small mb-1"><?= $this->__('admin.status') ?></label>
                <select class="form-select" id="feed-active" name="active">
                    <option value=""<?= $active === null ? ' selected' : '' ?>><?= $this->__('admin.all') ?></option>
                    <option value="1"<?= $active === 1 ? ' selected' : '' ?>><?= $this->__('admin.active') ?></option>
                    <option value="0"<?= $active === 0 ? ' selected' : '' ?>><?= $this->__('admin.inactive') ?></option>
                </select>
            </div>
            <div class="col-4 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> <?= $this->__('admin.filter') ?>
                </button>
                <?php if ($search !== '' || $active !== null): ?>
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
                        <th><?= $this->__('admin.status') ?></th>
                        <th></th>
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
                            <td>
                                <?php if ((int) $f->active === 1): ?>
                                    <span class="badge bg-success"><?= $this->__('admin.active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary" title="<?= $this->e($this->__('admin.fetch_failures')) ?>: <?= (int) $f->fetch_failures ?>">
                                        <?= $this->__('admin.inactive') ?>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td class="text-end">
                                <?php
                                $isActive = (int) $f->active === 1;
                                $toggleQs = http_build_query(array_filter([
                                    'q'      => $search !== '' ? $search : null,
                                    'active' => $active !== null ? (string) $active : null,
                                    'page'   => $page > 1 ? $page : null,
                                ], fn($v) => $v !== null && $v !== ''));
                                $action   = '/admin/subscription/' . (int) $f->id . '/toggle' . ($toggleQs ? '?' . $toggleQs : '');
                                $confirm  = $isActive
                                    ? $this->__('admin.disable_subscription_confirm')
                                    : $this->__('admin.enable_subscription_confirm');
                                ?>
                                <form method="post" action="<?= $this->e($action) ?>" class="m-0"
                                    onsubmit="return confirm('<?= $this->e($confirm) ?>');">
                                    <input type="hidden" name="active" value="<?= $isActive ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                        <?php if ($isActive): ?>
                                            <i class="bi bi-pause-circle"></i> <?= $this->__('admin.disable') ?>
                                        <?php else: ?>
                                            <i class="bi bi-play-circle"></i> <?= $this->__('admin.activate') ?>
                                        <?php endif ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($feeds)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4"><?= $this->__('admin.no_subscriptions_found') ?></td>
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
