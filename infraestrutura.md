# CriaVibe — Análise de Infraestrutura e Deploy

## Arquivos do Sistema — O que é Usado

### Núcleo da Aplicação (ESSENCIAIS)
| Arquivo | Papel |
|---|---|
| [app.py](file:///c:/Users/willi/Documents/criavibe_site/app.py) | **Servidor principal** — todas as rotas, lógica e APIs |
| [db.py](file:///c:/Users/willi/Documents/criavibe_site/db.py) | **Banco de dados** — todas as funções de acesso ao MySQL |
| [config.py](file:///c:/Users/willi/Documents/criavibe_site/config.py) | **Configuração** — variáveis de ambiente, paths, DB config |
| [r2_storage.py](file:///c:/Users/willi/Documents/criavibe_site/r2_storage.py) | **Storage** — upload/delete no Cloudflare R2 |
| [requirements.txt](file:///c:/Users/willi/Documents/criavibe_site/requirements.txt) | **Dependências** Python — Flask, mysql-connector, boto3, etc. |
| [.env](file:///c:/Users/willi/Documents/criavibe_site/.env) | **Credenciais** locais — nunca vai para o GitHub |

### Database (ESSENCIAIS)
| Arquivo | Papel |
|---|---|
| [schema_final.sql](file:///c:/Users/willi/Documents/criavibe_site/schema_final.sql) | **Schema COMPLETO** — use para banco novo |
| [migration_v3.sql](file:///c:/Users/willi/Documents/criavibe_site/migration_v3.sql) | **Migração segura** — use para banco existente |

### Deploy / Produção (ESSENCIAIS)
| Arquivo | Papel |
|---|---|
| [passenger_wsgi.py](file:///c:/Users/willi/Documents/criavibe_site/passenger_wsgi.py) | **WSGI da Hostinger** — ponto de entrada do servidor |
| [Procfile](file:///c:/Users/willi/Documents/criavibe_site/Procfile) | Para plataformas como Railway, Heroku |
| [.htaccess](file:///c:/Users/willi/Documents/criavibe_site/.htaccess) | Configuração do Apache na Hostinger |

### Templates (ESSENCIAIS — todos em uso)
[index.html](file:///c:/Users/willi/Documents/criavibe_site/templates/index.html), [entrar.html](file:///c:/Users/willi/Documents/criavibe_site/templates/entrar.html), [painel_usuario.html](file:///c:/Users/willi/Documents/criavibe_site/templates/painel_usuario.html), [nova_galeria.html](file:///c:/Users/willi/Documents/criavibe_site/templates/nova_galeria.html), [gerenciar_galeria.html](file:///c:/Users/willi/Documents/criavibe_site/templates/gerenciar_galeria.html), [galeria.html](file:///c:/Users/willi/Documents/criavibe_site/templates/galeria.html), [clientes.html](file:///c:/Users/willi/Documents/criavibe_site/templates/clientes.html), [editar_galeria.html](file:///c:/Users/willi/Documents/criavibe_site/templates/editar_galeria.html), [configuracoes.html](file:///c:/Users/willi/Documents/criavibe_site/templates/configuracoes.html), [admin_usuarios.html](file:///c:/Users/willi/Documents/criavibe_site/templates/admin_usuarios.html), [precos.html](file:///c:/Users/willi/Documents/criavibe_site/templates/precos.html), [saiba_mais.html](file:///c:/Users/willi/Documents/criavibe_site/templates/saiba_mais.html)

### Static (ESSENCIAL)
[static/dashboard.css](file:///c:/Users/willi/Documents/criavibe_site/static/dashboard.css) — sistema de design premium de todo o frontend

### Utilitários (OPCIONAIS mas úteis)
| Arquivo | Para que serve |
|---|---|
| [criar_admin.py](file:///c:/Users/willi/Documents/criavibe_site/criar_admin.py) | Cria o primeiro usuário admin no banco |
| [env_example.txt](file:///c:/Users/willi/Documents/criavibe_site/env_example.txt) | Template do .env para novos deploys |
| [README.md](file:///c:/Users/willi/Documents/criavibe_site/README.md), [DEPLOY.md](file:///c:/Users/willi/Documents/criavibe_site/DEPLOY.md), [CREDENCIAIS.md](file:///c:/Users/willi/Documents/criavibe_site/CREDENCIAIS.md) | Documentação |

---

## Arquivos Removidos (Obsoletos)
- [migration_add_r2_key.sql](file:///c:/Users/willi/Documents/criavibe_site/migration_add_r2_key.sql) — substituído pelo migration_v3.sql
- [migration_v2.sql](file:///c:/Users/willi/Documents/criavibe_site/migration_v2.sql) — substituído pelo migration_v3.sql
- [schema_completo.sql](file:///c:/Users/willi/Documents/criavibe_site/schema_completo.sql) — substituído pelo schema_final.sql
- [fix_server.py](file:///c:/Users/willi/Documents/criavibe_site/fix_server.py) — script de correção pontual, não mais necessário
- [deploy.py](file:///c:/Users/willi/Documents/criavibe_site/deploy.py) — script antigo de deploy descartado
- [render.yaml](file:///c:/Users/willi/Documents/criavibe_site/render.yaml) — configuração do Render.com (não usado)
- [.env.render](file:///c:/Users/willi/Documents/criavibe_site/.env.render) — variáveis do Render duplicadas
- [templates/upload_multiplas.html](file:///c:/Users/willi/Documents/criavibe_site/templates/upload_multiplas.html) — rota migrada para async no gerenciar_galeria

---

## Onde Está Hospedado

### Situação Atual (Local + Parcialmente Online)
```
┌─────────────────────────────────────────────────────┐
│ COMPUTADOR LOCAL (Windows)                          │
│  └─ python app.py (porta 5000)                      │
│     só você acessa: http://127.0.0.1:5000           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ BANCO DE DADOS — Hostinger MySQL                    │
│  Host:  srv1552.hstgr.io                            │
│  Banco: u276112142_criavibe                         │
│  User:  u276112142_willi                            │
│  ✅ já online, compartilhado com qualquer servidor  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ STORAGE DE FOTOS — Cloudflare R2                    │
│  Bucket: criavibe                                   │
│  ✅ já online, CDN global = fotos carregam rápido   │
└─────────────────────────────────────────────────────┘
```

---

## O Problema: o Servidor Flask Ainda não Está na Internet

O banco e as fotos já estão na internet. **O que falta é o servidor Flask estar acessível publicamente.**

---

## Opções para Colocar o CriaVibe na Internet

### Opção A — Hostinger Hatchling (você já tem o plano)

O [passenger_wsgi.py](file:///c:/Users/willi/Documents/criavibe_site/passenger_wsgi.py) já existe no projeto para isso.

**Passos:**
1. Acessar Hostinger → hPanel → Websites → Seu domínio
2. File Manager → `public_html/criavibe/` — fazer upload de todos os arquivos (exceto venv)
3. SSH no painel → instalar dependências:
   ```
   pip3 install -r requirements.txt --user
   ```
4. Configurar [.htaccess](file:///c:/Users/willi/Documents/criavibe_site/.htaccess) para apontar para o [passenger_wsgi.py](file:///c:/Users/willi/Documents/criavibe_site/passenger_wsgi.py)
5. Criar arquivo [.env](file:///c:/Users/willi/Documents/criavibe_site/.env) no servidor com as variáveis de produção
6. Reiniciar o servidor pelo hPanel

> [!IMPORTANT]
> O plano Hostinger Single/Starter **não suporta Python**. É necessário o plano Business ou superior com Python habilitado.

---

### Opção B — Railway.app (MAIS FÁCIL — recomendado)

Railway detecta Flask automaticamente e tudo funciona com apenas `git push`.

**Passos:**
1. Acesse railway.app → New Project → Deploy from GitHub
2. Selecione o repositório `criavibe`
3. Em **Variables**, adicione:
   ```
   DB_HOST=srv1552.hstgr.io
   DB_USER=u276112142_willi
   DB_PASSWORD=sua_senha
   DB_NAME=u276112142_criavibe
   SECRET_KEY=uma_chave_muito_longa_e_aleatoria
   R2_ACCESS_KEY_ID=sua_chave_r2
   R2_SECRET_KEY=sua_chave_secreta_r2
   R2_BUCKET_NAME=criavibe
   R2_ENDPOINT_URL=https://seu_id.r2.cloudflarestorage.com
   ```
4. Railway gera automaticamente uma URL pública: `https://criavibe.up.railway.app`
5. Apontar domínio próprio nas configurações DNS (opcional)

**Custo:** ~$5/mês (plano Hobby) — inclui a URL pública

---

### Opção C — Render.com (Gratuito com limitações)

O [Procfile](file:///c:/Users/willi/Documents/criavibe_site/Procfile) já existe para isso. No plano gratuito o servidor "dorme" após inatividade, o que causa lentidão na primeira requisição.

---

## Resumo do Ciclo para Funcionar na Internet

```
 Código (GitHub)
      │  git push
      ▼
 Servidor Flask (Railway/Hostinger)
      │  lê variáveis .env de produção
      │  conecta ao banco e ao R2
      ▼
 Banco MySQL (Hostinger)     Fotos (Cloudflare R2)
      │                             │
      └──────────┬──────────────────┘
                 ▼
         Usuário final via navegador
         qualquer dispositivo/país
```

## Variáveis de Ambiente Necessárias na Produção

```env
DB_HOST=srv1552.hstgr.io
DB_USER=u276112142_willi
DB_PASSWORD=***
DB_NAME=u276112142_criavibe
SECRET_KEY=***  (chave longa e aleatória — NUNCA reutilize)
R2_ACCESS_KEY_ID=***
R2_SECRET_KEY=***
R2_BUCKET_NAME=criavibe
R2_ENDPOINT_URL=https://***.r2.cloudflarestorage.com
FLASK_DEBUG=false
```

> [!CAUTION]
> Nunca faça commit do [.env](file:///c:/Users/willi/Documents/criavibe_site/.env) com credenciais reais. O [.gitignore](file:///c:/Users/willi/Documents/criavibe_site/.gitignore) já protege isso.


testar loca:
php -S localhost:8000
