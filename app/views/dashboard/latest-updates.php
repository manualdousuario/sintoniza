<h2 class="fs-3 mb-3"><?php echo __('general.latest_updates'); ?></h2>

<?php
$subscriptions = $gpodder->listActiveSubscriptions();
$actions = [];

foreach ($subscriptions as $sub) {
    $feed_actions = $gpodder->listActions($sub->id);
    $actions = array_merge($actions, $feed_actions);
}

usort($actions, function ($a, $b) {
    return $b->changed - $a->changed;
});

$actions = array_slice($actions, 0, 10);

if (!empty($actions)) { ?>
    <ul class="list-group">
    <?php
        foreach ($actions as $row) {
            $url = strtok(basename($row->url), '?');
            strtok('');
            $title = $row->title ?? $url;
            $image_url = !empty($row->image_url) ? '<div class="thumbnail"><img class="rounded border" src="' . $row->image_url . '" width="80" height="80" /></div>' : '';

            if ($row->action == 'play') {
                $action = '<div class="badge text-bg-success rounded-pill"><i class="bi bi-play"></i> ' . __('actions.played') . '</div>';
            } else if ($row->action == 'download') {
                $action = '<div class="badge text-bg-primary rounded-pill"><i class="bi bi-download"></i> ' . __('actions.downloaded') . '</div>';
            } else if ($row->action == 'delete') {
                $action = '<div class="badge text-bg-danger rounded-pill"><i class="bi bi-trash-fill"></i> ' . __('actions.deleted') . '</div>';
            } else {
                $action = '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . __('actions.unavailable') . '</div>';
            }

            $device_name = $row->device_name ? '<div class="badge text-bg-primary rounded-pill">' . $row->device_name . '</div>' : '<div class="badge text-bg-secondary rounded-pill"><i class="bi bi-motherboard"></i> ' . __('devices.unavailable') . '</div>';
            $duration = gmdate("H:i:s", $row->duration);
            ?>
                <li class="list-group-item p-3">
                    <div class="meta pb-2">
                        <?php echo $action; ?> <?php echo __('actions.on'); ?> <?php echo $device_name; ?> <small><time datetime="<?php echo date(DATE_ISO8601, $row->changed); ?>"><?php echo date('d/m/Y \à\s H:i', $row->changed); ?></time></small>
                    </div>
                    <div class="episode_info d-flex gap-3">
                        <?php echo $image_url; ?>
                        <div class="data">
                            <a class="link-dark" target="_blank" href="<?php echo $row->episode_url; ?>"><?php echo htmlspecialchars($title); ?></a><br/>
                            <?php echo __('general.duration'); ?>: <?php echo $duration; ?><br/>
                            <a href="<?php echo htmlspecialchars($row->url); ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="bi bi-cloud-arrow-down-fill"></i> <?php echo __('general.download'); ?></a>
                        </div>
                    </div>
                </li>
            <?php
        }
    ?>
    </ul>
<?php } ?>
