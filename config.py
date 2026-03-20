# config.py
import os
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import Error

# Carregar variáveis do .env antes de qualquer configuração
load_dotenv()


# Caminho para uploads de imagens
UPLOAD_FOLDER = os.path.join(os.getcwd(), 'static', 'uploads')
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}

# Configurações do banco de dados MySQL - usar variáveis de ambiente
DB_CONFIG = {
    "user": os.environ.get('DB_USER', 'root'),
    "password": os.environ.get('DB_PASSWORD', '98050771'),
    "host": os.environ.get('DB_HOST', 'localhost'),
    "database": os.environ.get('DB_NAME', 'criavibe'),
    "charset": 'utf8mb4',
    "autocommit": True
}

# Configurações de segurança
SECRET_KEY = os.environ.get('SECRET_KEY', 'algum_segredo_muito_forte')
MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16MB max file size

def get_db_connection():
    """Estabelece conexão com o banco de dados com tratamento de erro"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        print(f"Erro ao conectar no MySQL: {e}")
        return None

def validate_config():
    """Valida as configurações necessárias"""
    required_env_vars = ['DB_PASSWORD', 'SECRET_KEY']
    missing_vars = [var for var in required_env_vars if not os.environ.get(var)]
    
    if missing_vars:
        print(f"Aviso: Variáveis de ambiente não definidas: {missing_vars}")
        print("Usando valores padrão (não recomendado para produção)")
    
    return True
