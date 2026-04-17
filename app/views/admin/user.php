<?php $this->layout('layout', ['title' => 'Editar Usuário', 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $this->e($messageType ?? 'success') ?>" role="alert">
            <?= $this->e($message) ?>
        </div>
    <?php endif ?>

    <div class="d-flex align-items-center mb-4 gap-2">
        <a href="/admin/users" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fs-3 m-0">Editar Usuário: <?= $this->e($user->name) ?></h2>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Informações</div>
                <div class="card-body p-4">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">Nome de usuário</label>
                            <input type="text" class="form-control" value="<?= $this->e($user->name) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email"
                                value="<?= $this->e($user->email) ?>" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="admin" id="admin"
                                value="1" <?= ($user->admin ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="admin">Administrador</label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Salvar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Status da Conta</div>
                <div class="card-body">
                    <p class="mb-3">
                        Status:
                        <?php if ($user->active ?? 1): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif ?>
                    </p>
                    <?php if ($user->active ?? 1): ?>
                        <form method="post">
                            <input type="hidden" name="toggle_active" value="0">
                            <button type="submit" class="btn btn-warning btn-sm w-100"
                                    onclick="return confirm('Desabilitar esta conta?')">
                                <i class="bi bi-pause-circle"></i> Desabilitar
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="toggle_active" value="1">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-play-circle"></i> Ativar
                            </button>
                        </form>
                    <?php endif ?>
                </div>
            </div>

            <div class="card shadow-sm border-danger">
                <div class="card-header text-danger fw-semibold">Zona de Perigo</div>
                <div class="card-body">
                    <form method="post" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita.')">
                        <input type="hidden" name="delete_user" value="1">
                        <button type="submit" class="btn btn-danger btn-sm w-100">
                            <i class="bi bi-trash"></i> Apagar Conta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
