# Referencias Publicas - Ciclo de Vida de Documentacao de Software

Este arquivo registra referencias publicas do GitHub para orientar a metodologia `agente-willianbo` na documentacao de sistemas reais. O objetivo e manter registros tecnicos rastreaveis, revisaveis em pull request e proximos do codigo.

## Principios adotados

1. **Docs-as-code:** documentacao versionada no mesmo repositorio do codigo, revisada junto das mudancas tecnicas.
2. **ADR:** decisoes arquiteturais registradas com contexto, alternativas e consequencias.
3. **Changelog:** historico de mudancas organizado por versao, data ou fragmentos de alteracao.
4. **Runbooks:** procedimentos operacionais documentados para manutencao, resposta a incidentes e suporte.
5. **Works:** toda entrega precisa ter evidencia de funcionamento, como testes, logs, validacao manual ou captura.

## Referencias GitHub

| Referencia | Link | Uso na metodologia |
|------------|------|--------------------|
| Markdown Architectural Decision Records (MADR) | https://github.com/adr/madr | Base para registrar decisoes arquiteturais em Markdown, com templates e fluxo de uso em `docs/decisions`. |
| Architecture Decision Record | https://github.com/architecture-decision-record/architecture-decision-record | Referencia ampla para ADR, convencoes de nomes, uso com Git, trabalho em equipe e exemplos. |
| Log4brains | https://github.com/thomvaill/log4brains | Exemplo de docs-as-code para ADRs, com linha do tempo, publicacao e rastreabilidade por Git. |
| Scriv | https://github.com/nedbat/scriv | Exemplo de gestao de changelog por fragmentos, util para registrar entregas pequenas antes de consolidar versoes. |
| Runbooks MkDocs | https://github.com/Voronenko/runbooks-mkdocs | Exemplo de portal de runbooks e documentacao operacional com MkDocs. |
| Embedded Artistry Templates | https://github.com/embeddedartistry/templates | Colecao publica de templates para README, CONTRIBUTING, arquitetura, requisitos e documentacao de projeto. |

## Como aplicar no `agente-willianbo`

### Inicio da jornada
- Criar ou atualizar `documentacao/trabalho/trabalho_dia_mes_ano.md`.
- Registrar objetivo, task, escopo, riscos, dependencias e criterio de sucesso.
- Abrir check boxes de planejamento, implementacao, validacao e entrega.

### Durante a implementacao
- Registrar arquivos alterados e motivo de cada alteracao.
- Registrar decisoes importantes em formato ADR light.
- Registrar incidentes com sintoma, causa raiz, metodo de solucao e prevencao.

### Validacao Works
- Registrar comandos executados.
- Registrar resultado observado.
- Registrar validacao manual quando nao houver teste automatizado.
- Registrar pendencias quando a validacao for parcial.

### Fechamento
- Atualizar pendencias e proximos passos.
- Preparar resumo de commit.
- Perguntar ao usuario se pode realizar commit e push somente apos documentacao e validacao.

## Estrutura recomendada de documentacao

```text
documentacao/
  trabalho/
    trabalho_13_05_2026.md
  decisoes/
    0001-nome-da-decisao.md
  runbooks/
    deploy.md
    rollback.md
    incidentes.md
  releases/
    changelog.md
```

## Regra pratica

Toda task deve responder quatro perguntas:

1. **Task:** o que precisa ser feito?
2. **Implementacao:** como foi feito e quais arquivos mudaram?
3. **Check box:** quais criterios foram verificados?
4. **Works:** qual evidencia prova que funcionou?
