<?php
use jessedp\Timezones\Timezones;
use Sintoniza\Library\Language;

$this->layout('layout', ['title' => $this->__('general.profile'), 'logged' => $logged, 'isAdmin' => $isAdmin]);
?>

<div class="container my-4">

    <h2 class="fs-3 mb-3"><?= $this->__('general.profile') ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success" role="alert"><?= $this->e($success) ?></div>
    <?php endif ?>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="language_settings-tab" data-bs-toggle="tab"
                data-bs-target="#language_settings" type="button" role="tab" aria-controls="language_settings"
                aria-selected="true">
                <?= $this->__('profile.language_settings') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="timezone_settings-tab" data-bs-toggle="tab" data-bs-target="#timezone_settings"
                type="button" role="tab" aria-controls="timezone_settings" aria-selected="true">
                <?= $this->__('profile.timezone_settings') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="change_password-tab" data-bs-toggle="tab" data-bs-target="#change_password"
                type="button" role="tab" aria-controls="change_password" aria-selected="false">
                <?= $this->__('profile.change_password') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="api_token-tab" data-bs-toggle="tab" data-bs-target="#api_token" type="button"
                role="tab" aria-controls="api_token" aria-selected="false">
                <?= $this->__('profile.api_token') ?>
            </button>
        </li>
    </ul>
    <div class="tab-content" id="dashboard">
        <div class="tab-pane fade show active border border-top-0 bg-white rounded-bottom" id="language_settings"
            role="tabpanel" aria-labelledby="language_settings-tab">
            <form method="post" action="/dashboard/profile" class="p-3">
                <div class="form-group mb-3">
                    <label for="language" class="form-label"><?= $this->__('profile.select_language') ?></label>
                    <select name="language" id="language" class="form-control">
                        <?php foreach ($availableLanguages as $code => $name): ?>
                            <option value="<?= $this->e($code) ?>" <?= $code === $currentLang ? 'selected' : '' ?>><?= $this->e($name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?= $this->__('general.save') ?>
                </button>
            </form>
        </div>

        <div class="tab-pane fade border border-top-0 bg-white rounded-bottom" id="timezone_settings" role="tabpanel"
            aria-labelledby="timezone_settings-tab">
            <form method="post" action="/dashboard/profile" class="p-3">
                <div class="form-group mb-3">
                    <label for="timezone" class="form-label"><?= $this->__('profile.select_timezone') ?></label>
                    <?= Timezones::create('timezone', $currentTimezone, ['attr' => ['id' => 'timezone', 'required' => 'required', 'placeholder' => 'Timezone', 'class' => 'form-control']]) ?>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?= $this->__('general.save') ?>
                </button>
            </form>
        </div>

        <div class="tab-pane fade border border-top-0 bg-white rounded-bottom" id="change_password" role="tabpanel"
            aria-labelledby="change_password-tab">
            <form method="post" action="/dashboard/profile" class="p-3">
                <div class="mb-3">
                    <label for="current_password" class="form-label"><?= $this->__('profile.current_password') ?>:</label>
                    <input type="password" class="form-control" required name="current_password" id="current_password" />
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label"><?= $this->__('profile.new_password') ?>:
                        (<?= $this->__('profile.min_password_length') ?>):</label>
                    <input type="password" class="form-control" required name="new_password" id="new_password"
                        minlength="8" />
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label"><?= $this->__('profile.confirm_password') ?>:</label>
                    <input type="password" class="form-control" required name="confirm_password" id="confirm_password"
                        minlength="8" />
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <?= $this->__('general.save') ?>
                </button>
            </form>
        </div>

        <div class="tab-pane fade border border-top-0 bg-white rounded-bottom p-3" id="api_token" role="tabpanel"
            aria-labelledby="api_token-tab">
            <?= $this->__('dashboard.secret_user') ?>: <strong><?= $this->e($userToken) ?></strong>
            <small class="d-block"><?= $this->__('dashboard.secret_user_note') ?></small>
        </div>
    </div>

</div>
