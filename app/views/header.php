<?php

use Sintoniza\Library\Language;

function html_head($page_name = null, $logged = false)
{
	if ($page_name == null) {
		$title = TITLE;
	} else {
		$title = TITLE . ' | ' . $page_name;
	}
?>
	<!DOCTYPE html>
	<html lang="<?php echo Language::getInstance()->getCurrentLanguage(); ?>">

	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title><?php echo htmlspecialchars($title); ?></title>
		<link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
		<link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
		<link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
		<link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
		<link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
		<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($title); ?>" />
		<meta name="description" content="<?php echo htmlspecialchars(__('general.site_description')); ?>" />
		<meta property="og:type" content="website" />
		<meta property="og:url" content="<?php echo BASE_URL; ?>" />
		<meta property="og:title" content="<?php echo htmlspecialchars($title); ?> - <?php echo __('general.podcast_sync'); ?>" />
		<meta property="og:description" content="<?php echo htmlspecialchars(__('general.site_description')); ?>" />
		<meta property="og:image" content="/assets/opengraph.png" />
	</head>

	<body class="bg-light">
		<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom shadow">
			<div class="container">
				<div class="d-block d-md-flex">
					<div class="me-3">
						<i class="bi bi-book-half text-white fs-5 me-2"></i>
						<a href="/" class="fs-4 fw-bold text-white text-decoration-none"><?php echo TITLE; ?></a>
					</div>
				</div>
				<div class="d-flex align-items-center">
					<?php
					if (isAdmin()) { ?>
						<a href="/admin" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-shield-lock"></i> <?php echo __('general.administration'); ?></a>
					<?php }
					?>
					<?php
					if ($logged == false) { ?>
						<a href="/login" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-box-arrow-in-right"></i> <?php echo __('general.login'); ?></a>
						<a href="/register" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-person-plus"></i> <?php echo __('general.register'); ?></a>
					<?php } else { ?>
						<a href="/dashboard" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-mic-fill"></i> <?php echo __('general.subscriptions'); ?></a>
						<div class="dropdown me-2">
							<button class="btn btn-sm btn-outline-secondary dropdown-toggle" alt="<?php echo __('general.profile'); ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="bi bi-person-circle"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-end">
								<li><a class="dropdown-item" href="/dashboard/profile/latest-updates"><i class="bi bi-clock-history me-2"></i><?php echo __('general.latest_updates'); ?></a></li>
								<li><a class="dropdown-item" href="/dashboard/profile/devices"><i class="bi bi-phone me-2"></i><?php echo __('general.devices'); ?></a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="/dashboard/profile"><i class="bi bi-gear me-2"></i><?php echo __('general.profile'); ?></a></li>
							</ul>
						</div>
						<a href="/logout" class="btn btn-sm btn-outline-secondary me-2" alt="<?php echo __('general.logout'); ?>"><i class="bi bi-door-closed"></i></a>
					<?php }
					?>
				</div>
			</div>
		</nav>

		<div class="container py-3">

			<div class="py-4">
				<main>
				<?php
			}
