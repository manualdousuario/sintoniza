<?php
require_once __DIR__ . '/../config.php';

$db = new DB(DB_HOST, DB_NAME, DB_USER, DB_PASS);
$gpodder = new GPodder($db);

if (PHP_SAPI === 'cli') {
	$gpodder->updateAllFeeds(true);
}
echo "Feeds metadata updated successfully at " . date('Y-m-d H:i:s') . "\n";
