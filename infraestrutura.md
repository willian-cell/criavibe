# Infraestrutura CriaVibe

Este documento descreve a infraestrutura atual do CriaVibe.

## Arquitetura Atual

```text
Navegador
  |
  v
Railway - Servico CRIAVIBE
  |
  |-- PHP nativo via Docker
  |-- router.php
  |-- endpoints em /api
  |
  +--> Railway MySQL via endpoint privado
  |
  +--> Cloudflare R2 para fotos e capas
```

## Servico da Aplicacao

- Hospedagem: Railway.
- Runtime: Docker com `php:8.2-cli`.
- Comando: `php -S 0.0.0.0:${PORT:-8080} router.php`.
- Porta: fornecida pelo Railway via `PORT`.

## Banco de Dados

- Tipo: MySQL.
- Provedor: Railway MySQL.
- Conexao recomendada: `MYSQL_URL` privado.
- Evitar: `MYSQL_PUBLIC_URL`, `RAILWAY_TCP_PROXY_DOMAIN` e endpoints publicos para conexao interna.

## Storage

- Provedor: Cloudflare R2.
- Uso: armazenamento de fotos, capas e arquivos de galeria.
- Integracao: `api/lib/R2Storage.php`.

## Variaveis Necessarias

```env
MYSQL_URL=${{MySQL.MYSQL_URL}}
R2_ACCOUNT_ID=
R2_BUCKET_NAME=
R2_PUBLIC_URL=
R2_ACCESS_KEY_ID=
R2_SECRET_KEY=
SECRET_KEY=
```

## Bootstrap do Banco

`api/db_migrations.php` e idempotente:

- cria tabelas quando o MySQL esta vazio;
- adiciona colunas faltantes em bancos existentes;
- permite execucao inicial antes de existir usuario;
- exige sessao de `admin` ou `fotografo` quando ja existem usuarios.

Tabelas mantidas:

- `usuarios`
- `clientes`
- `galerias`
- `imagens`
- `musicas`

## Arquivos Removidos

Foram removidos arquivos que nao fazem parte do runtime atual ou eram risco operacional:

- `reset_admin.php`
- `check_db.php`
- `check_deploy.php`
- `check_limits.php`
- `api/teste_db.php`
- `api/test_r2.php`
- `api/ver_logs.php`
- `Manual_Tecnico_criavibe_site.pdf`
- registros antigos e desatualizados do agente WillianBO
- referencia externa de manual tecnico que nao pertence ao CriaVibe
- `CREDENCIAIS.md` local

## Validacao Registrada

- Conexao Railway com MySQL: ok.
- Migracao `/api/db_migrations.php`: ok.
- Cadastro: ok.
- Login: ok.
- Sessao `/api/auth/me.php`: ok.

## Regras de Manutencao

- Nao versionar `.env`, credenciais, logs ou uploads reais.
- Nao adicionar endpoints publicos de debug em producao.
- Atualizar este documento quando deploy, banco ou storage mudarem.
