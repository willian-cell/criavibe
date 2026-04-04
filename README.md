# CriaVibe - Sistema de Galerias de Fotos

O **CriaVibe** é uma aplicação focada em fotógrafos para administração, entrega de fotos, e aprovação de galerias em ambiente web amigável com um dashboard limpo e ágil. 

O sistema backend é desenvolvido inteiramente de forma otimizada usando **PHP** estruturado nativo, servindo uma interface construída puramente em **HTML, CSS e JavaScript Vanilla**.

## Estrutura de Diretórios

- `/assets`: Contém todo o styling (`/css`), imagens auxiliares (`/images`), e lógicas em JavaScript (`/js`). Funções vitais de fetch chamando a API encontram-se em `assets/js/api.js` e `auth.js`.
- `/api`: Núcleo de comunicação que processa e persiste transações.
- `/uploads`: Diretório dinâmico onde todas as fotos originais e comprimidas da aplicação são salvas em tempo real.
- Raiz (`.html`): View e front-facing do sistema (e.g., `painel.html`, `clientes.html`, `entrar.html`, `saiba_mais.html`).

## Funcionalidades e Rotas (Backend API)

Os controladores do sistema são separados por domínio lógico em `/api`, suportando JSON purista. 

**Rotas principais e domínios**:
- **Auth (`api/auth`)**: Sistema de sessão mantido pelo `config.php`, contendo checagem de usuário ativo, logout, e validação de permissões rígidas via token/session.
- **Clientes (`api/clientes`)**: Criação segura gerando link e senha únicos auto-distribuíveis para os clientes sem requerer envio em texto puro nas galerias.
- **Fotos (`api/fotos`)**:
  - Central upload multi-partes escalável em `upload.php`.
  - Processo de download flexível unificado ou empacotado sob demanda e em tempo real em `download_zip.php` validando o `$dl_count`.
  - Lógica de limitação inteligente de seleções (curadoria) controlados pelos endpoints `toggle_selecao.php` (individual) e `client_selecao.php` (em bloco para limpar e selecionar todos sob limites predefinidos).
- **Galerias (`api/galerias`)**:
  - Geração de álbuns ativando restrições de *max_downloads* e *max_selecao*. O endpoint `verify_access.php` faz fallback caso sessões PHP venham a cair ou fechar. 
  - Consultas protegidas para donos limitarem quem está explorando álbuns por senha.
- **Músicas (`api/musicas`)**: Upload de background tracks para playlists executadas automaticamente na capa frontal da galera do cliente.
- **Migrações (`api/db_migrations.php`)**: Utilitário engatilhado manual e seguramente para atualizar o banco e sanar estruturas `Lazy` com DDL sem impactar ou destruir os nós das listagens sob estresse simultâneo.

## Instalação e Configuração

O sistema já pode existir em qualquer provedor otimizado com suporte a PHP 7.4+ nativo.

1. **Baixe ou clone via repositório.**
2. Copie o escopo das credenciais (`env_example.txt` como inspiração) configurando obrigatoriamente dentro de `api/config.php` nos campos de `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
3. Inicie a configuração da pasta `/uploads`. Certifique-se de aplicar `CHMOD 775` (ou similar) em sistemas baseados em Unix nas varas `uploads/` para que o PHP grave as imagens do client.
4. Rode **somente uma vez** o sistema de auto-restauração acessando sua URL direta `/api/db_migrations.php` estando autenticado, para garantir que novas colunas do banco estejam devidamente instanciadas e prontas no schema.

Dúvidas gerais, analise os arquivos de roteamento na ramificação de `api/` para verificação dos cabeçalhos aceitos (CORS já vêm habilitado para `HTTP_ORIGIN` no `config.php`).


testar local: php -S localhost:8000