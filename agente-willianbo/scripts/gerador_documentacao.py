import os
import re
import glob
import json
import argparse
from datetime import datetime
from markdown_pdf import MarkdownPdf, Section

# --- DETECÇÃO DA RAIZ DO PROJETO ---

def find_project_root():
    """Busca a raiz do projeto subindo níveis até encontrar marcadores (ex: .git, manifest.json)"""
    current_path = os.path.abspath(os.getcwd())
    # Se o script está sendo rodado de dentro da pasta de scripts, vamos garantir que ele ache a raiz
    markers = ['.git', 'manifest.json', 'package.json', 'agente-willianbo']
    
    while current_path != os.path.dirname(current_path):
        if any(os.path.exists(os.path.join(current_path, m)) for m in markers):
            return current_path
        current_path = os.path.dirname(current_path)
    return os.getcwd() # Fallback para o diretório atual

# --- CONFIGURAÇÕES E IDENTIFICAÇÃO DO SISTEMA ---

def identify_system(root_path):
    system_info = {
        "name": os.path.basename(root_path),
        "description": "Sistema detectado automaticamente.",
        "technologies": [],
        "author": "Willian Batista Oliveira",
        "root": root_path
    }
    
    manifest_path = os.path.join(root_path, "manifest.json")
    if os.path.exists(manifest_path):
        try:
            with open(manifest_path, "r", encoding="utf-8") as f:
                data = json.load(f)
                system_info["name"] = data.get("name", system_info["name"])
                system_info["description"] = data.get("description", system_info["description"])
                system_info["technologies"].append("PWA (Progressive Web App)")
        except:
            pass

    # Identifica tecnologias por arquivos na raiz
    check_files = {
        "package.json": "Node.js/NPM",
        "index.html": "HTML5",
        "js": "JavaScript",
        "css": "Vanilla CSS",
        "requirements.txt": "Python (Pip)",
        "prisma": "Prisma ORM",
        ".git": "Git Version Control"
    }
    
    for marker, tech in check_files.items():
        if os.path.exists(os.path.join(root_path, marker)):
            system_info["technologies"].append(tech)
    
    return system_info

def remove_emojis(text):
    emoji_pattern = re.compile(
        r'[\U00010000-\U0010ffff]|[\u2600-\u27FF]|[\u2300-\u23FF]|[\u25A0-\u25FF]|[\u2190-\u21FF]|[\u2000-\u206F]|[\u2900-\u297F]|[\u2B00-\u2BFF]',
        flags=re.UNICODE
    )
    text = emoji_pattern.sub(r'', text)
    text = re.sub(r'> \[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]', r'', text)
    return text

def get_project_summary(info):
    now = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
    summary = f"""
# Análise Técnica de Sistema

**Nome do Sistema:** {info['name']}
**Raiz do Projeto:** `{info['root']}`
**Data da Análise:** {now}
**Responsável Técnico:** {info['author']}

## Descrição do Projeto
{info['description']}

## Stack Tecnológica Detectada
- {"\n- ".join(info['technologies'])}

## Estrutura de Pastas e Arquivos (Raiz)
"""
    for item in os.listdir(info['root']):
        if not item.startswith('.') and item != "node_modules":
            type_str = "[DIR]" if os.path.isdir(os.path.join(info['root'], item)) else "[FILE]"
            summary += f"- {type_str} {item}\n"
            
    summary += "\n---\n"
    return summary

# --- EXECUÇÃO ---

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Gerador de Documentação Profissional WillianBO")
    parser.add_argument("--path", help="Caminho raiz do projeto (opcional)", default=None)
    args = parser.parse_args()

    # Define a raiz
    root = args.path if args.path else find_project_root()
    os.chdir(root) # Move a execução para a raiz do projeto

    info = identify_system(root)
    print(f"[*] Sistema Identificado: {info['name']}")
    print(f"[*] Raiz do Projeto: {root}")

    # Busca arquivos .md
    todos_mds = glob.glob("**/*.md", recursive=True)
    mds_filtrados = [f for f in todos_mds if "node_modules" not in f and "Manual_Tecnico" not in f]

    # Capa
    conteudo_final = f"""
<div style="text-align: center; margin-top: 150px;">
    <h1 style="font-size: 32px; color: #2c3e50;">Manual Técnico e Documentação de Arquitetura</h1>
    <h2 style="font-size: 24px; color: #34495e;">Sistema {info['name']}</h2>
    <br><br><br><br>
    <h3 style="font-size: 18px; color: #7f8c8d;">Autor e Responsável Técnico</h3>
    <p style="font-size: 22px; font-weight: bold; color: #2c3e50;">{info['author']}</p>
    <p style="font-size: 14px; color: #555;">Desenvolvedor Sênior | Engenheiro de Sistemas | Auditor Q&A<br>Designer de Arquitetura | Engenheiro de Prompt</p>
    <br><br><br><br><br><br><br><br>
    <p style="font-size: 14px; color: #95a5a6;">Compilado em: {datetime.now().strftime("%d/%m/%Y %H:%M")}</p>
</div>
<div style="page-break-after: always;"></div>
"""

    conteudo_final += get_project_summary(info)
    conteudo_final += '<div style="page-break-after: always;"></div>'

    for arquivo in mds_filtrados:
        print(f"[+] Lendo: {arquivo}")
        try:
            with open(arquivo, 'r', encoding='utf-8') as f:
                texto = f.read()
        except:
            continue
            
        texto_limpo = remove_emojis(texto)
        titulo = arquivo.replace('.md', '').replace('\\', ' / ').replace('_', ' ').title()
        if not texto_limpo.strip().startswith('# '):
            conteudo_final += f"\n# {titulo}\n\n"
        conteudo_final += texto_limpo
        conteudo_final += "\n\n---\n"

    pdf = MarkdownPdf(toc_level=2)
    pdf.meta["title"] = f"Manual Técnico - {info['name']}"
    pdf.meta["author"] = info['author']

    partes = conteudo_final.split('<div style="page-break-after: always;"></div>')
    for parte in partes:
        if parte.strip():
            pdf.add_section(Section(parte.strip()))

    filename = f"Manual_Tecnico_{info['name'].replace(' ', '_')}.pdf"
    pdf.save(filename)
    print(f"\n[OK] Documentação gerada: {filename}")
