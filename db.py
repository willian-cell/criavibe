from typing import Optional, Dict, Any
import mysql.connector
from mysql.connector import Error
from config import DB_CONFIG
import os
import logging

from config import UPLOAD_FOLDER, ALLOWED_EXTENSIONS

logger = logging.getLogger(__name__)

def conectar():
    """Estabelece conexão com o banco de dados"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        logger.error(f"Erro ao conectar no MySQL: {e}")
        return None

def inserir_usuario(nome: str, email: str, senha: str, tipo: str = 'comum') -> bool:
    """Insere um novo usuário no banco de dados"""
    conn = conectar()
    if not conn:
        logger.error("Falha ao conectar com banco de dados")
        return False
    
    try:
        cursor = conn.cursor()
        params: tuple[str, str, str, str] = (nome, email, senha, tipo)
        cursor.execute("""
            INSERT INTO usuarios (nome, email, senha, tipo)
            VALUES (%s, %s, %s, %s)
        """, params)
        conn.commit()
        logger.info(f"Usuário inserido com sucesso: {email} ({tipo})")
        return True
    except Error as e:
        logger.error(f"Erro ao inserir usuário: {e}")
        return False
    finally:
        conn.close()

def buscar_usuario_por_email(email: str) -> Optional[Dict[str, Any]]:
    """Busca usuário por email"""
    conn = conectar()
    if not conn:
        logger.error("Falha ao conectar com banco de dados")
        return None
    
    try:
        cursor = conn.cursor(dictionary=True)
        params: tuple[str] = (email,)
        cursor.execute("SELECT * FROM usuarios WHERE email = %s", params)
        resultado = cursor.fetchone()
        # Garante que o retorno é dict ou None
        if resultado is None or isinstance(resultado, dict):
            return resultado
        return None
    except Error as e:
        logger.error(f"Erro ao buscar usuário: {e}")
        return None
    finally:
        conn.close()

def criar_galeria(usuario_email: str, nome: str, descricao: str, privacidade: str, senha: Optional[str] = None) -> bool:
    """Cria uma nova galeria"""
    conn = conectar()
    if not conn:
        logger.error("Falha ao conectar com banco de dados")
        return False
    
    try:
        cursor = conn.cursor()
        params: tuple[str, str, str, str, Optional[str]] = (usuario_email, nome, descricao, privacidade, senha)
        cursor.execute("""
            INSERT INTO galerias (usuario_email, nome, descricao, privacidade, senha)
            VALUES (%s, %s, %s, %s, %s)
        """, params)
        conn.commit()
        logger.info(f"Galeria criada com sucesso para: {usuario_email}")
        return True
    except Error as e:
        logger.error(f"Erro ao criar galeria: {e}")
        return False
    finally:
        conn.close()

def salvar_imagem(galeria_id: str, nome_arquivo: str, titulo: str, descricao: str,
                  caminho_arquivo: str, r2_key: Optional[str] = None) -> bool:
    """Salva uma nova imagem no banco de dados"""
    conn = conectar()
    if not conn:
        logger.error("Falha ao conectar com banco de dados")
        return False
    
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO imagens (galeria_id, nome_arquivo, titulo, descricao, caminho_arquivo, r2_key)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (galeria_id, nome_arquivo, titulo, descricao, caminho_arquivo, r2_key))
        conn.commit()
        logger.info(f"Imagem salva com sucesso: {nome_arquivo}")
        return True
    except Error as e:
        logger.error(f"Erro ao salvar imagem: {e}")
        return False
    finally:
        conn.close()
