
<div class="container my-4">
    
<div class="d-flex gap-2 pb-4 align-items-center">
    <h2 class="fs-3"><?php echo __('general.subscriptions');?></h2>
    <a href="/subscriptions/<?php echo htmlspecialchars($gpodder->user->name); ?>.opml" target="_blank" class="btn btn-outline btn-secondary"><i class="bi bi-rss-fill"></i></a>
</div>

<?php if (empty($subscriptions)): ?>
    <div class="alert alert-warning"><?php echo __('dashboard.no_info'); ?></div>
<?php else: ?>
    <ul class="list-group mb-4">
        <?php foreach ($subscriptions as $row):
            $image_url = !empty($row->image_url) ? '<div class="thumbnail"><img class="rounded border h-auto" src="'.$row->image_url.'" width="80" /></div>' : '' ;
            $title = $row->title ?? str_replace(['http://', 'https://'], '', $row->url);
        ?>
            <li class="list-group-item p-3">
                <div class="episode_info d-flex gap-3">
                    <?php echo $image_url; ?>
                    <div class="data">
                        <h2 class="fs-5"><a class="link-dark" href="/subscription/<?php echo $row->id; ?>"><?php echo htmlspecialchars($title); ?></a></h2>
                        <small class="d-block"><?php echo format_description($row->description); ?></small>
                        <small><strong><?php echo __('dashboard.last_update'); ?></strong>: <time datetime="<?php echo date(DATE_ISO8601, $row->last_change); ?>" class="text-nowrap"><?php echo date('d/m/Y \à\s H:i', $row->last_change); ?></time></small>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="/dashboard?page=<?php echo $page - 1; ?>">&laquo;</a>
                    </li>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="/dashboard?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="/dashboard?page=<?php echo $page + 1; ?>">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

</div>
