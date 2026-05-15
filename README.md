# CriaVibe

CriaVibe e um sistema web para fotografos criarem galerias, enviarem fotos em alta resolucao, permitirem selecao de imagens pelos clientes e entregarem arquivos em uma experiencia visual profissional.

## Stack Atual

- Frontend: HTML, CSS e JavaScript Vanilla.
- Backend: PHP nativo em endpoints dentro de `api/`.
- Banco de dados: MySQL.
- Deploy principal: Railway com Docker.
- Storage de midia: Cloudflare R2.

## Estrutura Principal

| Caminho | Funcao |
|---|---|
| `index.html` | Pagina inicial publica. |
| `saiba_mais.html` | Pagina institucional com hero em video. |
| `entrar.html` | Login e cadastro de fotografos. |
| `painel.html` | Dashboard principal do fotografo. |
| `galeria.html` | Gerenciamento de uma galeria. |
| `cliente.html` | Experiencia de acesso do cliente. |
| `clientes.html` | Gestao de clientes. |
| `configuracoes.html` | Configuracoes do sistema. |
| `api/` | Endpoints PHP do backend. |
| `api/db_migrations.php` | Bootstrap e migracoes idempotentes do MySQL. |
| `assets/` | CSS, JS, imagens e videos. |
| `logo/` | Logo institucional. |
| `uploads/` | Estrutura local/fallback, sem arquivos de clientes versionados. |
| `Dockerfile` | Build de producao para Railway. |
| `router.php` | Router do servidor PHP no container. |

## Variaveis de Ambiente

No Railway, configure as variaveis no servico da aplicacao CriaVibe.

Preferencial:

```env
MYSQL_URL=${{MySQL.MYSQL_URL}}
```

Alternativas suportadas:

```env
MYSQLHOST=
MYSQLPORT=
MYSQLDATABASE=
MYSQLUSER=
MYSQLPASSWORD=
```

Cloudflare R2:

```env
R2_ACCOUNT_ID=
R2_BUCKET_NAME=
R2_PUBLIC_URL=
R2_ACCESS_KEY_ID=
R2_SECRET_KEY=
```

Nao use `MYSQL_PUBLIC_URL` para conexao interna entre servicos Railway. Use o endpoint privado para evitar egress.

## Deploy Railway

1. Faça push para o repositorio conectado ao Railway.
2. Garanta que o Railway use o `Dockerfile`.
3. Configure `MYSQL_URL` no servico CriaVibe.
4. Configure as variaveis do Cloudflare R2.
5. Apos o primeiro deploy, execute:

```text
/api/db_migrations.php
```

Esse endpoint cria o schema base quando o banco esta vazio e adiciona colunas faltantes em bancos existentes.

## Desenvolvimento Local

Crie um `.env` local a partir de `env_example.txt` e rode:

```bash
php -S localhost:8000 router.php
```

O `.env` nunca deve ser versionado.

## Seguranca e Limpeza

- Arquivos publicos de diagnostico e reset foram removidos.
- Credenciais reais ficam apenas em variaveis de ambiente ou `.env` local ignorado.
- Logs, uploads e credenciais nao entram no Git.
- O endpoint de migracao permite bootstrap em banco vazio; depois que houver usuarios, exige sessao de `admin` ou `fotografo`.
