<?php $this->layout('layout', ['title' => $this->__('general.reset_password'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'auth']) ?>

<div class="auth-section">
    <div class="container">

        <?php if ($message): ?>
            <div class="row justify-content-center mb-3">
                <div class="col-md-6 col-lg-4">
                    <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
                        <?= $this->e($message) ?>
                    </div>
                    <?php if (($messageType ?? 'success') === 'success'): ?>
                        <a href="/login" class="btn btn-primary w-100 mt-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= $this->__('general.login') ?>
                        </a>
                    <?php endif ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="auth-brand">
                        <div class="auth-brand-icon">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <span><?= TITLE ?></span>
                    </div>
                    <div class="card auth-card">
                        <div class="card-body">
                            <form method="post" action="">
                                <h2 class="card-title text-center mb-4"><?= $this->__('general.reset_password') ?></h2>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label"><?= $this->__('general.new_password') ?></label>
                                    <input type="password" class="form-control" minlength="8" required name="new_password"
                                        id="new_password" />
                                </div>
                                <div class="d-grid">
                                    <button type="submit"
                                        class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-lock-fill"></i><?= $this->__('general.reset_password') ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

    </div>
</div>
