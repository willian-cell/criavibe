# 🚀 Guia de Deploy — Criavibe na Hostinger

## Credenciais de Acesso

| Tipo | Host | Usuário | Porta |
|------|------|---------|-------|
| **SSH** | `212.85.6.13` | `u276112142` | `65002` |
| **FTP** | `212.85.6.13` | `u276112142` | `21` |
| **Pasta** | `public_html/` | — | — |

---

## Passo 1 — Verificar Python no hPanel
hPanel → **Avançado → Python**
- Se não existir, use **git** ou deploy manual via SSH

## Passo 2 — Enviar arquivos via FTP (FileZilla)

**Conectar no FileZilla:**
- Host: `ftp://212.85.6.13`
- Usuário: `u276112142`
- Porta: `21`
- Senha: Deusjeova159$

**Enviar para `public_html/`:**
```
app.py          config.py        db.py
r2_storage.py   passenger_wsgi.py .htaccess
.env            requirements.txt  criar_admin.py
static/         templates/
```
> ⚠️ **NÃO envie:** `venv/`, `__pycache__/`, `.git/`

## Passo 3 — Instalar via SSH

No terminal (PowerShell/CMD):
```bash
ssh u276112142@212.85.6.13 -p 65002
```

Após conectar:
```bash
cd ~/domains/red-llama-690371.hostingersite.com/public_html
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python criar_admin.py
```

## Passo 4 — Configurar app Python no hPanel
hPanel → Avançado → Python:
- Root: `domains/red-llama-690371.hostingersite.com/public_html`
- Startup file: `passenger_wsgi.py`
- Clicar **Restart**

## URLs Finais
- **Site:** https://red-llama-690371.hostingersite.com
- **Banco:** `u276112142_criavibe` @ `srv2022.hstgr.io`
- **Fotos:** Cloudflare R2 `criavibe-galeria`


## Pré-requisitos
- Acesso ao hPanel da Hostinger
- SSH habilitado (hPanel → Avançado → SSH Access)
- FTP credentials (hPanel → Arquivos → FTP Accounts)

## Passos do Deploy

### 1️⃣ Habilitar Python no hPanel
1. hPanel → **Avançado → Python**
2. Versão: **3.x** (a mais recente disponível)
3. Root do app: `/home/u276112142/domains/red-llama-690371.hostingersite.com/public_html`
4. Startup file: `passenger_wsgi.py`
5. Clique em **Create**

### 2️⃣ Enviar arquivos via FTP
Use o **FileZilla** ou similar:

**Servidor FTP:** `ftp.red-llama-690371.hostingersite.com`
**Usuário/Senha:** (hPanel → Arquivos → FTP)

Envie estes arquivos/pastas para `public_html/`:
```
app.py
config.py
db.py
r2_storage.py
passenger_wsgi.py
.htaccess
.env
requirements.txt
criar_admin.py
static/
templates/
```
> ⚠️ **NÃO envie** a pasta `venv/` — ela será criada no servidor.

### 3️⃣ Instalar dependências via SSH
```bash
cd /home/u276112142/domains/red-llama-690371.hostingersite.com/public_html
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 4️⃣ Criar usuário Admin
```bash
python criar_admin.py
```

### 5️⃣ Reiniciar o app
No hPanel → Python → **Restart** 

---

## URLs Finais
- **Site:** https://red-llama-690371.hostingersite.com
- **Banco:** `u276112142_criavibe` @ `srv2022.hstgr.io`
- **Fotos:** Cloudflare R2 `criavibe-galeria`
