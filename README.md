# Criavibe Site

Aplicação web Flask para gerenciamento de galerias de imagens.

## Melhorias Implementadas

### 🔒 Segurança
- **Variáveis de ambiente**: Credenciais do banco e chave secreta agora usam variáveis de ambiente
- **Validação de entrada**: Validação robusta de dados de entrada
- **Sanitização de arquivos**: Verificação de extensões e nomes de arquivo seguros
- **Tratamento de erros**: Logging adequado e tratamento de exceções

### 🛠️ Funcionalidades
- **Logging**: Sistema de logs para monitoramento
- **Tipagem**: Adicionadas anotações de tipo para melhor manutenção
- **Validação**: Validação de dados de registro e login
- **Tratamento de erros**: Melhor tratamento de falhas de conexão com banco

### 📁 Estrutura
- **Configuração centralizada**: Arquivo `config.py` melhorado
- **Separação de responsabilidades**: Lógica de banco separada em `db.py`
- **Documentação**: README e requirements.txt

## Instalação

1. **Clone o repositório**
```bash
git clone <url-do-repositorio>
cd criavibe_site
```

2. **Instale as dependências**
```bash
pip install -r requirements.txt
```

3. **Configure as variáveis de ambiente**
```bash
# Copie o arquivo de exemplo
cp env_example.txt .env

# Edite o arquivo .env com suas configurações
```

4. **Configure o banco de dados**


5. **Execute a aplicação**
```bash
python app.py
```

## Variáveis de Ambiente

Copie o arquivo `env_example.txt` para `.env` e configure:

- `DB_USER`: Usuário do banco de dados
- `DB_PASSWORD`: Senha do banco de dados  
- `DB_HOST`: Host do banco de dados
- `DB_NAME`: Nome do banco de dados
- `SECRET_KEY`: Chave secreta para sessões Flask

## Estrutura do Projeto

```
criavibe_site/
├── app.py              # Aplicação principal Flask
├── config.py           # Configurações
├── db.py              # Operações de banco de dados
├── requirements.txt    # Dependências Python
├── env_example.txt    # Exemplo de variáveis de ambiente
├── static/            # Arquivos estáticos
│   └── uploads/       # Imagens enviadas
└── templates/         # Templates HTML
```

## Funcionalidades

- ✅ Registro e login de usuários
- ✅ Upload de imagens
- ✅ Criação de galerias
- ✅ Sistema de sessões
- ✅ Validação de dados
- ✅ Logging de atividades
- ✅ Tratamento de erros

## Segurança

- Senhas são hasheadas com Werkzeug
- Validação de tipos de arquivo
- Sanitização de nomes de arquivo
- Variáveis de ambiente para credenciais
- Logging de tentativas de login

## Próximas Melhorias Sugeridas

1. **Autenticação JWT**: Implementar tokens JWT
2. **Rate Limiting**: Limitar tentativas de login
3. **Compressão de imagens**: Reduzir tamanho de uploads
4. **Cache**: Implementar cache para melhor performance
5. **Testes**: Adicionar testes unitários
6. **Docker**: Containerização da aplicação 