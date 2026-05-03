<?php
/**
 * DotEnv Loader - CriaVibe
 * Carrega variáveis de um arquivo .env para o ambiente PHP.
 */
class DotEnv {
    public static function load($path) {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '#') === 0) continue;

            if (strpos($line, '=') === false) continue;

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remover aspas se existirem
            $value = trim($value, '"\'');

            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
