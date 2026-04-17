<?php $this->layout('layout', ['title' => $this->__('general.latest_updates'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><i class="bi bi-clock-history me-2"></i><?= $this->__('general.latest_updates') ?></h2>
    </div>

    <?php if (!empty($actions)): ?>
        <ul class="list-group mb-4">
        <?php foreach ($actions as $row):
            $url = strtok(basename($row->url), '?');
            strtok('');
            $title = $row->title ?? $url;
            $image_url = !empty($row->image_url) ? '<div class="thumbnail"><img class="rounded border" src="' . $this->e($row->image_url) . '" width="80" height="80" /></div>' : '';

            if ($row->action == 'play') {
                $action = '<div class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . $this->__('actions.played') . '</div>';
            } elseif ($row->action == 'download') {
                $action = '<div class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . $this->__('actions.downloaded') . '</div>';
            } elseif ($row->action == 'delete') {
                $action = '<div class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . $this->__('actions.deleted') . '</div>';
            } else {
                $action = '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . $this->__('actions.unavailable') . '</div>';
            }

            $device_name = $row->device_name ? '<div class="badge text-bg-primary rounded-pill">' . $this->e($row->device_name) . '</div>' : '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . $this->__('devices.unavailable') . '</div>';
            $duration = gmdate("H:i:s", $row->duration);
        ?>
            <li class="list-group-item p-3">
                <div class="meta pb-2">
                    <?= $action ?> <?= $this->__('actions.on') ?> <?= $device_name ?> <small><time datetime="<?= date(DATE_ISO8601, $row->changed) ?>"><?= date('d/m/Y \à\s H:i', $row->changed) ?></time></small>
                </div>
                <div class="episode_info d-flex gap-3">
                    <?= $image_url ?>
                    <div class="data">
                        <a class="link-dark" href="/subscription/<?= (int) $row->subscription ?>/episode/<?= (int) $row->episode ?>"><?= $this->e($title) ?></a><br/>
                        <?= $this->__('general.duration') ?>: <?= $duration ?>
                    </div>
                </div>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif ?>

</div>
