<?php

declare(strict_types=1);

namespace Sintoniza\Library;

class Language
{
    private static ?self $instance = null;
    private array $translations = [];
    private string $currentLang = 'en';

    private function __construct()
    {
        $this->loadInitialLanguage();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getCurrentLanguage(): string
    {
        if (isset($_SESSION['user']->language) && $this->isValidLanguage($_SESSION['user']->language)) {
            return $_SESSION['user']->language;
        }

        return $this->currentLang;
    }

    public function setLanguage(string $lang): bool
    {
        if (!$this->isValidLanguage($lang)) {
            return false;
        }

        $this->currentLang = $lang;
        $this->loadLanguage($lang);

        return true;
    }

    public function get(string $key): string
    {
        $keys  = explode('.', $key);
        $value = $this->translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        return (string) $value;
    }

    public function getAvailableLanguages(): array
    {
        return [
            'en'    => 'English',
            'es'    => 'Español',
            'pt-BR' => 'Português (Brasil)',
        ];
    }

    private function loadInitialLanguage(): void
    {
        if (isset($_SESSION['user']->language) && $this->isValidLanguage($_SESSION['user']->language)) {
            $this->currentLang = $_SESSION['user']->language;
        }

        $this->loadLanguage($this->currentLang);
    }

    private function loadLanguage(string $lang): void
    {
        $langFile = __DIR__ . "/../../translations/{$lang}.php";

        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            $this->translations = require __DIR__ . '/../../translations/en.php';
            $this->currentLang  = 'en';
        }
    }

    private function isValidLanguage(string $lang): bool
    {
        return array_key_exists($lang, $this->getAvailableLanguages());
    }
}
