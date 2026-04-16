<?php if (isset($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType ?? 'success') ?>" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php if (($messageType ?? 'success') === 'success'): ?>
        <a href="/login" class="btn btn-primary"><?= __('general.login') ?></a>
    <?php endif; ?>
<?php else: ?>
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

<?php endif; ?>