from typing import Optional, Dict, Any, List
import mysql.connector
from mysql.connector import Error
from config import DB_CONFIG
import logging

logger = logging.getLogger(__name__)

def conectar():
    """Estabelece conexão com o banco de dados"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        logger.error(f"Erro ao conectar no MySQL: {e}")
        return None

# ─────────────────────────────────────────────────────────
# USUÁRIOS
# ─────────────────────────────────────────────────────────

def inserir_usuario(nome: str, email: str, senha: str, tipo: str = 'comum') -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO usuarios (nome, email, senha, tipo)
            VALUES (%s, %s, %s, %s)
        """, (nome, email, senha, tipo))
        conn.commit()
        logger.info(f"Usuário inserido: {email} ({tipo})")
        return True
    except Error as e:
        logger.error(f"Erro ao inserir usuário: {e}")
        return False
    finally:
        conn.close()

def buscar_usuario_por_email(email: str) -> Optional[Dict[str, Any]]:
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM usuarios WHERE email = %s", (email,))
        resultado = cursor.fetchone()
        return resultado if isinstance(resultado, dict) or resultado is None else None
    except Error as e:
        logger.error(f"Erro ao buscar usuário: {e}")
        return None
    finally:
        conn.close()

# ─────────────────────────────────────────────────────────
# GALERIAS
# ─────────────────────────────────────────────────────────

def criar_galeria(usuario_email: str, nome: str, descricao: str,
                  privacidade: str, senha: Optional[str] = None) -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO galerias (usuario_email, nome, descricao, privacidade, senha)
            VALUES (%s, %s, %s, %s, %s)
        """, (usuario_email, nome, descricao, privacidade, senha))
        conn.commit()
        logger.info(f"Galeria criada: '{nome}' para {usuario_email}")
        return True
    except Error as e:
        logger.error(f"Erro ao criar galeria: {e}")
        return False
    finally:
        conn.close()

def buscar_galerias_usuario(email: str) -> List[Dict]:
    """Retorna todas as galerias do usuário com contagem de fotos e capa."""
    conn = conectar()
    if not conn:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT
                g.*,
                COUNT(i.id)  AS total_fotos,
                COALESCE(g.capa_url,
                    (SELECT caminho_arquivo FROM imagens
                     WHERE galeria_id = g.id ORDER BY ordem, id LIMIT 1)
                ) AS thumb
            FROM galerias g
            LEFT JOIN imagens i ON i.galeria_id = g.id
            WHERE g.usuario_email = %s
            GROUP BY g.id
            ORDER BY g.id DESC
        """, (email,))
        return cursor.fetchall() or []
    except Error as e:
        logger.error(f"Erro ao buscar galerias: {e}")
        return []
    finally:
        conn.close()

def buscar_galeria_por_id(galeria_id: int) -> Optional[Dict]:
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT g.*,
                   COUNT(i.id) AS total_fotos
            FROM galerias g
            LEFT JOIN imagens i ON i.galeria_id = g.id
            WHERE g.id = %s
            GROUP BY g.id
        """, (galeria_id,))
        return cursor.fetchone()
    except Error as e:
        logger.error(f"Erro ao buscar galeria {galeria_id}: {e}")
        return None
    finally:
        conn.close()

def atualizar_galeria(galeria_id: int, nome: str, descricao: str,
                      privacidade: str, senha: Optional[str]) -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE galerias
            SET nome=%s, descricao=%s, privacidade=%s, senha=%s
            WHERE id=%s
        """, (nome, descricao, privacidade, senha, galeria_id))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao atualizar galeria: {e}")
        return False
    finally:
        conn.close()

def toggle_galeria(galeria_id: int, campo: str) -> Optional[bool]:
    """Alterna um campo booleano da galeria. Retorna o novo valor ou None em erro."""
    campos_permitidos = {
        'entrega_em_alta', 'selecao_fotos', 'selecao_ativa',
        'download_individual', 'download_all',
        'musicas_ativas', 'vender_fotos',
        'selecao_sem_senha', 'selecao_publica'
    }
    if campo not in campos_permitidos:
        return None
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(f"SELECT `{campo}` FROM galerias WHERE id = %s", (galeria_id,))
        row = cursor.fetchone()
        if not row:
            return None
        novo_valor = 0 if row[campo] else 1
        cursor.execute(f"UPDATE galerias SET `{campo}` = %s WHERE id = %s", (novo_valor, galeria_id))
        conn.commit()
        return bool(novo_valor)
    except Error as e:
        logger.error(f"Erro ao fazer toggle '{campo}': {e}")
        return None
    finally:
        conn.close()

def set_capa_galeria(galeria_id: int, capa_url: str) -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        cursor.execute("UPDATE galerias SET capa_url=%s WHERE id=%s", (capa_url, galeria_id))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao definir capa: {e}")
        return False
    finally:
        conn.close()

def excluir_galeria_db(galeria_id: int) -> List[Dict]:
    """Exclui galeria e retorna lista de imagens (para limpar storage externo)."""
    conn = conectar()
    if not conn:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT caminho_arquivo, r2_key FROM imagens WHERE galeria_id = %s", (galeria_id,))
        imagens = cursor.fetchall() or []
        cursor.execute("DELETE FROM imagens WHERE galeria_id = %s", (galeria_id,))
        cursor.execute("DELETE FROM galerias WHERE id = %s", (galeria_id,))
        conn.commit()
        return imagens
    except Error as e:
        logger.error(f"Erro ao excluir galeria: {e}")
        return []
    finally:
        conn.close()

# ─────────────────────────────────────────────────────────
# IMAGENS
# ─────────────────────────────────────────────────────────

def salvar_imagem(galeria_id: str, nome_arquivo: str, titulo: str, descricao: str,
                  caminho_arquivo: str, r2_key: Optional[str] = None,
                  tamanho_bytes: Optional[int] = None) -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        # Próxima ordem: max(ordem)+1
        cursor.execute("SELECT COALESCE(MAX(ordem),0)+1 FROM imagens WHERE galeria_id=%s", (galeria_id,))
        row = cursor.fetchone()
        proxima_ordem = row[0] if row else 1
        cursor.execute("""
            INSERT INTO imagens (galeria_id, nome_arquivo, titulo, descricao,
                                 caminho_arquivo, r2_key, ordem, tamanho_bytes)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (galeria_id, nome_arquivo, titulo, descricao, caminho_arquivo,
              r2_key, proxima_ordem, tamanho_bytes))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao salvar imagem: {e}")
        return False
    finally:
        conn.close()

def buscar_imagens_galeria(galeria_id: int, ordem: str = 'ordem') -> List[Dict]:
    """Retorna imagens ordenadas. ordem pode ser: ordem|nome_arquivo|enviado_em"""
    campos_ordem = {
        'ordem': 'ordem ASC, id ASC',
        'ordem_desc': 'ordem DESC, id DESC',
        'nome_asc': 'nome_arquivo ASC',
        'nome_desc': 'nome_arquivo DESC',
        'data_asc': 'enviado_em ASC',
        'data_desc': 'enviado_em DESC',
    }
    order_clause = campos_ordem.get(ordem, 'ordem ASC, id ASC')
    conn = conectar()
    if not conn:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(f"SELECT * FROM imagens WHERE galeria_id=%s ORDER BY {order_clause}", (galeria_id,))
        return cursor.fetchall() or []
    except Error as e:
        logger.error(f"Erro ao buscar imagens: {e}")
        return []
    finally:
        conn.close()

def salvar_ordem_imagens(ordem_ids: List[int]) -> bool:
    """Recebe lista de IDs na nova ordem e atualiza campo 'ordem'."""
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        for idx, img_id in enumerate(ordem_ids):
            cursor.execute("UPDATE imagens SET ordem=%s WHERE id=%s", (idx, img_id))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao salvar ordem: {e}")
        return False
    finally:
        conn.close()

def toggle_selecao_imagem(imagem_id: int) -> Optional[bool]:
    """Alterna seleção de uma imagem pelo cliente."""
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT selecionada FROM imagens WHERE id=%s", (imagem_id,))
        row = cursor.fetchone()
        if not row:
            return None
        novo = 0 if row['selecionada'] else 1
        cursor.execute("UPDATE imagens SET selecionada=%s WHERE id=%s", (novo, imagem_id))
        conn.commit()
        return bool(novo)
    except Error as e:
        logger.error(f"Erro ao toggle seleção: {e}")
        return None
    finally:
        conn.close()

def excluir_imagem_db(imagem_id: int) -> Optional[Dict]:
    """Exclui imagem e retorna seus dados (para limpar storage externo)."""
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM imagens WHERE id=%s", (imagem_id,))
        img = cursor.fetchone()
        if img:
            cursor.execute("DELETE FROM imagens WHERE id=%s", (imagem_id,))
            conn.commit()
        return img
    except Error as e:
        logger.error(f"Erro ao excluir imagem: {e}")
        return None
    finally:
        conn.close()

# ─────────────────────────────────────────────────────────
# MÚSICAS
# ─────────────────────────────────────────────────────────

def salvar_musica(galeria_id: int, nome_arquivo: str, nome_exibicao: str,
                  caminho_arquivo: Optional[str] = None,
                  r2_key: Optional[str] = None,
                  youtube_url: Optional[str] = None) -> bool:
    """Salva uma música (upload de arquivo ou link YouTube)."""
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        cursor.execute("SELECT COALESCE(MAX(ordem),0)+1 FROM musicas WHERE galeria_id=%s", (galeria_id,))
        row = cursor.fetchone()
        proxima_ordem = row[0] if row else 1
        cursor.execute("""
            INSERT INTO musicas (galeria_id, nome_arquivo, nome_exibicao,
                                 caminho_arquivo, r2_key, youtube_url, ordem)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """, (galeria_id, nome_arquivo, nome_exibicao or nome_arquivo,
              caminho_arquivo, r2_key, youtube_url, proxima_ordem))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao salvar música: {e}")
        return False
    finally:
        conn.close()

def buscar_musicas_galeria(galeria_id: int) -> List[Dict]:
    conn = conectar()
    if not conn:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            "SELECT * FROM musicas WHERE galeria_id=%s ORDER BY ordem ASC, id ASC",
            (galeria_id,)
        )
        return cursor.fetchall() or []
    except Error as e:
        logger.error(f"Erro ao buscar músicas: {e}")
        return []
    finally:
        conn.close()

def excluir_musica_db(musica_id: int) -> Optional[Dict]:
    """Exclui música e retorna seus dados (para limpar storage externo)."""
    conn = conectar()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM musicas WHERE id=%s", (musica_id,))
        musica = cursor.fetchone()
        if musica:
            cursor.execute("DELETE FROM musicas WHERE id=%s", (musica_id,))
            conn.commit()
        return musica
    except Error as e:
        logger.error(f"Erro ao excluir música: {e}")
        return None
    finally:
        conn.close()

def salvar_ordem_musicas(ordem_ids: List[int]) -> bool:
    conn = conectar()
    if not conn:
        return False
    try:
        cursor = conn.cursor()
        for idx, mid in enumerate(ordem_ids):
            cursor.execute("UPDATE musicas SET ordem=%s WHERE id=%s", (idx, mid))
        conn.commit()
        return True
    except Error as e:
        logger.error(f"Erro ao salvar ordem de músicas: {e}")
        return False
    finally:
        conn.close()

def buscar_galerias_publicas(limite: int = 8) -> List[Dict]:
    conn = conectar()
    if not conn:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT g.*,
                   COUNT(i.id) AS total_fotos,
                   COALESCE(g.capa_url,
                       (SELECT caminho_arquivo FROM imagens
                        WHERE galeria_id = g.id ORDER BY ordem, id LIMIT 1)
                   ) AS thumb
            FROM galerias g
            LEFT JOIN imagens i ON i.galeria_id = g.id
            WHERE g.privacidade = 'publica'
            GROUP BY g.id
            ORDER BY g.id DESC
            LIMIT %s
        """, (limite,))
        return cursor.fetchall() or []
    except Error as e:
        logger.error(f"Erro ao buscar galerias públicas: {e}")
        return []
    finally:
        conn.close()
