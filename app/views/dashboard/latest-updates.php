<?php $this->layout('layout', ['title' => $this->__('general.latest_updates'), 'logged' => $logged, 'isAdmin' => $isAdmin, 'env' => 'dashboard']) ?>

<div class="container">

    <div class="page-header d-flex gap-2 align-items-center">
        <h2 class="page-title flex-grow-1"><?= $this->__('general.latest_updates') ?></h2>
    </div>

    <?php if (empty($actions)): ?>
        <div class="alert alert-warning"><?= $this->__('dashboard.no_info') ?></div>
    <?php else: ?>
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

        <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/dashboard/profile/latest-updates?page=<?= $page - 1 ?>">&laquo;</a>
                        </li>
                    <?php endif ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/dashboard/profile/latest-updates?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor ?>

                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/dashboard/profile/latest-updates?page=<?= $page + 1 ?>">&raquo;</a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        <?php endif ?>
    <?php endif ?>

</div>
