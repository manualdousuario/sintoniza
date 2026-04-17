<?php $this->layout('layout', ['title' => $this->__('admin.edit_user'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <a href="/admin/users" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="page-title flex-grow-1"><?= $this->__('admin.edit_user') ?>: <?= $this->e($user->name) ?></h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
            <?= $this->e($message) ?>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header fw-semibold"><?= $this->__('admin.information') ?></div>
                <div class="card-body p-4">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label"><?= $this->__('general.username') ?></label>
                            <input type="text" class="form-control" value="<?= $this->e($user->name) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= $this->__('general.email') ?></label>
                            <input type="email" class="form-control" name="email" id="email"
                                value="<?= $this->e($user->email) ?>" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="admin" id="admin"
                                value="1" <?= ($user->admin ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="admin"><?= $this->__('admin.administrator') ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> <?= $this->__('general.save') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold"><?= $this->__('admin.account_status') ?></div>
                <div class="card-body">
                    <p class="mb-3">
                        <?= $this->__('admin.status') ?>:
                        <?php if ($user->active ?? 1): ?>
                            <span class="badge bg-success"><?= $this->__('admin.active') ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= $this->__('admin.inactive') ?></span>
                        <?php endif ?>
                    </p>
                    <?php if ($user->active ?? 1): ?>
                        <form method="post">
                            <input type="hidden" name="toggle_active" value="0">
                            <button type="submit" class="btn btn-warning btn-sm w-100"
                                    onclick="return confirm('<?= $this->e($this->__('admin.disable_account_confirm')) ?>')">
                                <i class="bi bi-pause-circle"></i> <?= $this->__('admin.disable') ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="toggle_active" value="1">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-play-circle"></i> <?= $this->__('admin.activate') ?>
                            </button>
                        </form>
                    <?php endif ?>
                </div>
            </div>

            <div class="card danger-zone">
                <div class="card-header"><?= $this->__('admin.danger_zone') ?></div>
                <div class="card-body">
                    <form method="post" onsubmit="return confirm('<?= $this->e($this->__('admin.delete_account_confirm')) ?>')">
                        <input type="hidden" name="delete_user" value="1">
                        <button type="submit" class="btn btn-danger btn-sm w-100">
                            <i class="bi bi-trash"></i> <?= $this->__('admin.delete_account') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
