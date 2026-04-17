<?php

declare(strict_types=1);

namespace Sintoniza\Library;

class Language
{
    private static ?self $instance = null;
    private array $translations = [];
    private string $currentLang = 'en';
    private bool $resolved = false;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getCurrentLanguage(): string
    {
        $this->resolve();
        return $this->currentLang;
    }

    public function setLanguage(string $lang): bool
    {
        if (!$this->isValidLanguage($lang)) {
            return false;
        }

        $this->currentLang = $lang;
        $this->loadLanguage($lang);
        $this->resolved = true;

        return true;
    }

    public function get(string $key): string
    {
        $this->resolve();

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

    public static function translate(string $lang, string $key): string
    {
        $langFile = __DIR__ . "/../../translations/{$lang}.php";

        if (!file_exists($langFile)) {
            $langFile = __DIR__ . '/../../translations/en.php';
        }

        $translations = require $langFile;
        $keys         = explode('.', $key);
        $value        = $translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        return (string) $value;
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->currentLang = $this->detectLanguage();
        $this->loadLanguage($this->currentLang);
        $this->resolved = true;
    }

    private function detectLanguage(): string
    {
        if (isset($_SESSION['user']->language) && $this->isValidLanguage($_SESSION['user']->language)) {
            return $_SESSION['user']->language;
        }

        if (isset($_SESSION['language']) && is_string($_SESSION['language']) && $this->isValidLanguage($_SESSION['language'])) {
            return $_SESSION['language'];
        }

        $fromHeader = $this->detectFromAcceptLanguage();
        if ($fromHeader !== null) {
            return $fromHeader;
        }

        return 'en';
    }

    private function detectFromAcceptLanguage(): ?string
    {
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($header === '') {
            return null;
        }

        $languages = [];
        foreach (explode(',', $header) as $part) {
            $sub  = explode(';', trim($part));
            $code = trim($sub[0]);
            if ($code === '') {
                continue;
            }
            $q = 1.0;
            if (isset($sub[1]) && preg_match('/q=([0-9.]+)/', $sub[1], $m)) {
                $q = (float) $m[1];
            }
            $languages[] = ['code' => $code, 'q' => $q];
        }

        usort($languages, fn($a, $b) => $b['q'] <=> $a['q']);

        foreach ($languages as $l) {
            $code = $l['code'];
            if ($this->isValidLanguage($code)) {
                return $code;
            }
            $prefix = strtolower(substr($code, 0, 2));
            if ($prefix === 'pt') {
                return 'pt-BR';
            }
            if ($prefix === 'es') {
                return 'es';
            }
            if ($prefix === 'en') {
                return 'en';
            }
        }

        return null;
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
