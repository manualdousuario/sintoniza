<?php

class Language {
    private static $instance = null;
    private $translations = [];
    private $currentLang = 'en';
    private $db;

    private function __construct() {
        try {
            require_once __DIR__ . '/DB.php';
            $this->db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT);
        } catch (Exception $e) {
            error_log("Error connecting to database: " . $e->getMessage());
            $this->db = null;
        }

        // Carrega o idioma inicial
        $this->loadInitialLanguage();
    }

    private function loadInitialLanguage() {
        if (isset($_SESSION['user'], $_SESSION['user']->id) && $this->db !== null) {
            try {
                $result = $this->db->firstRow(
                    "SELECT language FROM users WHERE id = ?",
                    $_SESSION['user']->id
                );

                if ($result && isset($result->language) && $this->isValidLanguage($result->language)) {
                    $this->currentLang = $result->language;
                }
            } catch (Exception $e) {
                error_log("Error loading initial language: " . $e->getMessage());
            }
        }

        // Carrega os arquivos de tradução
        $this->loadLanguage($this->currentLang);
    }

    public function getCurrentLanguage() {
        // Se tem usuário logado e conexão com banco, sempre busca do banco
        if (isset($_SESSION['user'], $_SESSION['user']->id) && $this->db !== null) {
            try {
                $result = $this->db->firstRow(
                    "SELECT language FROM users WHERE id = ?",
                    $_SESSION['user']->id
                );

                if ($result && isset($result->language) && $this->isValidLanguage($result->language)) {
                    $this->currentLang = $result->language;
                }
            } catch (Exception $e) {
                error_log("Error getting current language: " . $e->getMessage());
            }
        }

        return $this->currentLang;
    }

    public function setLanguage($lang) {
        if (!$this->isValidLanguage($lang)) {
            return false;
        }

        $this->currentLang = $lang;

        // Se tem usuário logado e conexão com banco, atualiza no banco
        if (isset($_SESSION['user'], $_SESSION['user']->id) && $this->db !== null) {
            try {
                $this->db->simple(
                    "UPDATE users SET language = ? WHERE id = ?",
                    $lang,
                    $_SESSION['user']->id
                );
            } catch (Exception $e) {
                error_log("Error setting language: " . $e->getMessage());
                return false;
            }
        }

        $this->loadLanguage($lang);
        return true;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadLanguage($lang) {
        $langFile = __DIR__ . "/languages/{$lang}.php";
        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            $this->translations = require __DIR__ . "/languages/en.php";
            $this->currentLang = 'en';
        }
    }

    public function get($key) {
        $keys = explode('.', $key);
        $value = $this->translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function getAvailableLanguages() {
        return [
            'en' => 'English',
            'es' => 'Español',
            'pt-BR' => 'Português (Brasil)'
        ];
    }

    private function isValidLanguage($lang) {
        return array_key_exists($lang, $this->getAvailableLanguages());
    }
}

// Helper function for easy translation
function __($key) {
    return Language::getInstance()->get($key);
}
