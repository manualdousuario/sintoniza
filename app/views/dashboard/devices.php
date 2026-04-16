<div class="container my-4">

    <h2 class="fs-3 mb-3"><?php echo __('general.devices'); ?></h2>

    <?php
    $devices = $db->all('SELECT * FROM devices WHERE user = ? ORDER BY name', $gpodder->user->id);

    if (!empty($devices)) { ?>
        <div class="list-group">
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