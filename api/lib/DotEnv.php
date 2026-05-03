<?php
/**
 * DotEnv Loader - CriaVibe
 * Carrega variáveis de um arquivo .env para o ambiente PHP.
 */
class DotEnv {
    public static function load($path) {
        if (!file_exists($path)) {
            die("Erro Crítico: Arquivo .env não encontrado em: " . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        error_log("DotEnv: Lendo arquivo. Total de linhas: " . count($lines));

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '#') === 0) continue;

            // Busca por chave=valor usando uma lógica mais simples e direta
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remove aspas se existirem
                $value = trim($value, "\"' \t\n\r\0\x0B");

                if ($name) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}
