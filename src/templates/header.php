<?php
function html_head($page_name = null, $logged = false) {
	if($page_name == null) {
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
	<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($title) ; ?>" />
	<meta name="description" content="<?php echo htmlspecialchars(__('general.site_description')) ; ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?php echo BASE_URL ; ?>" />
	<meta property="og:title" content="<?php echo htmlspecialchars($title) ; ?> - <?php echo __('general.podcast_sync') ; ?>" />
	<meta property="og:description" content="<?php echo htmlspecialchars(__('general.site_description')) ; ?>" />
	<meta property="og:image" content="/assets/opengraph.png" />
</head>
<body class="bg-light py-3">
	<div class="container">
		<header class="bg-dark bg-gradient rounded-3 shadow pt-4 pt-md-3 pb-4 pb-md-3 ps-4 pe-4 text-bg-dark">
			<div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
				<a href="/" class="d-flex align-items-center me-lg-3 text-white text-decoration-none">
					<i class="bi bi-broadcast-pin fs-3"></i><span class="fw-bold ms-3"><?php echo TITLE ; ?></span>
				</a>

				<ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
					<li><a href="/" class="nav-link px-2 text-white d-flex align-items-center justify-content-center gap-2"><i class="bi bi-house"></i> <?php echo __('general.home'); ?></a></li>
					<li><a href="/statistics" class="nav-link px-2 text-white d-flex align-items-center justify-content-center gap-2"><i class="bi bi-house"></i> <?php echo __('general.statistics'); ?></a></li>
					<?php
						if(isAdmin()) { ?>
							<li><a href="/admin" class="nav-link px-2 text-white d-flex align-items-center justify-content-center gap-2"><i class="bi bi-shield-lock"></i> <?php echo __('general.administration'); ?></a></li>
						<?php }
					?>
				</ul>

				<div class="text-end d-flex align-items-center justify-content-center gap-2 flex-wrap">
					<?php 
						if ($logged == false) { ?>
							<a href="/login" class="btn btn-light d-flex align-items-center justify-content-center gap-2"><i class="bi bi-box-arrow-in-right"></i> <?php echo __('general.login'); ?></a>
							<a href="/register" class="btn btn-warning d-flex align-items-center justify-content-center gap-2"><i class="bi bi-person-plus"></i> <?php echo __('general.register'); ?></a>
						<?php } else { ?>
							<a href="/dashboard/subscriptions" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"><i class="bi bi-mic-fill"></i>  <?php echo __('general.subscriptions'); ?></a>
							<a href="/dashboard/profile" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"><i class="bi bi-nut"></i>  <?php echo __('general.profile'); ?></a>
							<a href="/logout" class="btn btn-danger d-flex align-items-center justify-content-center gap-2"><i class="bi bi-box-arrow-right"></i>  <?php echo __('general.logout'); ?></a>
						<?php }
					?>
				</div>
			</div>
		</header>

		<div class="py-4">
			<main>
<?php
}
