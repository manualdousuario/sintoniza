<?php
if ($error) { ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
<?php }

if (isset($_GET['token'])) { ?>
    <div class="alert alert-warning" role="alert"><?php echo __('messages.app_requesting_access'); ?></div>
<?php }
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <form method="post" action="">
                    <h2 class="card-title text-center mb-4"><?php echo __('general.login'); ?></h2>
                    <div class="mb-3">
                        <label for="login" class="form-label"><?php echo __('general.username'); ?></label>
                        <input type="text" class="form-control" required name="login" id="login" />
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo __('general.password'); ?></label>
                        <input type="password" class="form-control" required name="password" id="password" />
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i> <?php echo __('general.login'); ?>
                        </button>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="/forget-password"><?php echo __('general.forgot_password'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>