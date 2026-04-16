<div class="container">

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="post" action="">
                        <h2 class="card-title text-center mb-4"><?php echo __('general.register') ?></h2>
                        <div class="mb-3">
                            <label for="username" class="form-label"><?php echo __('general.username') ?></label>
                            <input type="text" class="form-control" name="username" required id="username" />
                        </div>
                        <div class="mb-3">
                            <label for="password"
                                class="form-label"><?php echo __('general.min_password_length') ?></label>
                            <input type="password" class="form-control" minlength="8" required name="password"
                                id="password" />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo __('general.email') ?></label>
                            <input type="email" class="form-control" minlength="8" required name="email" id="email" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Captcha</label>
                            <div class="alert alert-info">
                                <?php echo __('messages.fill_captcha') ?> <?php echo $gpodder->generateCaptcha() ?>
                            </div>
                            <input type="text" class="form-control" name="captcha" required id="captcha" />
                        </div>
                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-person-plus"></i> <?php echo __('general.register'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>