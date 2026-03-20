"""
Script de deploy automático para Hostinger via SSH/SCP
Execute: venv\Scripts\python deploy.py

IMPORTANTE: DB_HOST no servidor Hostinger deve ser 127.0.0.1 (não srv2022.hstgr.io)
"""
import os
import paramiko
from scp import SCPClient

# ── Credenciais SSH ──────────────────────────────────────────
SSH_HOST = "212.85.6.13"
SSH_PORT = 65002
SSH_USER = "u276112142"
SSH_PASS = "Deusjeova159$"
DOMAIN = "red-llama-690371.hostingersite.com"
REMOTE_DIR = f"/home/{SSH_USER}/domains/{DOMAIN}/public_html"
VENV_PIP    = f"{REMOTE_DIR}/venv/bin/pip"
VENV_PYTHON = f"{REMOTE_DIR}/venv/bin/python"

# ── Arquivos/pastas a enviar ─────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ARQUIVOS = [
    "app.py", "config.py", "db.py", "r2_storage.py",
    "passenger_wsgi.py", ".htaccess", ".env",
    "requirements.txt", "criar_admin.py",
]
PASTAS = ["static", "templates"]


def ssh_connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"🔗 Conectando em {SSH_HOST}:{SSH_PORT}...")
    client.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, password=SSH_PASS, timeout=30)
    print("✅ SSH conectado!")
    return client


def ssh_run(client, cmd, desc=""):
    if desc:
        print(f"\n▶ {desc}")
    stdin, stdout, stderr = client.exec_command(cmd, get_pty=True)
    stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    if out:
        print(out[-600:])
    return out


def upload_files(client):
    print("\n📤 Enviando arquivos...")
    with SCPClient(client.get_transport()) as scp:
        for arq in ARQUIVOS:
            local = os.path.join(BASE_DIR, arq)
            if os.path.exists(local):
                scp.put(local, f"{REMOTE_DIR}/{arq}")
                print(f"  ✓ {arq}")
            else:
                print(f"  ⚠ {arq} não encontrado — pulando")
        for pasta in PASTAS:
            local = os.path.join(BASE_DIR, pasta)
            if os.path.exists(local):
                scp.put(local, REMOTE_DIR, recursive=True)
                print(f"  ✓ {pasta}/")


def main():
    client = ssh_connect()

    upload_files(client)

    # Criar venv sem pip (Hostinger não tem ensurepip)
    ssh_run(client,
        f"cd {REMOTE_DIR} && rm -rf venv && python3 -m venv venv --without-pip",
        "Criando venv..."
    )

    # Instalar pip manualmente
    ssh_run(client,
        f"cd {REMOTE_DIR} && curl -s https://bootstrap.pypa.io/get-pip.py -o get-pip.py && venv/bin/python3 get-pip.py -q && rm get-pip.py",
        "Instalando pip..."
    )

    # Instalar dependências
    ssh_run(client,
        f"cd {REMOTE_DIR} && {VENV_PIP} install -r requirements.txt -q",
        "Instalando dependências..."
    )
    print("✅ Dependências instaladas!")

    # Garantir DB_HOST=127.0.0.1 no servidor
    ssh_run(client,
        f"sed -i 's/DB_HOST=srv2022.hstgr.io/DB_HOST=127.0.0.1/' {REMOTE_DIR}/.env",
        "Ajustando DB_HOST para produção..."
    )

    # Criar admin
    ssh_run(client,
        f"cd {REMOTE_DIR} && {VENV_PYTHON} criar_admin.py",
        "Criando usuário admin..."
    )

    print(f"\n🎉 Deploy concluído!")
    print(f"   🌐 Site: https://{DOMAIN}")
    print(f"   ⚙️ Ativar em: hPanel → Avançado → Python → Startup: passenger_wsgi.py")
    client.close()


if __name__ == "__main__":
    main()
