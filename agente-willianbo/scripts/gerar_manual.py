from datetime import datetime
from pathlib import Path


PASTAS_IGNORADAS = {
    ".git",
    "uploads",
    "node_modules",
    "vendor",
    "__pycache__",
}

ARQUIVOS_IGNORADOS = {
    ".env",
    "CREDENCIAIS.md",
}

EXTENSOES_DOCUMENTADAS = {
    ".php",
    ".html",
    ".css",
    ".js",
    ".md",
    ".txt",
    ".json",
    ".yml",
    ".yaml",
    ".dockerignore",
}


def encontrar_raiz() -> Path:
    caminho = Path(__file__).resolve()
    for candidato in caminho.parents:
        if (candidato / "api").exists() and (candidato / "index.html").exists():
            return candidato
    return caminho.parents[2]


def deve_ignorar(caminho: Path, raiz: Path) -> bool:
    relativo = caminho.relative_to(raiz)
    partes = set(relativo.parts)
    if partes & PASTAS_IGNORADAS:
        return True
    if caminho.name in ARQUIVOS_IGNORADOS:
        return True
    if caminho.suffix.lower() in {".png", ".jpg", ".jpeg", ".gif", ".webp", ".mp4", ".pdf", ".log"}:
        return True
    return False


def listar_arquivos(raiz: Path) -> list[Path]:
    arquivos = []
    for caminho in raiz.rglob("*"):
        if caminho.is_file() and not deve_ignorar(caminho, raiz):
            sufixo = caminho.suffix.lower()
            if sufixo in EXTENSOES_DOCUMENTADAS or caminho.name in {"Dockerfile"}:
                arquivos.append(caminho)
    return sorted(arquivos, key=lambda item: item.relative_to(raiz).as_posix().lower())


def contar_linhas(caminho: Path) -> int:
    try:
        return len(caminho.read_text(encoding="utf-8", errors="ignore").splitlines())
    except OSError:
        return 0


def gerar_manual() -> str:
    raiz = encontrar_raiz()
    data = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
    arquivos = listar_arquivos(raiz)

    inventario = ["| Arquivo | Linhas | Tamanho |", "|---|---:|---:|"]
    for arquivo in arquivos:
        rel = arquivo.relative_to(raiz).as_posix()
        inventario.append(f"| `{rel}` | {contar_linhas(arquivo)} | {arquivo.stat().st_size} bytes |")

    return f"""# Manual Tecnico CriaVibe

Gerado em: {data}

## Stack

- PHP nativo
- HTML, CSS e JavaScript Vanilla
- Railway
- MySQL
- Cloudflare R2
- Docker

## Entradas Principais

- `index.html`
- `entrar.html`
- `painel.html`
- `galeria.html`
- `cliente.html`
- `api/config.php`
- `api/db_migrations.php`
- `Dockerfile`
- `router.php`

## Inventario

{chr(10).join(inventario)}
"""


def main() -> None:
    raiz = encontrar_raiz()
    destino = raiz / "documentacao" / "manual"
    destino.mkdir(parents=True, exist_ok=True)
    arquivo = destino / "Manual_Tecnico_CriaVibe.md"
    arquivo.write_text(gerar_manual(), encoding="utf-8")
    print(f"Manual gerado em: {arquivo}")


if __name__ == "__main__":
    main()
