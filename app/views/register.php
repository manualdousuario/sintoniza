<?php $this->layout('layout', ['title' => $this->__('general.register'), 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

    <?php if ($notice): ?>
        <div class="alert alert-warning" role="alert"><?= $this->e($notice) ?></div>
    <?php endif ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
    <?php endif ?>

    <?php if (!$disabled): ?>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <form method="post" action="">
                        <h2 class="card-title text-center mb-4"><?= $this->__('general.register') ?></h2>
                        <div class="mb-3">
                            <label for="username" class="form-label"><?= $this->__('general.username') ?></label>
                            <input type="text" class="form-control" name="username" required id="username" />
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><?= $this->__('general.min_password_length') ?></label>
                            <input type="password" class="form-control" minlength="8" required name="password"
                                id="password" />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= $this->__('general.email') ?></label>
                            <input type="email" class="form-control" minlength="8" required name="email" id="email" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Captcha</label>
                            <div class="alert alert-info">
                                <?= $this->__('messages.fill_captcha') ?> <?= $captcha ?>
                            </div>
                            <input type="text" class="form-control" name="captcha" required id="captcha" />
                        </div>
                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                <?= $this->__('general.register') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif ?>

</div>
