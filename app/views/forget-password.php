<?php $this->layout('layout', ['title' => $this->__('general.forgot_password'), 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="auth-section">
    <div class="container">

        <?php if ($message): ?>
            <div class="row justify-content-center mb-3">
                <div class="col-md-4">
                    <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
                        <?= $this->e($message) ?>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card auth-card">
                    <div class="card-body">
                        <form method="post" action="/forget-password">
                            <h2 class="card-title text-center mb-4"><?= $this->__('general.forgot_password') ?></h2>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= $this->__('general.email') ?></label>
                                <input type="email" class="form-control" required name="email" id="email" />
                            </div>
                            <div class="d-grid">
                                <button type="submit"
                                    class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                    <?= $this->__('general.send_reset_link') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>