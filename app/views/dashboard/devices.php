<?php $this->layout('layout', ['title' => $this->__('general.devices'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><?= $this->__('general.devices') ?></h2>
    </div>

    <?php if (!empty($devices)): ?>
        <div class="list-group">
            <?php foreach ($devices as $device):
                $data = json_decode($device->data, true);
                $types = [
                    'mobile' => 'bi-phone',
                    'other' => 'bi-laptop',
                    'desktop' => 'bi-browser-chrome'
                ];
                $device_type = $types[$data['type']] ?? 'bi-pc-display-horizontal';
            ?>
                <div class="list-group-item py-2 px-3">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $device_type ?> fs-4 me-2"></i>
                        <div>
                            <strong><?= $this->e($device->name) ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>
