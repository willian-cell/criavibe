# CriaVibe Railway Deployment

Este guia descreve como implantar o CriaVibe no Railway e como configurar o worker de processamento de imagens.

## Pré-requisitos

- Conta Railway ativa
- Repositório Git conectado ao Railway
- Projeto CriaVibe com `Dockerfile` na raiz
- Serviços Redis e MySQL configurados no Railway ou variáveis de conexão externas

## Arquivos importantes

- `Dockerfile` - Build do container PHP para Railway.
- `Procfile` - Define os processos `web` e `worker`.
- `docker-compose.yml` - Ambiente local para testes.
- `api/db_migrations.php` - aplica schema e migrações no MySQL.
- `api/workers/image_worker.php` - worker de miniaturas e processamento de imagens.
- `api/fotos/direct_prepare.php` - prepara uploads diretos para Cloudflare R2.
- `api/fotos/direct_confirm.php` - confirma uploads e registra metadados.
- `api/lib/Queue.php` - wrapper Redis para fila.
- `api/lib/RateLimiter.php` - limita chamadas de prepare para evitar abuso.

## Variáveis de ambiente necessárias

### Banco de dados

Use `MYSQL_URL` se estiver conectando o serviço MySQL do Railway.

```env
MYSQL_URL=${{MySQL.MYSQL_URL}}
```

Como alternativa:

```env
MYSQLHOST=
MYSQLPORT=
MYSQLDATABASE=
MYSQLUSER=
MYSQLPASSWORD=
```

### Cloudflare R2

```env
R2_ACCOUNT_ID=
R2_BUCKET_NAME=
R2_PUBLIC_URL=
R2_ACCESS_KEY_ID=
R2_SECRET_KEY=
```

### Redis / Worker

Se usar serviço Redis Railway, apenas forneça `REDIS_URL`.

```env
REDIS_URL=
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
WORKER_QUEUE_NAME=image_jobs
WORKER_POLL_TIMEOUT=5
```

### Forçar uploads diretos

```env
FORCE_DIRECT_UPLOAD=1
```

## Como configurar no Railway

### 1. Criar serviço Web

1. No Railway, adicione um novo serviço do tipo `Deploy from GitHub`.
2. Aponte para este repositório.
3. Garanta que o deploy use o `Dockerfile` na raiz.
4. Defina as variáveis de ambiente listadas acima no serviço.
5. Configure o build command padrão do Railway para usar o `Dockerfile` (Railway detecta automaticamente).

### 2. Criar serviço Worker

1. Crie um segundo serviço Railway baseado no mesmo repositório.
2. No campo de comando do serviço, defina:

```bash
php api/workers/image_worker.php
```

3. Use as mesmas variáveis de ambiente do serviço web.
4. Defina `WORKER_QUEUE_NAME=image_jobs` e `WORKER_POLL_TIMEOUT=5` se ainda não estiverem configuradas.

## Como rodar migrações

Após o primeiro deploy do serviço web, abra o endpoint de migração no navegador:

```text
https://<seu-app>.up.railway.app/api/db_migrations.php
```

Ou use Railway CLI:

```bash
railway run php api/db_migrations.php
```

Isso criará tabelas e colunas necessárias no banco MySQL.

## Validando o deploy

### Validar o serviço web

1. Acesse a URL pública do serviço web.
2. Verifique se a página inicial carrega sem erro.
3. No Railway dashboard, abra a aba de logs do serviço web.
4. Procure por erros em `/api/fotos/direct_prepare.php` e `/api/fotos/direct_confirm.php`.

### Validar o worker

1. No Railway dashboard, abra o serviço worker.
2. Verifique a aba de logs.
3. Procure mensagens com o prefixo:

```text
[image_worker]
```

4. Verifique se o worker está consumindo jobs e processando thumbnails.

## Logs e debugging

### Logs do worker

- Use Railway UI para ver os logs do serviço worker.
- Use Railway CLI:

```bash
railway logs -s <nome-do-servico-worker>
```

### Logs do upload direto

No serviço web, verifique logs para chamadas a:

- `/api/fotos/direct_prepare.php`
- `/api/fotos/direct_confirm.php`

No navegador:
- verifique a aba `Network` do DevTools
- confirme que os uploads usam `PUT` direto ao Cloudflare R2
- confirme que `direct_confirm.php` retorna `status: ok`

## Teste local rápido

No ambiente local você pode usar o `docker-compose.yml` com:

```bash
docker-compose up --build
```

Isso levanta:
- `web`
- `worker`
- `redis`
- `db`

## Comandos úteis

```bash
cp .env.example .env
php api/db_migrations.php
php api/workers/image_worker.php
docker-compose up --build
railway run php api/db_migrations.php
```

## Observações

- Use `FORCE_DIRECT_UPLOAD=1` para garantir que uploads grandes não passem pelo servidor PHP.
- Escale o worker separadamente do serviço web.
- Monitore Redis, conexões MySQL e uso do R2 para suportar muitos uploads simultâneos.
