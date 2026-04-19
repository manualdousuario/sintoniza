<?php $this->layout('layout', ['title' => $this->__('general.login'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'auth']) ?>

<div class="auth-section">
    <div class="container">

        <?php if (!empty($success)): ?>
            <div class="row justify-content-center mb-3">
                <div class="col-md-4">
                    <div class="alert alert-success" role="alert"><?= $this->e($success) ?></div>
                </div>
            </div>
        <?php endif ?>

        <?php if ($error): ?>
            <div class="row justify-content-center mb-3">
                <div class="col-md-4">
                    <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
                </div>
            </div>
        <?php endif ?>

        <?php if ($hasToken): ?>
            <div class="row justify-content-center mb-3">
                <div class="col-md-4">
                    <div class="alert alert-warning" role="alert"><?= $this->__('messages.app_requesting_access') ?></div>
                </div>
            </div>
        <?php endif ?>

        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card auth-card">
                    <div class="card-body">
                        <form method="post" action="">
                            <h2 class="card-title text-center mb-4"><?= $this->__('general.login') ?></h2>
                            <div class="mb-3">
                                <label for="login" class="form-label"><?= $this->__('general.username') ?></label>
                                <input type="text" class="form-control" required name="login" id="login" />
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= $this->__('general.password') ?></label>
                                <input type="password" class="form-control" required name="password" id="password" />
                            </div>
                            <div class="d-grid">
                                <button type="submit"
                                    class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-box-arrow-in-right"></i><?= $this->__('general.login') ?>
                                </button>
                            </div>
                            <div class="mt-3 text-center">
                                <a href="/forget-password"><?= $this->__('general.forgot_password') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
