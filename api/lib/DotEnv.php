<?php
/**
 * DotEnv Loader - CriaVibe
 * Carrega variaveis de um arquivo .env para o ambiente PHP.
 */
class DotEnv {
    public static function load($path, $required = false) {
        if (!file_exists($path)) {
            if ($required) {
                die("Erro Critico: Arquivo .env nao encontrado em: " . $path);
            }
            return false;
        }

        $content = file_get_contents($path);
        $content = str_replace("\xEF\xBB\xBF", '', $content);

        $lines = explode("\n", str_replace("\r", "", $content));
        error_log("DotEnv: Lendo conteudo. Total de linhas: " . count($lines));

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $value = trim($value, "\"' \t\n\r\0\x0B");

                if ($name && (getenv($name) === false || getenv($name) === '')) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        return true;
    }
}
