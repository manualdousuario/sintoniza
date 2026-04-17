<?php $this->layout('layout', ['title' => $this->__('general.devices'), 'logged' => $logged, 'isAdmin' => $isAdmin]) ?>

<div class="container my-4">

    <h2 class="fs-3 mb-3"><?= $this->__('general.devices') ?></h2>

    <?php if (!empty($devices)): ?>
        <div class="list-group">
            <?php foreach ($devices as $device):
                $data = json_decode($device->data, true);
                $device_type = ($data['type'] ?? '') === 'mobile' ? 'bi-phone' : 'bi-window';
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
