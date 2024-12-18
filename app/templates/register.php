<?php
if (!empty($_POST)) {
    if (isset($_POST['email']) && strlen($_POST['email']) < 8) {
        echo '<div class="alert alert-danger" role="alert">A nova senha é muito curta (mínimo 8 caracteres)</div>';
    } else {
        if (!$gpodder->checkCaptcha($_POST['captcha'] ?? '', $_POST['cc'] ?? '')) {
            echo '<div class="alert alert-danger" role="alert">' . __('messages.invalid_captcha') . '</div>';
        } else {
            $email = $_POST['email'] ?? '';
            $existingUser = $db->firstRow('SELECT * FROM users WHERE email = ?', $email);
            if ($existingUser) { ?>
                <div class="alert alert-danger" role="alert"><?php echo __('messages.email_already_registered'); ?></div>
            <?php } else if ($error = $gpodder->subscribe($_POST['username'] ?? '', $_POST['password'] ?? '', $email)) { ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php } else { ?>
                <div class="alert alert-success" role="alert"><?php echo __('admin.user_registered'); ?></div>
                <p><a href="login" class="btn btn-light me-2 d-flex align-items-center justify-content-center gap-2"><i class="bi bi-box-arrow-in-right"></i> <?php echo __('general.login'); ?></a></p>
<?php }
        }
    }
}
?>

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
                        <label for="password" class="form-label"><?php echo __('general.min_password_length') ?></label>
                        <input type="password" class="form-control" minlength="8" required name="password" id="password" />
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
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-person-plus"></i> <?php echo __('general.register'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>