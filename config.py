# config.py
import os
from dotenv import load_dotenv

# Carregar variáveis do .env antes de qualquer configuração
load_dotenv()

# Caminho para uploads de imagens
UPLOAD_FOLDER = os.path.join(os.getcwd(), 'static', 'uploads')
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}

# Configurações do banco de dados MySQL - usar variáveis de ambiente
DB_CONFIG = {
    "user": os.environ.get('DB_USER', 'root'),
    "password": os.environ.get('DB_PASSWORD', ''),
    "host": os.environ.get('DB_HOST', 'localhost'),
    "database": os.environ.get('DB_NAME', 'criavibe'),
    "charset": 'utf8mb4',
    "autocommit": True
}

# Configurações de segurança
SECRET_KEY = os.environ.get('SECRET_KEY', 'algum_segredo_muito_forte')

# FIX #14: MAX_CONTENT_LENGTH exportado para ser aplicado no app
MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16MB max file size

# FIX #13: get_db_connection() removida — use db.conectar() em todo o projeto
def validate_config():
    """Valida as configurações necessárias"""
    required_env_vars = ['DB_PASSWORD', 'SECRET_KEY']
    missing_vars = [var for var in required_env_vars if not os.environ.get(var)]
    
    if missing_vars:
        print(f"Aviso: Variáveis de ambiente não definidas: {missing_vars}")
        print("Usando valores padrão (não recomendado para produção)")
    
    return True
