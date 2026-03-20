import sys
import os

# Adiciona o diretório da aplicação ao path
app_directory = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, app_directory)

# Carrega variáveis de ambiente do .env
from dotenv import load_dotenv
load_dotenv(os.path.join(app_directory, '.env'))

# Importa a aplicação Flask
from app import app as application
