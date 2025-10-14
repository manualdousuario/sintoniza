<?php

class GPodder
{
	protected DB $db;
	public ?\stdClass $user = null;

	public function __construct(DB $db)
	{
		$this->db = $db;

		if (!empty($_POST['login']) || isset($_COOKIE[session_name()])) {
			if (isset($_GET['token']) && ctype_alnum($_GET['token'])) {
				session_id($_GET['token']);
			}

			@session_start();

			if (!empty($_SESSION['user'])) {
				$this->user = $_SESSION['user'];
			}
		}
	}

	public function login(): ?string
	{
		if (empty($_POST['login']) || empty($_POST['password'])) {
			return null;
		}

		$user = $this->db->firstRow('SELECT * FROM users WHERE name = ?;', trim($_POST['login']));

		if (!$user || !password_verify(trim($_POST['password']), $user->password ?? '')) {
			return __('messages.invalid_username_password');
		}

		$_SESSION['user'] = $this->user = $user;

		if (!empty($_GET['token'])) {
			$_SESSION['app_password'] = sprintf('%s:%s', $_GET['token'], sha1($user->password . $_GET['token']));
		}

		return null;
	}

	public function isLogged(): bool
	{
		return !empty($_SESSION['user']);
	}

	public function logout(): void
	{
		session_destroy();
	}

	public function getUserToken(): string
	{
		return $this->user->name . '__' . substr(sha1($this->user->password), 0, 10);
	}

	public function validateToken(string $username): bool
	{
		$login = strtok($username, '__');
		$token = strtok('');

		$this->user = $this->db->firstRow('SELECT * FROM users WHERE name = ?;', $login);

		if (!$this->user) {
			return false;
		}

		return $username === $this->getUserToken();
	}

	public function changePassword(string $currentPassword, string $newPassword): ?string
	{
		if (!$this->user) {
			return __('messages.user_not_logged');
		}

		// Verify current password
		if (!password_verify(trim($currentPassword), $this->user->password)) {
			return __('messages.current_password_incorrect');
		}

		// Update password in database
		$hashedPassword = password_hash($newPassword, null);
		$this->db->simple('UPDATE users SET password = ? WHERE id = ?;', $hashedPassword, $this->user->id);

		// Update session user object
		$this->user->password = $hashedPassword;
		$_SESSION['user'] = $this->user;

		return null;
	}

	public function getUserByEmail(string $email): ?stdClass
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->firstRow($sql, ['email' => $email]);
    }

    public function getUserByPasswordResetToken(string $token): ?stdClass
    {
        $sql = "SELECT * FROM users WHERE password_reset_token = :token AND password_reset_token_expires_at > :now";
        return $this->db->firstRow($sql, ['token' => $token, 'now' => time()]);
    }

	public function updatePasswordResetToken(int $userId, ?string $token, ?int $expiresAt = null): void
    {
        $sql = "UPDATE users SET password_reset_token = :token, password_reset_token_expires_at = :expiresAt WHERE id = :userId";
        $this->db->simple($sql, ['userId' => $userId, 'token' => $token, 'expiresAt' => $expiresAt]);
    }

 public function resetPassword(int $userId, string $newPassword): void
    {
        // Hash the new password and update in database
        $hashedPassword = password_hash($newPassword, null);
        $this->db->simple('UPDATE users SET password = ? WHERE id = ?;', $hashedPassword, $userId);
    }

 public function updateLanguage(string $language): ?string
	{
		if (!$this->user) {
			return __('messages.user_not_logged');
		}

		// Validate language using Language class
		$validLanguages = Language::getInstance()->getAvailableLanguages();
		if (!array_key_exists($language, $validLanguages)) {
			return __('messages.invalid_language');
		}

		// Update language in database
		$this->db->simple('UPDATE users SET language = ? WHERE id = ?;', $language, $this->user->id);

		// Update current language instance
		Language::getInstance()->setLanguage($language);

		// Update session user object
		$this->user->language = $language;
		$_SESSION['user'] = $this->user;

		return null;
	}

	public function updateTimezone(string $timezone): ?string
	{
		if (!$this->user) {
			return __('messages.user_not_logged');
		}

		// Validate timezone
		if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
			return __('messages.invalid_timezone');
		}

		// Update timezone in database
		$this->db->simple('UPDATE users SET timezone = ? WHERE id = ?;', $timezone, $this->user->id);

		// Update session user object
		$this->user->timezone = $timezone;
		$_SESSION['user'] = $this->user;

		return null;
	}

	public function canSubscribe(): bool
	{
		if (ENABLE_SUBSCRIPTIONS) {
			return true;
		}

		if (!$this->db->firstColumn('SELECT COUNT(*) FROM users;')) {
			return true;
		}

		return false;
	}

	public function validateEmail(string $email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		
		$domain = substr(strrchr($email, "@"), 1);
		if (!checkdnsrr($domain, "MX")) {
			return false;
		}
		
		return true;
	}

	public function subscribe(string $name, string $password, string $email): ?string
	{
		if (trim($name) === '' || !preg_match('/^\w[\w_-]+$/', $name)) {
			return 'Nome de usuário inválido. Permitido é: \w[\w\d_-]+';
		}

		if ($name === 'current') {
			return 'Este nome de usuário está bloqueado, escolha outro.';
		}

		$password = trim($password);

		if (strlen($password) < 8) {
			return 'A senha é muito curta';
		}

		$email = trim($email);

		if ($this->validateEmail($email) === false) {
			return 'Email invalido';
		}

		if ($this->db->firstColumn('SELECT 1 FROM users WHERE name = ?;', $name)) {
			return 'O nome de usuário já existe';
		}

		$this->db->simple('INSERT INTO users (name, password, email, language, timezone) VALUES (?, ?, ?, ?, ?);', trim($name), password_hash($password, null), trim($email), 'en', 'UTC');
		return null;
	}

	/**
	 * @throws Exception
	 */
	public function generateCaptcha(): string
	{
		$n = '';
		$c = '';

		for ($i = 0; $i < 4; $i++) {
			$j = random_int(0, 9);
			$c .= $j;
			$n .= sprintf('<b class="d-none">%d</b><i>%d</i>', random_int(0, 9), $j);
		}

		$n .= sprintf('<input type="hidden" name="cc" value="%s" />', sha1($c . __DIR__));

		return $n;
	}

	public function checkCaptcha(string $captcha, string $check): bool
	{
		$captcha = trim($captcha);
		return sha1($captcha . __DIR__) === $check;
	}

    public function countActiveSubscriptions(): int
    {
        $count = $this->db->firstColumn(
            'SELECT COUNT(*) FROM subscriptions WHERE user = ? AND deleted = 0',
            $this->user->id
        );
        
        return (int)$count;
    }

    public function listActiveSubscriptions(): array
    {
        return $this->db->all('SELECT s.*, 
            COUNT(a.id) AS count, 
            f.title, 
			f.image_url, 
			f.description, 
            GREATEST(COALESCE(MAX(a.changed), 0), s.changed) AS last_change
            FROM subscriptions s
                LEFT JOIN episodes_actions a ON a.subscription = s.id
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE s.user = ? AND s.deleted = 0
            GROUP BY s.id, s.user, s.url, s.feed, s.changed, s.deleted, f.title
            ORDER BY last_change DESC', 
            $this->user->id
        );
    }

	public function listActions(int $subscription): array
	{
		return $this->db->all('SELECT a.*,
				d.name AS device_name,
				e.title,
				e.image_url,
				e.duration,
				e.url AS episode_url
			FROM episodes_actions a
				LEFT JOIN devices d ON d.id = a.device AND a.user = d.user
				LEFT JOIN episodes e ON e.id = a.episode
			WHERE a.user = ? AND a.subscription = ?
			ORDER BY changed DESC;', $this->user->id, $subscription);
	}

	public function updateFeedForSubscription(int $subscription): ?Feed
	{
		try {
			$url = $this->db->firstColumn('SELECT url FROM subscriptions WHERE id = ?;', $subscription);

			if (!$url) {
				return null;
			}

			$feed = new Feed($url);

			if (!$feed->fetch()) {
				error_log(sprintf("Failed to fetch feed for subscription %d (URL: %s)", $subscription, $url));
				return null;
			}

			$feed->sync($this->db);

			return $feed;
		} catch (\Exception $e) {
			error_log(sprintf("Error updating feed for subscription %d: %s in %s:%d",
				$subscription, $e->getMessage(), $e->getFile(), $e->getLine()));
			return null;
		}
	}

	public function getFeedForSubscription(int $subscription): ?Feed
	{
		$data = $this->db->firstRow('SELECT f.*
			FROM subscriptions s INNER JOIN feeds f ON f.id = s.feed
			WHERE s.id = ?;', $subscription);

		if (!$data) {
			return null;
		}

		$feed = new Feed($data->feed_url);
		$feed->load($data);
		return $feed;
	}

    public function updateAllFeeds(bool $cli = false): void
    {
        $sql = 'SELECT s.id AS subscription, s.url, 
            GREATEST(COALESCE(MAX(a.changed), 0), s.changed) AS changed
            FROM subscriptions s
                LEFT JOIN episodes_actions a ON a.subscription = s.id
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE f.last_fetch IS NULL 
                OR f.last_fetch < s.changed 
                OR f.last_fetch < COALESCE(a.changed, 0)
            GROUP BY s.id, s.url, s.changed';

        @ini_set('max_execution_time', 3600);
        @ob_end_flush();
        @ob_implicit_flush(true);
        $i = 0;

        foreach ($this->db->iterate($sql) as $row) {
            @set_time_limit(30);

            if ($cli) {
                printf("Atualizando %s\n", $row->url);
            }
            else {
                printf("<h4>Atualizando %s</h4>", htmlspecialchars($row->url));
                echo str_pad(' ', 4096);
                flush();
            }

            try {
                $this->updateFeedForSubscription($row->subscription);
                $i++;
            } catch (\Exception $e) {
                // Log the error and continue with the next feed
                $errorMsg = sprintf("ERRO ao atualizar feed %s (subscription %d): %s",
                    $row->url, $row->subscription, $e->getMessage());
                error_log($errorMsg);
                
                if ($cli) {
                    printf("  ❌ ERRO: %s\n", $e->getMessage());
                } else {
                    printf("<p style='color:red'>❌ ERRO: %s</p>", htmlspecialchars($e->getMessage()));
                    flush();
                }
                // Continue to the next feed
                continue;
            }
        }

        if (!$i) {
            echo "Nada para atualizar\n";
        }
    }
}
