<p>
    <a href="/subscription/<?php echo $subscription->subscription_id; ?>" class="btn btn-danger"><?php echo __('general.back'); ?></a>
</p>

<div class="row mb-4">
    <?php if (!empty($episode->image_url)): ?>
        <div class="col-12 col-md-2">
            <img class="rounded w-100 h-auto border" width="150" height="150" src="<?php echo htmlspecialchars($episode->image_url); ?>">
        </div>
    <?php endif; ?>
    <div class="col-12 col-md-10">
        <h2 class="fs-3"><?php echo htmlspecialchars($episode->title ?? basename(strtok($episode->media_url, '?'))); ?></h2>
        <p class="text-muted mb-1">
            <?php echo __('general.duration'); ?>: <?php echo $episode->duration ? gmdate('H:i:s', (int) $episode->duration) : '—'; ?>
            <?php if ($episode->pubdate): ?>
                &middot; <?php echo date('d/m/Y', strtotime($episode->pubdate)); ?>
            <?php endif; ?>
        </p>
        <a href="<?php echo htmlspecialchars($episode->media_url); ?>" target="_blank" class="btn btn-sm btn-secondary">
            <i class="bi bi-cloud-arrow-down-fill"></i> <?php echo __('general.download'); ?>
        </a>
        <?php if (!empty($episode->description)): ?>
            <div class="mt-3"><?php echo format_description($episode->description); ?></div>
        <?php endif; ?>
    </div>
</div>

<h3 class="fs-4 mb-3"><?php echo __('general.history'); ?></h3>

<?php if (empty($actions)): ?>
    <div class="alert alert-secondary"><?php echo __('dashboard.no_info'); ?></div>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($actions as $row):
            if ($row->action === 'play') {
                $actionBadge = '<div class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . __('actions.played') . '</div>';
            } elseif ($row->action === 'download') {
                $actionBadge = '<div class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . __('actions.downloaded') . '</div>';
            } elseif ($row->action === 'delete') {
                $actionBadge = '<div class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . __('actions.deleted') . '</div>';
            } else {
                $actionBadge = '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . __('actions.unavailable') . '</div>';
            }

            $deviceBadge = $row->device_name
                ? '<div class="badge text-bg-primary rounded-pill">' . htmlspecialchars($row->device_name) . '</div>'
                : '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> Indisponível</div>';
        ?>
            <li class="list-group-item p-3">
                <div class="meta">
                    <?php echo $actionBadge; ?>
                    <?php echo __('actions.from'); ?> <?php echo $deviceBadge; ?>
                    <?php echo __('actions.on'); ?>
                    <small>
                        <time datetime="<?php echo date(DATE_ISO8601, $row->changed); ?>">
                            <?php echo date('d/m/Y', $row->changed); ?> <?php echo __('actions.at'); ?> <?php echo date('H:i', $row->changed); ?>
                        </time>
                    </small>
                    <?php if ($row->action === 'play' && !empty($row->position)): ?>
                        &middot; <small><?php echo gmdate('H:i:s', (int) $row->position); ?></small>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
