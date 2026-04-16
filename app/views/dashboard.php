<div class="text-center mb-4">
    <h2 class="mb-3"><?php echo __('general.hello'); ?>, <strong><?php echo $gpodder->user->name; ?></strong>!</h2>
    <div class="alert alert-warning" role="alert">
        <?php echo __('dashboard.secret_user'); ?>: <strong><?php echo $gpodder->getUserToken(); ?></strong>
        <small class="d-block"><?php echo __('dashboard.secret_user_note'); ?></small>
    </div>
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="latest-tab" data-bs-toggle="tab" data-bs-target="#latest" type="button" role="tab" aria-controls="latest" aria-selected="true">
            <?php echo __('general.latest_updates'); ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab" aria-controls="devices" aria-selected="false">
            <?php echo __('general.devices'); ?>
        </button>
    </li>
</ul>
<div class="tab-content" id="dashboard">
    <div class="tab-pane fade show active border border-top-0 bg-white rounded-bottom" id="latest" role="tabpanel" aria-labelledby="latest-tab">
        <?php
        // Latest subscriptions
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
            <ul class="list-group p-3">
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
    </div>
    <div class="tab-pane fade border border-top-0 bg-white rounded-bottom" id="devices" role="tabpanel" aria-labelledby="devices-tab">
        <?php
        // Get user's registered devices
        $devices = $db->all('SELECT * FROM devices WHERE user = ? ORDER BY name', $gpodder->user->id);

        if (!empty($devices)) { ?>
            <div class="list-group p-3">
            <?php
            foreach ($devices as $device) {
                $data = json_decode($device->data, true);
                if ($data['type'] == 'mobile') {
                    $device_type = 'bi-phone';
                } else {
                    $device_type = 'bi-window';
                }
                ?>
                <div class="list-group-item py-2 px-3">
                    <div class="d-flex align-items-center">
                        <i class="bi <?php echo $device_type; ?> fs-4 me-2"></i>
                        <div>
                            <strong><?php echo htmlspecialchars($device->name); ?></strong>
                        </div>
                    </div>
                </div>
                <?php
            } ?>
            </div>
        <?php } ?>
    </div>
</div>