<?php $this->layout('layout', ['title' => $this->__('admin.users'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
            <?= $this->e($message) ?>
        </div>
    <?php endif ?>

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><i class="bi bi-people me-2"></i><?= $this->__('admin.users') ?></h2>
        <a href="/admin/register-user" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus"></i> <?= $this->__('admin.new_user') ?>
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th><?= $this->__('admin.name') ?></th>
                        <th><?= $this->__('general.email') ?></th>
                        <th><?= $this->__('admin.admin') ?></th>
                        <th><?= $this->__('admin.status') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $u->id ?></td>
                            <td><?= $this->e($u->name) ?></td>
                            <td><?= $this->e($u->email) ?></td>
                            <td>
                                <?php if ($u->admin): ?>
                                    <span class="badge bg-danger"><?= $this->__('admin.admin') ?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($u->active ?? 1): ?>
                                    <span class="badge bg-success"><?= $this->__('admin.active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= $this->__('admin.inactive') ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-end">
                                <a href="/admin/user/<?= $u->id ?>" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-pencil"></i> <?= $this->__('admin.edit') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4"><?= $this->__('admin.no_users_found') ?></td>
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
                        <a class="page-link" href="/admin/users?page=<?= $page - 1 ?>">&laquo;</a>
                    </li>
                <?php endif ?>

                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="/admin/users?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor ?>

                <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="/admin/users?page=<?= $page + 1 ?>">&raquo;</a>
                    </li>
                <?php endif ?>
            </ul>
        </nav>
    <?php endif ?>

</div>
