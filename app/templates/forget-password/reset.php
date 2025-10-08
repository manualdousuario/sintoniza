<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_password']) && strlen($_POST['new_password']) < 8) {
        echo '<div class="alert alert-danger" role="alert">A nova senha é muito curta (mínimo 8 caracteres)</div>';
    } else {
        $token = $_GET['token'];
        $newPassword = $_POST['new_password'];
    
        $user = $gpodder->getUserByPasswordResetToken($token);
    
        if ($user) {
            $gpodder->resetPassword($user->id, $newPassword);
            $gpodder->updatePasswordResetToken($user->id, null, null);
    
            echo '<div class="alert alert-success" role="alert">
                Your password has been successfully reset. You can now log in with your new password.
            </div>';
            echo '<a href="/login" class="btn btn-primary">Go to Login</a>';
        } else {
            echo '<div class="alert alert-danger" role="alert">
                Invalid or expired password reset token.
            </div>';
        }
    }
} else {
?>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="post" action="">
                        <h2 class="card-title text-center mb-4"><?= __('general.reset_password') ?></h2>
                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?= __('general.new_password') ?></label>
                            <input type="password" class="form-control" minlength="8" required name="new_password" id="new_password" />
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-lock-fill"></i> <?= __('general.reset_password') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php } ?>