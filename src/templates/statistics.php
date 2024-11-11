<?php
    require_once __DIR__ . '/../config.php';
    $db = new DB(DB_HOST, DB_NAME, DB_USER, DB_PASS);

    function format_number($num) {
        return number_format($num, 0, ',', '.');
    }
    
    $total_users = $db->firstColumn("SELECT COUNT(*) FROM users");
    $total_devices = $db->firstColumn("SELECT COUNT(*) FROM devices");
    
    $top_feeds = $db->all("
        SELECT 
            f.title,
            f.feed_url,
            COUNT(s.id) as subscription_count
        FROM feeds f
        JOIN subscriptions s ON s.feed = f.id
        WHERE s.deleted = 0
        GROUP BY f.id
        ORDER BY subscription_count DESC
        LIMIT 10
    ");

    $top_played = $db->all("
        SELECT 
            e.title,
            f.title as feed_title,
            COUNT(DISTINCT ea.user) as play_count
        FROM episodes e
        JOIN feeds f ON e.feed = f.id
        JOIN episodes_actions ea ON ea.episode = e.id
        WHERE ea.action = 'play'
        GROUP BY e.id
        ORDER BY play_count DESC
        LIMIT 10
    ");
?>
<h2 class="fs-3 mb-3"><?php echo __('general.statistics');?></h2>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('statistics.registered_users'); ?></h5>
                <p class="display-5 text-primary mb-0"><?php echo format_number($total_users) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('statistics.registered_devices'); ?></h5>
                <p class="display-5 text-success mb-0"><?php echo format_number($total_devices) ?></p>
            </div>
        </div>
    </div>
</div>

<h2 class="fs-4 mb-3"><?php echo __('statistics.top_10'); ?></h2>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="top_feeds-tab" data-bs-toggle="tab" data-bs-target="#top_feeds" type="button" role="tab" aria-controls="top_feeds" aria-selected="true">
            <?php echo __('statistics.most_subscribed'); ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="top_played-tab" data-bs-toggle="tab" data-bs-target="#top_played" type="button" role="tab" aria-controls="top_played" aria-selected="false">
            <?php echo __('statistics.most_played'); ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="dashboard">
    <div class="tab-pane fade show active border border-top-0 bg-white rounded-bottom" id="top_feeds" role="tabpanel" aria-labelledby="top_feeds-tab">
        <ol class="list-group list-group-numbered p-3">
            <?php foreach ($top_feeds as $i => $feed) { ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold"><?php echo htmlspecialchars($feed->title) ?></div>
                    </div>
                    <span class="badge text-bg-primary rounded-pill"><?php echo format_number($feed->subscription_count) ?></span>
                </li>
            <?php }; ?>
        </ol>
    </div>
    <div class="tab-pane fade border border-top-0 bg-white rounded-bottom" id="top_played" role="tabpanel" aria-labelledby="devices-tab">
        <ol class="list-group list-group-numbered p-3">
            <?php foreach ($top_played as $i => $episode) { ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                    <div class="fw-bold"><?php echo htmlspecialchars($episode->title) ?></div>
                        <?php echo htmlspecialchars($episode->feed_title) ?>
                    </div>
                    <span class="badge text-bg-primary rounded-pill"><?php echo format_number($episode->play_count) ?></span>
                </li>
            <?php }; ?>
        </ol>
    </div>
</div>