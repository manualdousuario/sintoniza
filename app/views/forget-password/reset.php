<?php $this->layout('layout', ['title' => $this->__('general.reset_password'), 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
            <?= $this->e($message) ?>
        </div>
        <?php if (($messageType ?? 'success') === 'success'): ?>
            <a href="/login" class="btn btn-primary"><?= $this->__('general.login') ?></a>
        <?php endif ?>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
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
                                    <i class="bi bi-lock-fill"></i> <?= $this->__('general.reset_password') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

</div>
