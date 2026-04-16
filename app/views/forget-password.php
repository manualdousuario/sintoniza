<div class="container">

    <?php if (isset($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType ?? 'success') ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="post" action="/forget-password">
                        <h2 class="card-title text-center mb-4"><?php echo __('general.forgot_password'); ?></h2>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo __('general.email'); ?></label>
                            <input type="email" class="form-control" required name="email" id="email" />
                        </div>
                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-envelope"></i> <?php echo __('general.send_reset_link'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>