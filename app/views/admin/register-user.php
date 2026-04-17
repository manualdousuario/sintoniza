<?php $this->layout('layout', ['title' => 'Registrar Usuário', 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'admin']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <a href="/admin/users" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="page-title flex-grow-1"><i class="bi bi-person-plus me-2"></i>Registrar Usuário</h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
            <?= $this->e($message) ?>
        </div>
    <?php endif ?>

    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="new_username" class="form-label">Nome de usuário</label>
                    <input type="text" class="form-control" name="new_username" id="new_username" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Senha</label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required
                        minlength="8">
                </div>
                <div class="mb-3">
                    <label for="new_email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="new_email" id="new_email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-person-plus"></i> Registrar
                </button>
            </form>
        </div>
    </div>

</div>
