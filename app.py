from flask import (Flask, render_template, request, jsonify, session,
                   redirect, url_for, flash, send_file)
from flask_cors import CORS
from werkzeug.security import generate_password_hash, check_password_hash
import webbrowser, threading, os, logging, io, zipfile
from werkzeug.utils import secure_filename
from typing import Optional, Dict, Any
from dotenv import load_dotenv
from functools import wraps

load_dotenv()

from config import UPLOAD_FOLDER, ALLOWED_EXTENSIONS, MAX_CONTENT_LENGTH
from db import (
    conectar, inserir_usuario, buscar_usuario_por_email,
    criar_galeria, buscar_galerias_usuario, buscar_galeria_por_id,
    atualizar_galeria, toggle_galeria, set_capa_galeria,
    excluir_galeria_db, salvar_imagem, buscar_imagens_galeria,
    salvar_ordem_imagens, toggle_selecao_imagem, excluir_imagem_db,
    buscar_galerias_publicas,
    salvar_musica, buscar_musicas_galeria, excluir_musica_db, salvar_ordem_musicas
)
from r2_storage import upload_para_r2, deletar_do_r2, r2_configurado

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'algum_segredo_muito_forte')
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = MAX_CONTENT_LENGTH
CORS(app)

# ─────────────────────────────────────────────────────────
# DECORADORES
# ─────────────────────────────────────────────────────────

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario' not in session:
            return redirect(url_for('entrar'))
        return f(*args, **kwargs)
    return decorated

def fotografo_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario' not in session or session['usuario'].get('tipo') not in ['fotografo', 'admin']:
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated

def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario' not in session or session['usuario'].get('tipo') != 'admin':
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated

def galeria_owner_required(f):
    """Garante que o usuário é dono da galeria OU admin."""
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario' not in session:
            return redirect(url_for('entrar'))
        galeria_id = kwargs.get('galeria_id')
        galeria = buscar_galeria_por_id(galeria_id)
        if not galeria:
            return render_template('404.html'), 404
        usuario = session['usuario']
        if galeria['usuario_email'] != usuario['email'] and usuario.get('tipo') != 'admin':
            flash('Acesso não autorizado a esta galeria.', 'error')
            return redirect(url_for('painel_usuario'))
        return f(*args, galeria=galeria, **kwargs)
    return decorated

# ─────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────

def allowed_file(filename: Optional[str]) -> bool:
    if not filename:
        return False
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def validar_dados_registro(data: Dict[str, Any]) -> tuple:
    if not data.get('nome') or len(data['nome'].strip()) < 2:
        return False, "Nome deve ter pelo menos 2 caracteres"
    if not data.get('email') or '@' not in data['email']:
        return False, "E-mail inválido"
    if not data.get('senha') or len(data['senha']) < 6:
        return False, "Senha deve ter pelo menos 6 caracteres"
    if data.get('tipo') and data['tipo'] not in ['admin', 'fotografo', 'comum']:
        return False, "Tipo de usuário inválido"
    return True, ""

def abrir_navegador():
    webbrowser.open_new('http://127.0.0.1:5000/')

# ─────────────────────────────────────────────────────────
# PÁGINAS PÚBLICAS
# ─────────────────────────────────────────────────────────

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/entrar')
def entrar():
    return render_template('entrar.html', usuario=session.get('usuario'))

@app.route('/precos')
def precos():
    return render_template('precos.html')

@app.route('/saiba_mais')
def saiba_mais():
    return render_template('saiba_mais.html')

# ─────────────────────────────────────────────────────────
# AUTENTICAÇÃO
# ─────────────────────────────────────────────────────────

@app.route('/api/registro', methods=['POST'])
def registro():
    try:
        data = request.get_json()
        if not data:
            return jsonify({'status': 'erro', 'mensagem': 'Dados inválidos'}), 400
        valido, mensagem = validar_dados_registro(data)
        if not valido:
            return jsonify({'status': 'erro', 'mensagem': mensagem}), 400
        nome = data['nome'].strip()
        email = data['email'].strip().lower()
        senha = generate_password_hash(data['senha'])
        tipo = data.get('tipo', 'comum')
        sucesso = inserir_usuario(nome, email, senha, tipo)
        if not sucesso:
            return jsonify({'status': 'erro', 'mensagem': 'E-mail já cadastrado ou BD indisponível.'}), 400
        logger.info(f"Usuário registrado: {email} ({tipo})")
        return jsonify({'status': 'ok', 'mensagem': 'Usuário registrado com sucesso!'})
    except Exception as e:
        logger.error(f"Erro no registro: {e}")
        return jsonify({'status': 'erro', 'mensagem': 'Erro interno ao registrar.'}), 500

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
        if usuario is None:
            conn_teste = conectar()
            if not conn_teste:
                return jsonify({'status': 'erro', 'mensagem': 'Serviço temporariamente indisponível.'}), 503
            conn_teste.close()
            return jsonify({'status': 'erro', 'mensagem': 'E-mail ou senha incorretos!'}), 401
        if isinstance(usuario, dict) and check_password_hash(str(usuario.get('senha', '')), senha):
            session['usuario'] = {
                'nome': str(usuario.get('nome', '')),
                'email': str(usuario.get('email', '')),
                'tipo': str(usuario.get('tipo', 'comum'))
            }
            logger.info(f"Login: {email}")
            return jsonify({'status': 'ok', 'mensagem': 'Login realizado!',
                            'usuario': session['usuario'],
                            'redirect': url_for('painel_usuario')})
        return jsonify({'status': 'erro', 'mensagem': 'E-mail ou senha incorretos!'}), 401
    except Exception as e:
        logger.error(f"Erro no login: {e}")
        return jsonify({'status': 'erro', 'mensagem': 'Erro interno do servidor'}), 500

@app.route('/api/logout')
def logout():
    if 'usuario' in session:
        logger.info(f"Logout: {session['usuario'].get('email')}")
    session.pop('usuario', None)
    return jsonify({'mensagem': 'Logout realizado'})

@app.route('/api/usuario_logado')
def usuario_logado():
    if 'usuario' in session:
        return jsonify(session['usuario'])
    return jsonify({'erro': 'não autenticado'}), 401

# ─────────────────────────────────────────────────────────
# PAINEL DO USUÁRIO
# ─────────────────────────────────────────────────────────

@app.route('/painel_usuario')
@login_required
def painel_usuario():
    usuario = session['usuario']
    galerias = buscar_galerias_usuario(usuario['email'])
    return render_template('painel_usuario.html', usuario=usuario, galerias=galerias)

@app.route('/galerias')
@login_required
def galerias():
    return redirect(url_for('painel_usuario'))

@app.route('/configuracoes')
@login_required
def configuracoes():
    return render_template('configuracoes.html', usuario=session['usuario'])

@app.route('/admin/usuarios')
@admin_required
def admin_usuarios():
    conn = conectar()
    usuarios = []
    if conn:
        try:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id, nome, email, tipo FROM usuarios ORDER BY id DESC")
            usuarios = cursor.fetchall()
        finally:
            conn.close()
    return render_template('admin_usuarios.html', usuarios=usuarios)

# ─────────────────────────────────────────────────────────
# CRIAR GALERIA
# ─────────────────────────────────────────────────────────

@app.route('/nova_galeria', methods=['GET', 'POST'])
@fotografo_required
def nova_galeria():
    if request.method == 'POST':
        nome = request.form.get('nome', '').strip()
        descricao = request.form.get('descricao', '').strip()
        privacidade = request.form.get('privacidade', 'privada')
        senha = request.form.get('senha') or None
        senha_hash = generate_password_hash(senha) if senha else None
        email = session['usuario']['email']
        if not nome:
            flash('O nome da galeria é obrigatório.', 'error')
            return render_template('nova_galeria.html')
        sucesso = criar_galeria(email, nome, descricao, privacidade, senha_hash)
        if sucesso:
            flash('Galeria criada com sucesso!', 'success')
            return redirect(url_for('painel_usuario'))
        flash('Erro ao criar galeria. Tente novamente.', 'error')
    return render_template('nova_galeria.html')

# ─────────────────────────────────────────────────────────
# GERENCIAR GALERIA (FOTÓGRAFO)
# ─────────────────────────────────────────────────────────

@app.route('/galeria/<int:galeria_id>/fotos')
@fotografo_required
@galeria_owner_required
def gerenciar_galeria(galeria_id, galeria):
    ordem = request.args.get('ordem', 'ordem')
    imagens = buscar_imagens_galeria(galeria_id, ordem)
    return render_template('gerenciar_galeria.html',
                           usuario=session['usuario'],
                           galeria=galeria,
                           imagens=imagens,
                           ordem_atual=ordem,
                           secao='fotos')

@app.route('/galeria/<int:galeria_id>/entrega')
@fotografo_required
@galeria_owner_required
def galeria_entrega(galeria_id, galeria):
    imagens = buscar_imagens_galeria(galeria_id)
    return render_template('gerenciar_galeria.html',
                           usuario=session['usuario'],
                           galeria=galeria,
                           imagens=imagens,
                           ordem_atual='ordem',
                           secao='entrega')

@app.route('/galeria/<int:galeria_id>/selecao_config')
@fotografo_required
@galeria_owner_required
def galeria_selecao_config(galeria_id, galeria):
    imagens = buscar_imagens_galeria(galeria_id)
    return render_template('gerenciar_galeria.html',
                           usuario=session['usuario'],
                           galeria=galeria,
                           imagens=imagens,
                           ordem_atual='ordem',
                           secao='selecao')

@app.route('/galeria/<int:galeria_id>/configuracoes_galeria')
@fotografo_required
@galeria_owner_required
def galeria_configuracoes(galeria_id, galeria):
    return render_template('gerenciar_galeria.html',
                           usuario=session['usuario'],
                           galeria=galeria,
                           imagens=[],
                           ordem_atual='ordem',
                           secao='config')

@app.route('/galeria/<int:galeria_id>/editar', methods=['GET', 'POST'])
@fotografo_required
@galeria_owner_required
def editar_galeria(galeria_id, galeria):
    if request.method == 'POST':
        nome = request.form.get('nome', '').strip()
        descricao = request.form.get('descricao', '').strip()
        privacidade = request.form.get('privacidade', 'publica')
        senha_nova = request.form.get('senha') or None
        senha_hash = generate_password_hash(senha_nova) if senha_nova else galeria.get('senha')
        atualizar_galeria(galeria_id, nome, descricao, privacidade, senha_hash)
        flash('Galeria atualizada!', 'success')
        return redirect(url_for('gerenciar_galeria', galeria_id=galeria_id))
    return render_template('editar_galeria.html', galeria=galeria)

@app.route('/galeria/<int:galeria_id>/excluir', methods=['POST'])
@fotografo_required
@galeria_owner_required
def excluir_galeria(galeria_id, galeria):
    imagens = excluir_galeria_db(galeria_id)
    for img in imagens:
        if isinstance(img, dict):
            r2_key = img.get('r2_key')
            caminho = img.get('caminho_arquivo', '')
            if r2_key:
                deletar_do_r2(r2_key)
            elif caminho and not caminho.startswith('http'):
                try:
                    os.remove(os.path.join(os.getcwd(), caminho.lstrip('/')))
                except Exception:
                    pass
    flash('Galeria excluída com sucesso!', 'success')
    return redirect(url_for('painel_usuario'))

# ─────────────────────────────────────────────────────────
# UPLOAD DE IMAGENS
# ─────────────────────────────────────────────────────────

@app.route('/galeria/<int:galeria_id>/upload', methods=['GET', 'POST'])
@fotografo_required
@galeria_owner_required
def upload_multiplas_imagens(galeria_id, galeria):
    """Upload em lote via form normal."""
    if request.method == 'POST':
        imagens_files = request.files.getlist('imagens')
        erros = []
        enviadas = 0
        for imagem in imagens_files:
            if not imagem or not allowed_file(imagem.filename):
                continue
            filename = secure_filename(imagem.filename or '')
            if not filename:
                continue
            try:
                tamanho = imagem.seek(0, 2); imagem.seek(0)
                if r2_configurado():
                    res = upload_para_r2(imagem, galeria_id)
                    salvar_imagem(galeria_id, filename, '', '',
                                  res['url'], r2_key=res['key'], tamanho_bytes=tamanho)
                else:
                    os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
                    caminho = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                    imagem.save(caminho)
                    salvar_imagem(galeria_id, filename, '', '',
                                  f'/static/uploads/{filename}', tamanho_bytes=tamanho)
                enviadas += 1
            except Exception as e:
                logger.error(f"Erro upload {filename}: {e}")
                erros.append(filename)
        if erros:
            flash(f"Erro ao enviar: {', '.join(erros)}", 'error')
        if enviadas:
            flash(f'{enviadas} foto(s) enviada(s) com sucesso!', 'success')
        return redirect(url_for('gerenciar_galeria', galeria_id=galeria_id))
    return render_template('gerenciar_galeria.html',
                           usuario=session['usuario'], galeria=galeria,
                           imagens=buscar_imagens_galeria(galeria_id),
                           ordem_atual='ordem', secao='fotos')

@app.route('/api/galeria/<int:galeria_id>/upload_async', methods=['POST'])
@fotografo_required
def upload_async(galeria_id):
    """Upload assíncrono: UMA foto por request. Usado pela barra de progresso JS."""
    if 'usuario' not in session or session['usuario']['tipo'] not in ['fotografo', 'admin']:
        return jsonify({'status': 'erro', 'mensagem': 'Sem permissão'}), 403
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or galeria['usuario_email'] != session['usuario']['email']:
        if session['usuario']['tipo'] != 'admin':
            return jsonify({'status': 'erro', 'mensagem': 'Galeria não encontrada'}), 404
    if 'imagem' not in request.files:
        return jsonify({'status': 'erro', 'mensagem': 'Nenhum arquivo'}), 400
    imagem = request.files['imagem']
    if not imagem or not allowed_file(imagem.filename):
        return jsonify({'status': 'erro', 'mensagem': 'Arquivo inválido'}), 400
    filename = secure_filename(imagem.filename or '')
    if not filename:
        return jsonify({'status': 'erro', 'mensagem': 'Nome inválido'}), 400
    try:
        tamanho = imagem.seek(0, 2); imagem.seek(0)
        if r2_configurado():
            res = upload_para_r2(imagem, galeria_id)
            salvar_imagem(galeria_id, filename, '', '',
                          res['url'], r2_key=res['key'], tamanho_bytes=tamanho)
            url = res['url']
        else:
            os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
            caminho = os.path.join(app.config['UPLOAD_FOLDER'], filename)
            imagem.save(caminho)
            url = f'/static/uploads/{filename}'
            salvar_imagem(galeria_id, filename, '', '', url, tamanho_bytes=tamanho)
        return jsonify({'status': 'ok', 'url': url, 'filename': filename})
    except Exception as e:
        logger.error(f"Erro upload async: {e}")
        return jsonify({'status': 'erro', 'mensagem': str(e)}), 500

# ─────────────────────────────────────────────────────────
# APIS DE GERENCIAMENTO
# ─────────────────────────────────────────────────────────

@app.route('/api/galeria/<int:galeria_id>/toggle', methods=['POST'])
@login_required
def api_toggle_galeria(galeria_id):
    """Toggle de entrega_em_alta, selecao_fotos, download_individual, download_all."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria:
        return jsonify({'erro': 'Galeria não encontrada'}), 404
    if galeria['usuario_email'] != session['usuario']['email'] and session['usuario']['tipo'] != 'admin':
        return jsonify({'erro': 'Sem permissão'}), 403
    campo = request.json.get('campo') if request.json else None
    novo_valor = toggle_galeria(galeria_id, campo)
    if novo_valor is None:
        return jsonify({'erro': 'Campo inválido ou erro no banco'}), 400
    return jsonify({'status': 'ok', 'campo': campo, 'valor': novo_valor})

@app.route('/api/galeria/<int:galeria_id>/capa', methods=['POST'])
@fotografo_required
def api_set_capa(galeria_id):
    """Define foto de capa enviando URL (body JSON: {url: '...'})."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or (galeria['usuario_email'] != session['usuario']['email']
                       and session['usuario']['tipo'] != 'admin'):
        return jsonify({'erro': 'Sem permissão'}), 403
    data = request.json or {}
    url = data.get('url', '').strip()
    if not url:
        return jsonify({'erro': 'URL obrigatória'}), 400
    set_capa_galeria(galeria_id, url)
    return jsonify({'status': 'ok', 'capa_url': url})

@app.route('/api/galeria/<int:galeria_id>/ordem', methods=['POST'])
@fotografo_required
def api_salvar_ordem(galeria_id):
    """Salva nova ordem: body JSON {ids: [1,3,2,...]}."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or (galeria['usuario_email'] != session['usuario']['email']
                       and session['usuario']['tipo'] != 'admin'):
        return jsonify({'erro': 'Sem permissão'}), 403
    data = request.json or {}
    ids = data.get('ids', [])
    if not ids or not isinstance(ids, list):
        return jsonify({'erro': 'Lista de IDs obrigatória'}), 400
    salvar_ordem_imagens(ids)
    return jsonify({'status': 'ok'})

@app.route('/api/imagem/<int:imagem_id>/selecionar', methods=['POST'])
@login_required
def api_selecionar_imagem(imagem_id):
    """Cliente marca/desmarca imagem como favorita."""
    novo = toggle_selecao_imagem(imagem_id)
    if novo is None:
        return jsonify({'erro': 'Imagem não encontrada'}), 404
    return jsonify({'status': 'ok', 'selecionada': novo})

@app.route('/api/galeria/<int:galeria_id>/zip')
@login_required
def api_download_zip(galeria_id):
    """Gera ZIP com todas as imagens locais (apenas fallback local)."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria:
        return jsonify({'erro': 'Galeria não encontrada'}), 404
    eh_dono = galeria['usuario_email'] == session['usuario']['email']
    eh_admin = session['usuario']['tipo'] == 'admin'
    download_all = galeria.get('download_all', False)
    if not (eh_dono or eh_admin or download_all):
        return jsonify({'erro': 'Download não permitido'}), 403
    imagens = buscar_imagens_galeria(galeria_id)
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, 'w', zipfile.ZIP_DEFLATED) as zf:
        for img in imagens:
            caminho = img.get('caminho_arquivo', '')
            if caminho and not caminho.startswith('http'):
                full_path = os.path.join(os.getcwd(), caminho.lstrip('/'))
                if os.path.exists(full_path):
                    zf.write(full_path, img.get('nome_arquivo', os.path.basename(full_path)))
    buf.seek(0)
    nome_zip = f"galeria_{galeria_id}_{galeria.get('nome','fotos').replace(' ','_')}.zip"
    return send_file(buf, as_attachment=True, download_name=nome_zip, mimetype='application/zip')

# ─────────────────────────────────────────────────────────
# VISTA DO CLIENTE (GALERIA PÚBLICA)
# ─────────────────────────────────────────────────────────

@app.route('/galeria/<int:galeria_id>')
@login_required
def ver_galeria(galeria_id):
    usuario = session['usuario']
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria:
        return render_template('404.html'), 404
    eh_dono = galeria['usuario_email'] == usuario['email']
    eh_admin = usuario['tipo'] == 'admin'
    eh_publica = galeria['privacidade'] == 'publica'
    if not (eh_dono or eh_admin or eh_publica):
        flash('Você não tem acesso a esta galeria.', 'error')
        return redirect(url_for('painel_usuario'))
    if eh_dono or eh_admin:
        return redirect(url_for('gerenciar_galeria', galeria_id=galeria_id))
    imagens = buscar_imagens_galeria(galeria_id)
    return render_template('galeria.html', usuario=usuario, galeria=galeria, imagens=imagens)

# ─────────────────────────────────────────────────────────
# EXCLUSÃO DE IMAGEM
# ─────────────────────────────────────────────────────────

@app.route('/imagem/<int:imagem_id>/excluir', methods=['POST'])
@fotografo_required
def excluir_imagem(imagem_id):
    img = excluir_imagem_db(imagem_id)
    galeria_id = None
    if img:
        galeria_id = img.get('galeria_id')
        r2_key = img.get('r2_key')
        caminho = img.get('caminho_arquivo', '')
        if r2_key:
            deletar_do_r2(r2_key)
        elif caminho and not caminho.startswith('http'):
            try:
                os.remove(os.path.join(os.getcwd(), caminho.lstrip('/')))
            except Exception:
                pass
        flash('Imagem excluída!', 'success')
    else:
        flash('Imagem não encontrada.', 'error')
    if galeria_id:
        return redirect(url_for('gerenciar_galeria', galeria_id=galeria_id))
    return redirect(url_for('painel_usuario'))

# ─────────────────────────────────────────────────────────
# MÚSICAS
# ─────────────────────────────────────────────────────────

ALLOWED_MUSIC_EXTENSIONS = {'mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma'}

def allowed_music(filename):
    return filename and '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_MUSIC_EXTENSIONS

@app.route('/api/galeria/<int:galeria_id>/musicas')
@fotografo_required
def api_musicas_galeria(galeria_id):
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or (galeria['usuario_email'] != session['usuario']['email']
                       and session['usuario']['tipo'] != 'admin'):
        return jsonify({'erro': 'Sem permissão'}), 403
    return jsonify(buscar_musicas_galeria(galeria_id))

@app.route('/api/galeria/<int:galeria_id>/musica/upload', methods=['POST'])
@fotografo_required
def api_upload_musica(galeria_id):
    """Upload de arquivo de música (mp3, wav, ogg, aac, flac, m4a)."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or (galeria['usuario_email'] != session['usuario']['email']
                       and session['usuario']['tipo'] != 'admin'):
        return jsonify({'erro': 'Sem permissão'}), 403
    arquivo = request.files.get('musica')
    if not arquivo or not allowed_music(arquivo.filename):
        return jsonify({'erro': 'Arquivo inválido. Formatos: mp3, wav, ogg, aac, flac, m4a'}), 400
    nome = secure_filename(arquivo.filename)
    nome_exibicao = request.form.get('nome_exibicao', nome.rsplit('.', 1)[0])
    caminho_url = None
    r2_key = None
    if r2_configurado():
        import uuid
        r2_key = f"musicas/{galeria_id}/{uuid.uuid4().hex}_{nome}"
        caminho_url = upload_para_r2(arquivo, r2_key, arquivo.content_type or 'audio/mpeg')
    else:
        pasta = os.path.join(UPLOAD_FOLDER, 'musicas', str(galeria_id))
        os.makedirs(pasta, exist_ok=True)
        caminho_local = os.path.join(pasta, nome)
        arquivo.save(caminho_local)
        caminho_url = f"/static/uploads/musicas/{galeria_id}/{nome}"
    if not caminho_url:
        return jsonify({'erro': 'Falha ao salvar arquivo'}), 500
    sucesso = salvar_musica(galeria_id, nome, nome_exibicao, caminho_arquivo=caminho_url, r2_key=r2_key)
    if not sucesso:
        return jsonify({'erro': 'Erro ao salvar no banco'}), 500
    return jsonify({'status': 'ok', 'url': caminho_url, 'nome': nome_exibicao})

@app.route('/api/galeria/<int:galeria_id>/musica/youtube', methods=['POST'])
@fotografo_required
def api_adicionar_youtube(galeria_id):
    """Adiciona música via link do YouTube."""
    galeria = buscar_galeria_por_id(galeria_id)
    if not galeria or (galeria['usuario_email'] != session['usuario']['email']
                       and session['usuario']['tipo'] != 'admin'):
        return jsonify({'erro': 'Sem permissão'}), 403
    data = request.get_json() or {}
    youtube_url = (data.get('url') or '').strip()
    nome_exibicao = (data.get('nome') or youtube_url).strip()
    # Validação básica de URL do YouTube
    if 'youtube.com' not in youtube_url and 'youtu.be' not in youtube_url:
        return jsonify({'erro': 'URL deve ser do YouTube (youtube.com ou youtu.be)'}), 400
    # Extrair ID do vídeo para embed
    import re
    yt_id = None
    m = re.search(r'(?:v=|youtu\.be/)([\w-]{11})', youtube_url)
    if m:
        yt_id = m.group(1)
    if not yt_id:
        return jsonify({'erro': 'Não foi possível extrair o ID do vídeo'}), 400
    sucesso = salvar_musica(galeria_id, yt_id, nome_exibicao, youtube_url=youtube_url)
    if not sucesso:
        return jsonify({'erro': 'Erro ao salvar no banco'}), 500
    return jsonify({'status': 'ok', 'yt_id': yt_id, 'nome': nome_exibicao})

@app.route('/api/musica/<int:musica_id>/excluir', methods=['POST'])
@fotografo_required
def api_excluir_musica(musica_id):
    musica = excluir_musica_db(musica_id)
    if musica:
        r2_key = musica.get('r2_key')
        caminho = musica.get('caminho_arquivo', '')
        if r2_key:
            deletar_do_r2(r2_key)
        elif caminho and not caminho.startswith('http'):
            try:
                os.remove(os.path.join(os.getcwd(), caminho.lstrip('/')))
            except Exception:
                pass
        return jsonify({'status': 'ok'})
    return jsonify({'erro': 'Não encontrada'}), 404

@app.route('/api/galeria/<int:galeria_id>/musica/ordem', methods=['POST'])
@fotografo_required
def api_ordem_musicas(galeria_id):
    data = request.get_json() or {}
    ids = data.get('ids', [])
    salvar_ordem_musicas(ids)
    return jsonify({'status': 'ok'})

# ─────────────────────────────────────────────────────────
# APIS PÚBLICAS
# ─────────────────────────────────────────────────────────

@app.route('/api/galerias_publicas')
def galerias_publicas():
    return jsonify(buscar_galerias_publicas(8))

@app.route('/api/galerias_usuario')
@login_required
def api_galerias_usuario():
    email = request.args.get('email', session['usuario']['email'])
    if email != session['usuario']['email'] and session['usuario']['tipo'] != 'admin':
        return jsonify({'erro': 'Acesso não autorizado'}), 403
    return jsonify(buscar_galerias_usuario(email))

@app.route('/api/imagens_por_galeria')
def imagens_por_galeria():
    galeria_id = request.args.get('galeria_id')
    if not galeria_id:
        return jsonify({'erro': 'ID obrigatório'}), 400
    return jsonify(buscar_imagens_galeria(int(galeria_id)))

if __name__ == '__main__':
    threading.Timer(1, abrir_navegador).start()
    debug_mode = os.environ.get('FLASK_DEBUG', 'false').lower() == 'true'
    app.run(debug=debug_mode)
