<?php
require_once __DIR__ . '/config.php';

if (PHP_SAPI === 'cli-server' && file_exists(__DIR__ . $_SERVER['REQUEST_URI']) && !is_dir(__DIR__ . $_SERVER['REQUEST_URI'])) {
	return false;
}

// Fix issues with badly configured web servers
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
	@list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
}

$db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT);
$api = new API($db);

try {
	if ($api->handleRequest()) {
		return;
	}
} catch (JsonException $e) {
	return;
}

$gpodder = new GPodder($db);


function isAdmin(): bool {
    global $gpodder;
    return $gpodder->user && $gpodder->user->admin === 1;
}

if($gpodder->isLogged()) {
    date_default_timezone_set($gpodder->user->timezone);
} else {
	date_default_timezone_set('UTC');
}

function format_description(?string $str): string {
	if ($str === null) {
		return '';
	}
	$str = str_replace('</p>', "\n\n", $str);
	$str = preg_replace_callback('!<a[^>]*href=(".*?"|\'.*?\'|\S+)[^>]*>(.*?)</a>!i', function ($match) {
		$url = trim($match[1], '"\'');
		if ($url === $match[2]) {
			return $match[1];
		}
		else {
			return '[' . $match[2] . '](' . $url . ')';
		}
	}, $str);
	$str = htmlspecialchars(strip_tags($str));
	$str = preg_replace("!(?:\r?\n){3,}!", "\n\n", $str);
	$str = preg_replace('!\[([^\]]+)\]\(([^\)]+)\)!', '<a href="$2">$1</a>', $str);
	$str = preg_replace(';(?<!")https?://[^<\s]+(?!");', '<a href="$0">$0</a>', $str);
	$str = nl2br($str);
	return $str;
}

if ($api->url === 'logout') {
	$gpodder->logout();
	header('Location: ./');
	exit;
}
elseif ($gpodder->user && $api->url === 'admin' && isAdmin()) {
	html_head('Administração', $gpodder->isLogged());

	// Handle delete user action
	if (isset($_POST['delete_user'])) {
		$user_id = (int)$_POST['delete_user'];
		$db->simple('DELETE FROM users WHERE id = ?', $user_id);
		echo '<div class="alert alert-success" role="alert">Usuário deletado com sucesso!</div>';
	}

	// Handle new user registration from admin
	if (isset($_POST['new_username'], $_POST['new_password'], $_POST['new_email'])) {
		if ($error = $gpodder->subscribe($_POST['new_username'], $_POST['new_password'], $_POST['new_email'])) {
			printf('<div class="alert alert-danger" role="alert">%s</div>', htmlspecialchars($error));
		} else {
			echo '<div class="alert alert-success" role="alert">Usuário registrado com sucesso!</div>';
		}
	}

	require_once __DIR__ . '/templates/admin.php';

	html_foot();
}
elseif ($gpodder->user && $api->url === 'dashboard/subscriptions') {
	html_head('Inscrições', $gpodder->isLogged());

	require_once __DIR__ . '/templates/dashboard/subscriptions.php';

	html_foot();
}
elseif ($gpodder->user && $api->url === 'dashboard/profile') {
	html_head('Painel', $gpodder->isLogged());

	if (isset($_GET['oktoken'])) {
		echo '<div class="alert alert-success" role="alert">Você está logado, pode fechar isso e voltar para o aplicativo.</div>';
	}

	require_once __DIR__ . '/templates/dashboard/profile.php';

	html_foot();
}
elseif ($gpodder->user && $api->url === 'dashboard') {
	html_head('Painel', $gpodder->isLogged());

	if (isset($_GET['oktoken'])) {
		echo '<div class="alert alert-success" role="alert">Você está logado, pode fechar isso e voltar para o aplicativo.</div>';
	}

	require_once __DIR__ . '/templates/dashboard.php';

	html_foot();
}
elseif ($gpodder->user) {
	// Redirect to dashboard if user is logged in
	header('Location: /dashboard');
	exit;
}
elseif ($api->url === 'login') {
	$error = $gpodder->login();

	if ($gpodder->isLogged()) {
		$token = isset($_GET['token']) ? '?oktoken' : '';
		header('Location: ./' . $token);
		exit;
	}

	html_head('Entrar');

	require_once __DIR__ . '/templates/login.php';

	html_foot();
}
elseif ($api->url === 'register' && !$gpodder->canSubscribe()) {
	html_head('Registrar');
	echo '<div class="alert alert-success" role="alert">As assinaturas estão desabilitadas.</div>';
	html_foot();
}
elseif ($api->url === 'register') {
	html_head('Registrar');

	require_once __DIR__ . '/templates/register.php';

	html_foot();
}
elseif ($api->url === 'forget-password') {
	html_head('Recuperar Senha');
	require_once __DIR__ . '/templates/forget-password.php';
	html_foot();
}
elseif ($api->url === 'forget-password/reset') {
	html_head('Recuperar Senha');
	require_once __DIR__ . '/templates/forget-password/reset.php';
	html_foot();
}
else {
	html_head();
	require_once __DIR__ . '/templates/index.php';
	html_foot();
}
