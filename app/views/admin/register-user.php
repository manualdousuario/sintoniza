<div class="d-flex align-items-center mb-4 gap-2">
    <a href="/admin/users" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="fs-3 m-0">Registrar Usuário</h2>
</div>

<div class="card shadow-sm" style="max-width: 480px;">
    <div class="card-body p-4">
        <form method="post" action="">
            <div class="mb-3">
                <label for="new_username" class="form-label">Nome de usuário</label>
                <input type="text" class="form-control" name="new_username" id="new_username" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Senha</label>
                <input type="password" class="form-control" name="new_password" id="new_password" required minlength="8">
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
