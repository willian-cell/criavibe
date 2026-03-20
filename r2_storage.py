# r2_storage.py
# Módulo de integração com Cloudflare R2 (compatível com AWS S3)

import boto3
import os
import uuid
import logging
from botocore.config import Config
from botocore.exceptions import ClientError
from werkzeug.datastructures import FileStorage

logger = logging.getLogger(__name__)

# Configurações do Cloudflare R2 (carregadas do .env)
R2_ACCOUNT_ID    = os.environ.get('R2_ACCOUNT_ID', '')
R2_ACCESS_KEY_ID = os.environ.get('R2_ACCESS_KEY_ID', '')
R2_SECRET_KEY    = os.environ.get('R2_SECRET_KEY', '')
R2_BUCKET_NAME   = os.environ.get('R2_BUCKET_NAME', 'criavibe-galeria')
R2_PUBLIC_URL    = os.environ.get('R2_PUBLIC_URL', '')  # https://pub-XXXX.r2.dev

def get_r2_client():
    """Retorna um cliente S3 apontando para o Cloudflare R2"""
    endpoint_url = f"https://{R2_ACCOUNT_ID}.r2.cloudflarestorage.com"
    return boto3.client(
        's3',
        endpoint_url=endpoint_url,
        aws_access_key_id=R2_ACCESS_KEY_ID,
        aws_secret_access_key=R2_SECRET_KEY,
        config=Config(signature_version='s3v4'),
        region_name='auto'
    )

def upload_para_r2(arquivo: FileStorage, galeria_id: int) -> dict:
    """
    Faz upload de uma imagem para o R2.
    
    Retorna dict com:
      - key: chave no R2 (galerias/{galeria_id}/{uuid}.ext)
      - url: URL pública para acesso
    """
    if not R2_ACCESS_KEY_ID or not R2_SECRET_KEY:
        raise ValueError("Credenciais R2 não configuradas. Verifique o arquivo .env")
    
    # Gerar nome único para evitar colisões
    ext = os.path.splitext(arquivo.filename or 'foto.jpg')[1].lower()
    if not ext:
        ext = '.jpg'
    
    unique_name = f"{uuid.uuid4().hex}{ext}"
    key = f"galerias/{galeria_id}/{unique_name}"
    
    client = get_r2_client()
    
    # Determinar Content-Type
    content_type_map = {
        '.jpg': 'image/jpeg',
        '.jpeg': 'image/jpeg',
        '.png': 'image/png',
        '.gif': 'image/gif',
        '.webp': 'image/webp',
    }
    content_type = content_type_map.get(ext, 'image/jpeg')
    
    try:
        client.upload_fileobj(
            arquivo,
            R2_BUCKET_NAME,
            key,
            ExtraArgs={'ContentType': content_type}
        )
        
        # URL pública
        public_url = f"{R2_PUBLIC_URL.rstrip('/')}/{key}"
        logger.info(f"Upload R2 realizado: {key}")
        
        return {'key': key, 'url': public_url}
    
    except ClientError as e:
        logger.error(f"Erro no upload R2: {e}")
        raise

def deletar_do_r2(key: str) -> bool:
    """Remove um objeto do R2 pelo seu key"""
    if not key:
        return False
    try:
        client = get_r2_client()
        client.delete_object(Bucket=R2_BUCKET_NAME, Key=key)
        logger.info(f"Objeto R2 deletado: {key}")
        return True
    except ClientError as e:
        logger.error(f"Erro ao deletar do R2: {e}")
        return False

def listar_fotos_galeria(galeria_id: int) -> list:
    """Lista todos os objetos de uma galeria no R2"""
    prefix = f"galerias/{galeria_id}/"
    try:
        client = get_r2_client()
        response = client.list_objects_v2(Bucket=R2_BUCKET_NAME, Prefix=prefix)
        objects = response.get('Contents', [])
        
        return [
            {
                'key': obj['Key'],
                'url': f"{R2_PUBLIC_URL.rstrip('/')}/{obj['Key']}",
                'tamanho': obj['Size'],
                'modificado': obj['LastModified'].isoformat()
            }
            for obj in objects
        ]
    except ClientError as e:
        logger.error(f"Erro ao listar objetos R2: {e}")
        return []

def r2_configurado() -> bool:
    """Verifica se as credenciais R2 estão configuradas"""
    return bool(R2_ACCOUNT_ID and R2_ACCESS_KEY_ID and R2_SECRET_KEY and R2_PUBLIC_URL)
