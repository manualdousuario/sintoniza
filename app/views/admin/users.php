<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fs-3 m-0">Usuários</h2>
        <a href="/admin/register-user" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus"></i> Novo Usuário
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="text-muted small"><?php echo $u->id; ?></td>
                            <td><?php echo htmlspecialchars($u->name); ?></td>
                            <td><?php echo htmlspecialchars($u->email); ?></td>
                            <td>
                                <?php if ($u->admin): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u->active ?? 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/admin/user/<?php echo $u->id; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="/admin/users?page=<?php echo $page - 1; ?>">&laquo;</a>
                    </li>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="/admin/users?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="/admin/users?page=<?php echo $page + 1; ?>">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

</div>