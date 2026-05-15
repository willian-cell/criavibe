# CriaVibe

CriaVibe é um sistema web para fotógrafos criarem galerias, enviarem fotos em alta resolução, permitirem seleção de imagens pelos clientes e entregarem uma experiência de visualização profissional.

## Arquitetura

- Frontend: HTML, CSS, JavaScript vanilla.
- Backend: PHP nativo (`api/`).
- Banco de dados: MySQL.
- Storage de mídia: Cloudflare R2.
- Filas e worker: Redis + PHP worker.
- Deploy principal: Railway com Docker.

## Estrutura do Repositório

| Caminho | Função |
|---|---|
| `index.html` | Página inicial pública. |
| `saiba_mais.html` | Página institucional com hero em vídeo. |
| `entrar.html` | Login e cadastro de fotógrafos. |
| `painel.html` | Dashboard do fotógrafo. |
| `galeria.html` | Gerenciamento de galeria e upload direto. |
| `cliente.html` | Acesso do cliente para visualização e seleção. |
| `clientes.html` | Gestão de clientes. |
| `configuracoes.html` | Configurações do sistema. |
| `api/` | Endpoints PHP do backend. |
| `api/db_migrations.php` | Criação de schema e migrações idempotentes. |
| `api/workers/image_worker.php` | Worker de processamento de imagem. |
| `api/lib/Queue.php` | Wrapper Redis para fila. |
| `api/lib/RateLimiter.php` | Limitação de taxa para preparações de upload. |
| `assets/` | CSS, JS, imagens e vídeos. |
| `Dockerfile` | Build do container para Railway. |
| `Procfile` | Define processos `web` e `worker`. |
| `docker-compose.yml` | Ambiente local com web, worker, Redis e MySQL. |
| `scripts/` | Exemplos de supervisor, systemd, Nginx e k6. |
| `DOCUMENTATION/` | Guias de deploy, testes e particionamento. |

## Variáveis de Ambiente

O projeto usa `.env` local para desenvolvimento e variáveis Railway em produção.

### Banco

```env
MYSQL_URL=
```

Ou alternativa:

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

```env
REDIS_URL=
REDIS_HOST=
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
WORKER_QUEUE_NAME=image_jobs
WORKER_POLL_TIMEOUT=5
```

### Feature flag

```env
FORCE_DIRECT_UPLOAD=1
```

## Deploy no Railway

### 1. Conectar repositório

- Crie um novo projeto Railway ou conecte o repo existente.
- Configure o serviço para usar `Dockerfile` na raiz.
- Garanta que o deploy esteja apontando para a branch correta.

### 2. Variáveis de ambiente

Adicione no Railway as mesmas variáveis listadas acima. Se usar um serviço Redis do Railway, use o `REDIS_URL` fornecido.

### 3. Criar serviço web

- Crie um serviço Railway para a aplicação web.
- Ele deve usar o processo `web` do `Procfile`, que já está presente no repositório.
- O comando padrão será:

```bash
sh -c "php -S 0.0.0.0:${PORT:-8080} router.php"
```

### 4. Criar serviço worker

No Railway, crie um segundo serviço a partir do mesmo repositório com o comando de start:

```bash
php api/workers/image_worker.php
```

Esse serviço consome jobs Redis e processa miniaturas/derivados de imagens.

### 5. Executar migrações

Após o deploy inicial, execute o endpoint de migração:

```text
https://<sua-app>.up.railway.app/api/db_migrations.php
```

Ou via Railway CLI, se preferir:

```bash
railway run php api/db_migrations.php
```

## Como testar uploads diretos

### Fluxo esperado

1. `galeria.html` pede `direct_prepare.php` para gerar URLs assinadas.
2. O navegador faz `PUT` direto ao Cloudflare R2 usando `upload_url`.
3. Em seguida, `direct_confirm.php` registra os metadados no banco e enfileira jobs.

### Teste mínimo de validação

- Abra a galeria no frontend.
- Arraste/seleciona fotos e observe o progresso.
- O cliente deve enviar apenas para R2, não para `/api/fotos/upload.php`.

### Validar na rede do navegador

- Abra DevTools → aba `Network`.
- Verifique chamadas para:
  - `/api/fotos/direct_prepare.php`
  - URLs `PUT` geradas pelo R2
  - `/api/fotos/direct_confirm.php`
- Se algum PUT falhar, o problema está na assinatura R2/CORS ou credenciais.
- Agora o `direct_prepare` também aceita HEIC/HEIF/AVIF/SVG/TIFF e outros formatos populares, usando fallback por extensão quando o navegador não envia um MIME type completo.

## Como validar logs do worker no Railway

### Logs via Railway UI

- Abra o projeto Railway.
- Selecione o serviço `worker` criado.
- Vá para a aba `Logs`.
- Procure por mensagens com prefixo:

```text
[image_worker]
```

### Logs via Railway CLI

```bash
railway logs -s <worker-service-name>
```

### O que procurar

- conexão Redis bem-sucedida
- jobs consumidos
- uploads de derivados com sucesso
- erros de download/upload ou alteração de banco

## Como validar logs de upload direto

### No backend web

- Verifique logs do serviço web Railway.
- Procure pelos endpoints:
  - `/api/fotos/direct_prepare.php`
  - `/api/fotos/direct_confirm.php`

### No navegador

- Procure erros de CORS ou HTTP 4xx/5xx ao fazer o PUT direto ao R2.
- Verifique se a resposta de `direct_prepare.php` contém `upload_url` válidos.
- Verifique se o `.confirm` retorna `status: ok`.

## Desenvolvimento local com Docker

Use o `docker-compose.yml` para rodar tudo localmente:

```bash
docker-compose up --build
```

Isso sobe:
- `web` (app PHP)
- `worker` (processa jobs Redis)
- `redis`
- `db`

## Comandos úteis

```bash
cp .env.example .env
php api/db_migrations.php
php api/workers/image_worker.php
railway run php api/db_migrations.php
docker-compose up --build
```

## Notas de produção

- Use `FORCE_DIRECT_UPLOAD=1` para obrigar uploads apenas via R2.
- Monitore Redis, conexões DB e largura de banda do R2.
- Use partições de banco para tabelas maiores que 1M linhas.
- O worker deve ser escalado separadamente do web.
