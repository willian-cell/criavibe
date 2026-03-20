from dotenv import load_dotenv
load_dotenv()
from db import conectar
from werkzeug.security import generate_password_hash

conn = conectar()
if not conn:
    print("ERRO: Nao conectou ao banco!")
else:
    cur = conn.cursor()
    cur.execute("SELECT COUNT(*) FROM usuarios WHERE email='admin@criavibe.com'")
    count = cur.fetchone()[0]
    print(f"Admin existe: {count}")
    
    if count == 0:
        senha_hash = generate_password_hash('Deusjeova159$')
        cur.execute(
            "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (%s, %s, %s, %s)",
            ('Admin', 'admin@criavibe.com', senha_hash, 'admin')
        )
        conn.commit()
        print("Admin criado com sucesso!")
    else:
        print("Admin ja existe no banco.")
    conn.close()
