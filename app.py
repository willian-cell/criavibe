from flask import Flask, render_template, request, jsonify, session, redirect, url_for, flash, get_flashed_messages
from flask_cors import CORS
from werkzeug.security import generate_password_hash, check_password_hash
import webbrowser
import threading
import os
import logging
from werkzeug.utils import secure_filename
from typing import Optional, Dict, Any, List, cast
from dotenv import load_dotenv

# Carregar variáveis de ambiente do .env
load_dotenv()

# Configurações do projeto
from config import UPLOAD_FOLDER, ALLOWED_EXTENSIONS
from db import conectar, inserir_usuario, buscar_usuario_por_email, criar_galeria, salvar_imagem
from r2_storage import upload_para_r2, deletar_do_r2, r2_configurado
from functools import wraps

# Configuração de logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Inicialização do app
app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'algum_segredo_muito_forte')
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER

# CORS
CORS(app)

@app.route('/')
def index():
    return render_template('index.html')

def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'usuario' not in session:
            return redirect(url_for('entrar'))
        return f(*args, **kwargs)
    return decorated_function

@app.route('/entrar')
def entrar():
    return render_template('entrar.html', usuario=session.get('usuario'))

@app.route('/precos')
def precos():
    return render_template('precos.html')

@app.route('/saiba_mais')
def saiba_mais():
    return render_template('saiba_mais.html')

@app.route('/painel_usuario')
@login_required
def painel_usuario():
    usuario = session['usuario']
    conn = conectar()
    galerias = []
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM galerias WHERE usuario_email = %s", (usuario['email'],))
        galerias_db = cursor.fetchall()
        galerias = []
        for g in galerias_db:
            galeria = None
            if isinstance(g, dict) and 'id' in g:
                galeria = g
            elif hasattr(cursor, 'description') and cursor.description and hasattr(g, '__iter__'):
                colnames = [desc[0] for desc in cursor.description]
                galeria = {col: val for col, val in zip(colnames, g)}
                if 'id' not in galeria:
                    continue
            if not galeria or 'id' not in galeria:
                continue
            cursor.execute("SELECT * FROM imagens WHERE galeria_id = %s", (str(galeria['id']),))
            galeria['imagens_lista'] = cursor.fetchall()
            galerias.append(galeria)
        conn.close()
    return render_template('painel_usuario.html', usuario=usuario, galerias=galerias)

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'usuario' not in session or session['usuario'].get('tipo') != 'admin':
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated_function

def fotografo_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'usuario' not in session or session['usuario'].get('tipo') not in ['fotografo', 'admin']:
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated_function

def validar_dados_registro(data: Dict[str, Any]) -> tuple[bool, str]:
    """Valida os dados de registro do usuário"""
    if not data.get('nome') or len(data['nome'].strip()) < 2:
        return False, "Nome deve ter pelo menos 2 caracteres"
    
    if not data.get('email') or '@' not in data['email']:
        return False, "E-mail inválido"
    
    if not data.get('senha') or len(data['senha']) < 6:
        return False, "Senha deve ter pelo menos 6 caracteres"
    
    if data.get('tipo') and data['tipo'] not in ['admin', 'fotografo', 'comum']:
        return False, "Tipo de usuário inválido"
    
    return True, ""

@app.route('/api/registro', methods=['POST'])
def registro():
    try:
        data = request.get_json()
        if not data:
            return jsonify({'status': 'erro', 'mensagem': 'Dados inválidos'}), 400
        
        # Validação dos dados
        valido, mensagem = validar_dados_registro(data)
        if not valido:
            return jsonify({'status': 'erro', 'mensagem': mensagem}), 400
        
        nome = data['nome'].strip()
        email = data['email'].strip().lower()
        senha = generate_password_hash(data['senha'])
        tipo = data.get('tipo', 'comum')

        inserir_usuario(nome, email, senha, tipo)
        logger.info(f"Usuário registrado com sucesso: {email} ({tipo})")
        return jsonify({'status': 'ok', 'mensagem': 'Usuário registrado com sucesso!'})
    except Exception as e:
        logger.error(f"Erro no registro: {str(e)}")
        return jsonify({'status': 'erro', 'mensagem': 'E-mail já cadastrado!'}), 400

@app.route('/api/login', methods=['POST'])
def login():
    try:
        data = request.get_json()
        if not data:
            return jsonify({'status': 'erro', 'mensagem': 'Dados inválidos'}), 400
        
        email = data.get('email', '').strip().lower()
        senha = data.get('senha', '')
        
        if not email or not senha:
            return jsonify({'status': 'erro', 'mensagem': 'E-mail e senha são obrigatórios'}), 400
        
        usuario = buscar_usuario_por_email(email)
        
        if usuario and isinstance(usuario, dict) and check_password_hash(str(usuario.get('senha', '')), senha):
            session['usuario'] = {
                'nome': str(usuario.get('nome', '')), 
                'email': str(usuario.get('email', '')),
                'tipo': str(usuario.get('tipo', 'comum'))
            }
            logger.info(f"Login realizado com sucesso: {email} ({session['usuario']['tipo']})")
            # Redirecionar para painel_usuario
            return jsonify({
                'status': 'ok', 
                'mensagem': 'Login realizado com sucesso!', 
                'usuario': session['usuario'],
                'redirect': url_for('painel_usuario')
            })
        
        logger.warning(f"Tentativa de login falhou para: {email}")
        return jsonify({'status': 'erro', 'mensagem': 'E-mail ou senha incorretos!'}), 401
    except Exception as e:
        logger.error(f"Erro no login: {str(e)}")
        return jsonify({'status': 'erro', 'mensagem': 'Erro interno do servidor'}), 500

@app.route('/api/usuario_logado')
def usuario_logado():
    if 'usuario' in session:
        return jsonify(session['usuario'])
    return jsonify({'erro': 'não autenticado'}), 401

@app.route('/api/logout')
def logout():
    if 'usuario' in session:
        logger.info(f"Logout realizado: {session['usuario'].get('email', 'desconhecido')}")
    session.pop('usuario', None)
    return jsonify({'mensagem': 'Logout realizado'})

def abrir_navegador():
    webbrowser.open_new('http://127.0.0.1:5000/')

@app.route('/api/galerias_usuario')
def galerias_usuario():
    try:
        email = request.args.get('email')
        if not email:
            return jsonify({'erro': 'E-mail é obrigatório'}), 400
        
        conn = conectar()
        if not conn:
            return jsonify({'erro': 'Erro de conexão com banco de dados'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM galerias WHERE usuario_email = %s", (email,))
        galerias = cursor.fetchall()
        conn.close()
        
        return jsonify(galerias)
    except Exception as e:
        logger.error(f"Erro ao buscar galerias: {str(e)}")
        return jsonify({'erro': 'Erro interno do servidor'}), 500

@app.route('/api/imagens_por_galeria')
def imagens_por_galeria():
    try:
        galeria_id = request.args.get('galeria_id')
        if not galeria_id:
            return jsonify({'erro': 'ID da galeria é obrigatório'}), 400
        
        conn = conectar()
        if not conn:
            return jsonify({'erro': 'Erro de conexão com banco de dados'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM imagens WHERE galeria_id = %s", (galeria_id,))
        imagens = cursor.fetchall()
        conn.close()
        
        return jsonify(imagens)
    except Exception as e:
        logger.error(f"Erro ao buscar imagens: {str(e)}")
        return jsonify({'erro': 'Erro interno do servidor'}), 500

def allowed_file(filename: Optional[str]) -> bool:
    """Verifica se a extensão do arquivo é permitida"""
    if not filename:
        return False
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@app.route('/api/upload_imagem', methods=['POST'])
def upload_imagem():
    if 'usuario' not in session or session['usuario']['tipo'] == 'comum':
        return jsonify({'status': 'erro', 'mensagem': 'Usuário comum não tem permissão.'}), 403
    try:
        galeria_id = request.form.get('galeria_id')
        titulo = request.form.get('titulo', '').strip()
        descricao = request.form.get('descricao', '').strip()

        if not galeria_id:
            return jsonify({'status': 'erro', 'mensagem': 'ID da galeria é obrigatório'}), 400

        if 'imagem' not in request.files:
            return jsonify({'status': 'erro', 'mensagem': 'Nenhum arquivo enviado'}), 400

        imagem = request.files['imagem']
        if not imagem or imagem.filename == '':
            return jsonify({'status': 'erro', 'mensagem': 'Nome de arquivo inválido'}), 400

        if not allowed_file(imagem.filename):
            return jsonify({'status': 'erro', 'mensagem': 'Formato de imagem inválido'}), 400

        filename = secure_filename(imagem.filename or '')
        if not filename:
            return jsonify({'status': 'erro', 'mensagem': 'Nome de arquivo inválido'}), 400

        if r2_configurado():
            # Upload para Cloudflare R2
            resultado = upload_para_r2(imagem, int(galeria_id))
            caminho_url = resultado['url']
            r2_key = resultado['key']
            salvar_imagem(galeria_id, filename, titulo, descricao, caminho_url, r2_key=r2_key)
            logger.info(f"Imagem enviada para R2: {r2_key}")
        else:
            # Fallback: salvar localmente
            os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
            caminho = os.path.join(app.config['UPLOAD_FOLDER'], filename)
            imagem.save(caminho)
            salvar_imagem(galeria_id, filename, titulo, descricao, f"/static/uploads/{filename}")
            logger.info(f"Imagem salva localmente: {filename}")

        flash('Imagem enviada com sucesso!', 'success')
        return redirect(url_for('ver_galeria', galeria_id=galeria_id))
    except Exception as e:
        logger.error(f"Erro no upload de imagem: {str(e)}")
        return jsonify({'status': 'erro', 'mensagem': f'Erro ao fazer upload: {str(e)}'}), 500

@app.route('/api/galerias_publicas')
def galerias_publicas():
    conn = conectar()
    if not conn:
        return jsonify([])
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT g.*, (SELECT caminho_arquivo FROM imagens WHERE galeria_id = g.id LIMIT 1) as capa FROM galerias g WHERE privacidade = 'publica' ORDER BY id DESC LIMIT 8")
    galerias = cursor.fetchall()
    conn.close()
    return jsonify(galerias)

@app.route('/galeria/<int:galeria_id>')
def ver_galeria(galeria_id):
    if 'usuario' not in session:
        return redirect(url_for('entrar'))
    usuario = session['usuario']
    conn = conectar()
    galeria = None
    imagens = []
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM galerias WHERE id = %s", (galeria_id,))
        galeria = cursor.fetchone()
        if galeria:
            cursor.execute("SELECT * FROM imagens WHERE galeria_id = %s", (galeria_id,))
            imagens = cursor.fetchall()
        conn.close()
    if not galeria:
        return render_template('404.html'), 404
    return render_template('galeria.html', usuario=usuario, galeria=galeria, imagens=imagens)

@app.route('/galeria/<int:galeria_id>/upload', methods=['GET', 'POST'])
@fotografo_required
def upload_multiplas_imagens(galeria_id):
    if request.method == 'POST':
        if 'imagens' not in request.files:
            return jsonify({'status': 'erro', 'mensagem': 'Nenhuma imagem enviada'}), 400
        imagens = request.files.getlist('imagens')
        titulos = request.form.getlist('titulos')
        descricoes = request.form.getlist('descricoes')
        erros = []
        for idx, imagem in enumerate(imagens):
            if imagem and allowed_file(imagem.filename):
                filename = secure_filename(imagem.filename)
                titulo = titulos[idx] if idx < len(titulos) else ''
                descricao = descricoes[idx] if idx < len(descricoes) else ''
                try:
                    if r2_configurado():
                        resultado = upload_para_r2(imagem, galeria_id)
                        salvar_imagem(galeria_id, filename, titulo, descricao,
                                      resultado['url'], r2_key=resultado['key'])
                        logger.info(f"Upload R2: {resultado['key']}")
                    else:
                        os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
                        caminho = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                        imagem.save(caminho)
                        salvar_imagem(galeria_id, filename, titulo, descricao,
                                      f'/static/uploads/{filename}')
                except Exception as e:
                    logger.error(f"Erro no upload de {filename}: {e}")
                    erros.append(filename)
        if erros:
            flash(f'Erro ao enviar: {', '.join(erros)}', 'error')
        else:
            flash('Fotos enviadas com sucesso!', 'success')
        return redirect(url_for('ver_galeria', galeria_id=galeria_id))
    # GET: renderiza o formulário
    return render_template('upload_multiplas.html', galeria_id=galeria_id)

@app.route('/galeria/<int:galeria_id>/editar', methods=['GET', 'POST'])
@fotografo_required
def editar_galeria(galeria_id):
    conn = conectar()
    galeria = None
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM galerias WHERE id = %s", (galeria_id,))
        galeria = cursor.fetchone()
        if request.method == 'POST':
            nome = request.form.get('nome', '').strip()
            descricao = request.form.get('descricao', '').strip()
            privacidade = request.form.get('privacidade', 'publica')
            senha = request.form.get('senha', None)
            cursor.execute("UPDATE galerias SET nome=%s, descricao=%s, privacidade=%s, senha=%s WHERE id=%s",
                           (nome, descricao, privacidade, senha, galeria_id))
            conn.commit()
            conn.close()
            return redirect(url_for('ver_galeria', galeria_id=galeria_id))
        conn.close()
    if not galeria:
        return render_template('404.html'), 404
    return render_template('editar_galeria.html', galeria=galeria)

@app.route('/galeria/<int:galeria_id>/excluir', methods=['POST'])
@fotografo_required
def excluir_galeria(galeria_id):
    conn = conectar()
    if conn:
        cursor = conn.cursor()
        # Excluir imagens físicas e registros
        cursor.execute("SELECT caminho_arquivo FROM imagens WHERE galeria_id = %s", (galeria_id,))
        imagens = cursor.fetchall()
        for img in imagens:
            caminho = None
            if isinstance(img, dict):
                caminho = img.get('caminho_arquivo')
            elif isinstance(img, (list, tuple)) and len(img) > 0 and isinstance(img[0], str):
                caminho = img[0]
            if caminho and isinstance(caminho, str):
                try:
                    os.remove(os.path.join(os.getcwd(), caminho.lstrip('/')))
                except Exception:
                    pass
        cursor.execute("DELETE FROM imagens WHERE galeria_id = %s", (galeria_id,))
        cursor.execute("DELETE FROM galerias WHERE id = %s", (galeria_id,))
        conn.commit()
        conn.close()
        flash('Galeria excluída com sucesso!', 'success')
    else:
        flash('Erro ao excluir galeria.', 'error')
    return redirect(url_for('painel_usuario'))

@app.route('/imagem/<int:imagem_id>/excluir', methods=['POST'])
@fotografo_required
def excluir_imagem(imagem_id):
    conn = conectar()
    galeria_id = None
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT caminho_arquivo, galeria_id, r2_key FROM imagens WHERE id = %s", (imagem_id,))
        img = cursor.fetchone()
        if img:
            galeria_id = img['galeria_id']
            r2_key = img.get('r2_key')
            caminho = img.get('caminho_arquivo')

            if r2_key:
                # Deletar do Cloudflare R2
                deletar_do_r2(r2_key)
            elif caminho and not caminho.startswith('http'):
                # Deletar arquivo local (fallback)
                try:
                    os.remove(os.path.join(os.getcwd(), caminho.lstrip('/')))
                except Exception:
                    pass

            cursor.execute("DELETE FROM imagens WHERE id = %s", (imagem_id,))
            conn.commit()
            flash('Imagem excluída com sucesso!', 'success')
        else:
            flash('Imagem não encontrada.', 'error')
        conn.close()
    else:
        flash('Erro ao excluir imagem.', 'error')
    if galeria_id:
        return redirect(url_for('ver_galeria', galeria_id=galeria_id))
    return redirect(url_for('painel_usuario'))

if __name__ == '__main__':
    threading.Timer(1, abrir_navegador).start()
    app.run(debug=True)
