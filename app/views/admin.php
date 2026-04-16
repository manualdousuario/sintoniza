<h2 class="fs-3 mb-3"><?php echo __('general.administration'); ?></h2>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="user_list-tab" data-bs-toggle="tab" data-bs-target="#user_list" type="button" role="tab" aria-controls="user_list" aria-selected="true">
            <?php echo __('admin.user_list'); ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="add_user-tab" data-bs-toggle="tab" data-bs-target="#add_user" type="button" role="tab" aria-controls="add_user" aria-selected="false">
            <?php echo __('admin.add_user'); ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="dashboard">

    <div class="tab-pane fade show active border border-top-0 bg-white rounded-bottom" id="user_list" role="tabpanel" aria-labelledby="user_list-tab">
        <ul class="list-group p-3">
            <?php
            $users = $db->all('SELECT id, name, email FROM users ORDER BY id DESC');
            foreach ($users as $user) {
            ?>
                <li class="list-group-item p-3">
                    <h2 class="fs-6 m-0 p-0"><?php echo htmlspecialchars($user->name); ?></h2>
                    <small class="d-block mb-2"><?php echo $user->email; ?></small>
                    <form method="post" action="" class="d-inline" onsubmit="return confirm('<?php echo __('admin.confirm_delete'); ?>');">
                        <input type="hidden" name="delete_user" value="<?php echo $user->id; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> <?php echo __('general.delete'); ?>
                        </button>
                    </form>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>

    <div class="tab-pane fade border border-top-0 bg-white rounded-bottom" id="add_user" role="tabpanel" aria-labelledby="add_user-tab">
        <form method="post" action="" class="container">
            <div class="row py-3 px-2">
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <label for="new_username" class="form-label"><?php echo __('general.username'); ?></label>
                    <input type="text" class="form-control" name="new_username" id="new_username" required>
                </div>
                <div class="col-12 col-md-3 mb-2 mb-md-0">
                    <label for="new_password" class="form-label"><?php echo __('general.password'); ?></label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required minlength="8">
                </div>
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <label for="new_email" class="form-label"><?php echo __('general.email'); ?></label>
                    <input type="email" class="form-control" name="new_email" id="new_email" required minlength="8">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo __('general.add'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>