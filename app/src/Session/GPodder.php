<?php

declare(strict_types=1);

namespace Sintoniza\Session;

use Josantonius\Session\Session;
use Sintoniza\Database\DB;
use Sintoniza\Feed\Feed;
use Sintoniza\Library\Language;
use stdClass;

class GPodder
{
    protected DB $db;
    protected Session $session;
    public ?stdClass $user = null;

    public function __construct(DB $db, Session $session)
    {
        $this->db      = $db;
        $this->session = $session;

        if (!empty($_POST['login']) || isset($_COOKIE[$this->session->getName()])) {
            if (isset($_GET['token']) && ctype_alnum($_GET['token'])) {
                $this->session->setId($_GET['token']);
            }

            if (!$this->session->isStarted()) {
                $this->session->start();
            }

            $this->user = $this->session->get('user');
        }
    }

    public function login(): ?string
    {
        if (empty($_POST['login']) || empty($_POST['password'])) {
            return null;
        }

        $user = $this->db->firstRow('SELECT * FROM users WHERE name = ?', trim($_POST['login']));

        if (!$user || !password_verify(trim($_POST['password']), $user->password ?? '')) {
            return __('messages.invalid_username_password');
        }

        $this->user = $user;
        $this->session->set('user', $user);

        if (!empty($_GET['token'])) {
            $this->session->set('app_password', sprintf('%s:%s', $_GET['token'], sha1($user->password . $_GET['token'])));
        }

        return null;
    }

    public function isLogged(): bool
    {
        return $this->session->isStarted() && $this->session->has('user');
    }

    public function logout(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }
    }

    public function getUserToken(): string
    {
        return $this->user->name . '__' . substr(sha1($this->user->password), 0, 10);
    }

    public function validateToken(string $username): bool
    {
        $login = strtok($username, '__');
        $token = strtok('');

        $this->user = $this->db->firstRow('SELECT * FROM users WHERE name = ?', $login);

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

        if (!password_verify(trim($currentPassword), $this->user->password)) {
            return __('messages.current_password_incorrect');
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->simple('UPDATE users SET password = ? WHERE id = ?', $hashed, $this->user->id);

        $this->user->password = $hashed;
        $this->session->set('user', $this->user);

        return null;
    }

    public function getUserByEmail(string $email): ?stdClass
    {
        return $this->db->firstRow('SELECT * FROM users WHERE email = ?', $email);
    }

    public function getUserByPasswordResetToken(string $token): ?stdClass
    {
        return $this->db->firstRow(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_token_expires_at > ?',
            $token,
            time()
        );
    }

    public function updatePasswordResetToken(int $userId, ?string $token, ?int $expiresAt = null): void
    {
        $this->db->simple(
            'UPDATE users SET password_reset_token = ?, password_reset_token_expires_at = ? WHERE id = ?',
            $token,
            $expiresAt,
            $userId
        );
    }

    public function resetPassword(int $userId, string $newPassword): void
    {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->simple('UPDATE users SET password = ? WHERE id = ?', $hashed, $userId);
    }

    public function updateLanguage(string $language): ?string
    {
        if (!$this->user) {
            return __('messages.user_not_logged');
        }

        $validLanguages = Language::getInstance()->getAvailableLanguages();
        if (!array_key_exists($language, $validLanguages)) {
            return __('messages.invalid_language');
        }

        $this->db->simple('UPDATE users SET language = ? WHERE id = ?', $language, $this->user->id);
        Language::getInstance()->setLanguage($language);

        $this->user->language = $language;
        $this->session->set('user', $this->user);

        return null;
    }

    public function updateTimezone(string $timezone): ?string
    {
        if (!$this->user) {
            return __('messages.user_not_logged');
        }

        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            return __('messages.invalid_timezone');
        }

        $this->db->simple('UPDATE users SET timezone = ? WHERE id = ?', $timezone, $this->user->id);

        $this->user->timezone = $timezone;
        $this->session->set('user', $this->user);

        return null;
    }

    public function canSubscribe(): bool
    {
        if (ENABLE_SUBSCRIPTIONS) {
            return true;
        }

        return !$this->db->firstColumn('SELECT COUNT(*) FROM users');
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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email invalido';
        }

        if ($this->db->firstColumn('SELECT 1 FROM users WHERE name = ?', $name)) {
            return 'O nome de usuário já existe';
        }

        $isFirstUser = !$this->db->firstColumn('SELECT COUNT(*) FROM users');
        $admin       = $isFirstUser ? 1 : 0;

        $this->db->simple(
            'INSERT INTO users (name, password, email, language, timezone, admin) VALUES (?, ?, ?, ?, ?, ?)',
            trim($name),
            password_hash($password, PASSWORD_DEFAULT),
            trim($email),
            'en',
            'UTC',
            $admin
        );

        return null;
    }

    public function generateCaptcha(): string
    {
        $c = '';
        $n = '';

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
        return sha1(trim($captcha) . __DIR__) === $check;
    }

    public function listActiveSubscriptions(): array
    {
        return $this->db->all(
            'SELECT s.*,
                COUNT(a.id) AS count,
                f.title,
                f.image_url,
                f.description,
                GREATEST(COALESCE(MAX(a.changed), 0), s.changed) AS last_change
            FROM subscriptions s
                LEFT JOIN episodes_actions a ON a.subscription = s.id
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE s.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)
            GROUP BY s.id, s.user, s.url, s.feed, s.changed, s.deleted, f.title
            ORDER BY last_change DESC',
            $this->user->id
        );
    }

    public function countActiveSubscriptions(): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*) FROM subscriptions s
                LEFT JOIN feeds f ON f.id = s.feed
             WHERE s.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)',
            $this->user->id
        );
    }

    public function listActiveSubscriptionsPage(int $offset, int $limit): array
    {
        return $this->db->all(
            'SELECT s.*,
                COUNT(a.id) AS count,
                f.title,
                f.image_url,
                f.description,
                GREATEST(COALESCE(MAX(a.changed), 0), s.changed) AS last_change
            FROM subscriptions s
                LEFT JOIN episodes_actions a ON a.subscription = s.id
                LEFT JOIN feeds f ON f.id = s.feed
            WHERE s.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)
            GROUP BY s.id, s.user, s.url, s.feed, s.changed, s.deleted, f.title
            ORDER BY last_change DESC
            LIMIT ? OFFSET ?',
            $this->user->id,
            $limit,
            $offset
        );
    }

    public function listActions(int $subscription): array
    {
        return $this->db->all(
            'SELECT a.*,
                d.name AS device_name,
                e.title,
                e.image_url,
                e.duration,
                e.url AS episode_url
            FROM episodes_actions a
                LEFT JOIN devices d ON d.id = a.device AND a.user = d.user
                LEFT JOIN episodes e ON e.id = a.episode
            WHERE a.user = ? AND a.subscription = ?
            ORDER BY changed DESC',
            $this->user->id,
            $subscription
        );
    }

    public function countActions(): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*)
             FROM episodes_actions a
                INNER JOIN subscriptions s ON s.id = a.subscription
                LEFT JOIN feeds f ON f.id = s.feed
             WHERE a.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)
                AND a.changed >= ?',
            $this->user->id,
            time() - 7 * 86400
        );
    }

    public function listActionsPage(int $offset, int $limit): array
    {
        return $this->db->all(
            'SELECT a.*,
                d.name AS device_name,
                e.title,
                e.image_url,
                e.duration,
                e.url AS episode_url
            FROM episodes_actions a
                INNER JOIN subscriptions s ON s.id = a.subscription
                LEFT JOIN feeds f ON f.id = s.feed
                LEFT JOIN devices d ON d.id = a.device AND a.user = d.user
                LEFT JOIN episodes e ON e.id = a.episode
            WHERE a.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)
                AND a.changed >= ?
            ORDER BY a.changed DESC
            LIMIT ? OFFSET ?',
            $this->user->id,
            time() - 7 * 86400,
            $limit,
            $offset
        );
    }

    public function getFeedForSubscription(int $subscription): ?Feed
    {
        $data = $this->db->firstRow(
            'SELECT f.* FROM subscriptions s INNER JOIN feeds f ON f.id = s.feed WHERE s.id = ?',
            $subscription
        );

        if (!$data) {
            return null;
        }

        $feed = new Feed($data->feed_url ?? '');
        $feed->load($data);

        return $feed;
    }

    public function getSubscriptionWithFeed(int $subscriptionId): ?\stdClass
    {
        return $this->db->firstRow(
            'SELECT s.id AS subscription_id, s.feed AS feed_id, s.url AS subscription_url, f.title, f.image_url, f.description, f.url, f.feed_url
             FROM subscriptions s
             LEFT JOIN feeds f ON f.id = s.feed
             WHERE s.id = ? AND s.user = ? AND s.deleted = 0
                AND (f.id IS NULL OR f.active = 1)',
            $subscriptionId,
            $this->user->id
        );
    }

    public function listEpisodesByFeed(int $feedId, int $offset, int $limit): array
    {
        return $this->db->all(
            'SELECT e.*,
                (SELECT a.action FROM episodes_actions a WHERE a.episode = e.id AND a.user = ? ORDER BY a.changed DESC LIMIT 1) AS last_action,
                (SELECT a.changed FROM episodes_actions a WHERE a.episode = e.id AND a.user = ? ORDER BY a.changed DESC LIMIT 1) AS last_action_time
             FROM episodes e
             WHERE e.feed = ?
             ORDER BY e.pubdate DESC
             LIMIT ? OFFSET ?',
            $this->user->id,
            $this->user->id,
            $feedId,
            $limit,
            $offset
        );
    }

    public function countEpisodesByFeed(int $feedId): int
    {
        return (int) $this->db->firstColumn(
            'SELECT COUNT(*) FROM episodes WHERE feed = ?',
            $feedId
        );
    }

    public function listEpisodeActions(int $subscriptionId, int $episodeId): array
    {
        $rows = $this->db->all(
            'SELECT a.*,
                d.name AS device_name,
                e.title,
                e.image_url,
                e.duration,
                e.url AS episode_url
             FROM episodes_actions a
                LEFT JOIN devices d ON d.id = a.device AND a.user = d.user
                LEFT JOIN episodes e ON e.id = a.episode
             WHERE a.user = ? AND a.subscription = ? AND a.episode = ?
             ORDER BY a.changed DESC',
            $this->user->id,
            $subscriptionId,
            $episodeId
        );

        foreach ($rows as $row) {
            if (!empty($row->data)) {
                try {
                    $decoded = json_decode($row->data, true, 512, JSON_THROW_ON_ERROR);
                    foreach ($decoded as $key => $value) {
                        if (!isset($row->$key)) {
                            $row->$key = $value;
                        }
                    }
                } catch (\JsonException) {
                    // ignore malformed data
                }
            }
        }

        return $rows;
    }


}
