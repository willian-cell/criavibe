import paramiko
from scp import SCPClient

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('212.85.6.13', port=65002, username='u276112142', password='Deusjeova159$', timeout=30)

DIR = '/home/u276112142/domains/red-llama-690371.hostingersite.com/public_html'
PYTHON = f'{DIR}/venv/bin/python'

def run(cmd, desc=''):
    if desc:
        print(f'\n>> {desc}')
    stdin, stdout, stderr = client.exec_command(cmd, get_pty=True)
    stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    if out:
        print(out[-600:])
    return out

# Criar admin
run(f'cd {DIR} && {PYTHON} criar_admin.py', 'Criando admin...')

# Limpar arquivo de teste
run(f'rm -f {DIR}/test_db.py {DIR}/get-pip.py', 'Limpando arquivos temporários...')

client.close()
print('\n✅ FINALIZADO')
