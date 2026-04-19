<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use Sintoniza\Database\DB;
use Sintoniza\Exception\AuthException;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Library\Language;
use Sintoniza\Repository\UserRepository;
use stdClass;

class UserService
{
    public function __construct(
        private DB $db,
        private UserRepository $userRepository
    ) {}

    public function authenticate(string $username, string $password): stdClass
    {
        $user = $this->userRepository->findByUsername(trim($username));

        if (!$user || !password_verify(trim($password), $user->password ?? '')) {
            throw new AuthException(__('messages.invalid_username_password'));
        }

        if (!($user->active ?? 1)) {
            throw new AuthException('Conta desabilitada.');
        }

        return $user;
    }

    public function register(string $name, string $password, string $email): void
    {
        $errors = $this->validateRegistration($name, $password, $email);

        if ($errors) {
            throw new ValidationException($errors);
        }

        $isFirstUser  = $this->userRepository->count() === 0;
        $passwordHash = password_hash(trim($password), PASSWORD_DEFAULT);
        $language     = Language::getInstance()->getCurrentLanguage();

        $this->userRepository->create(trim($name), $passwordHash, trim($email), $isFirstUser, $language);
    }

    public function changePassword(stdClass $user, string $currentPassword, string $newPassword): void
    {
        if (!password_verify(trim($currentPassword), $user->password)) {
            throw new AuthException(__('messages.current_password_incorrect'));
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->updatePassword((int) $user->id, $hash);
    }

    public function updateTimezone(int $userId, string $timezone): void
    {
        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            throw new ValidationException(['timezone' => __('messages.invalid_timezone')]);
        }

        $this->userRepository->updateTimezone($userId, $timezone);
    }

    public function updateLanguage(int $userId, string $language): void
    {
        $validLanguages = Language::getInstance()->getAvailableLanguages();

        if (!array_key_exists($language, $validLanguages)) {
            throw new ValidationException(['language' => __('messages.invalid_language')]);
        }

        $this->db->simple('UPDATE users SET language = ? WHERE id = ?', $language, $userId);
        Language::getInstance()->setLanguage($language);
    }

    public function canSubscribe(): bool
    {
        if (ENABLE_SUBSCRIPTIONS) {
            return true;
        }

        return $this->userRepository->count() === 0;
    }

    public function deleteUser(int $id): void
    {
        $this->userRepository->delete($id);
    }

    public function generatePasswordResetToken(string $email): ?stdClass
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return null;
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = time() + 3600;

        $this->userRepository->updatePasswordResetToken((int) $user->id, $token, $expiresAt);
        $user->reset_token = $token;

        return $user;
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findByResetToken($token);

        if (!$user) {
            throw new AuthException('Token inválido ou expirado.');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->updatePassword((int) $user->id, $hash);
        $this->userRepository->clearResetToken((int) $user->id);
    }

    public function getUserToken(stdClass $user): string
    {
        return $user->name . '__' . substr(sha1($user->password), 0, 10);
    }

    public function validateToken(string $username): ?stdClass
    {
        $login = strtok($username, '__');
        $token = strtok('');

        $user = $this->userRepository->findByUsername($login);

        if (!$user) {
            return null;
        }

        $expected = $user->name . '__' . substr(sha1($user->password), 0, 10);

        return $username === $expected ? $user : null;
    }

    private function validateRegistration(string $name, string $password, string $email): array
    {
        $errors = [];

        if (trim($name) === '' || !preg_match('/^\w[\w_-]+$/', $name)) {
            $errors['name'] = 'Nome de usuário inválido. Permitido: \w[\w\d_-]+';
        }

        if ($name === 'current') {
            $errors['name'] = 'Este nome de usuário está bloqueado, escolha outro.';
        }

        if (strlen(trim($password)) < 8) {
            $errors['password'] = 'A senha é muito curta';
        }

        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if (empty($errors) && $this->userRepository->findByUsername(trim($name))) {
            $errors['name'] = 'O nome de usuário já existe';
        }

        return $errors;
    }
}
