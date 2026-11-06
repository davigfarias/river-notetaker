# Decisões: Sessão de Princípios

Formato: decisão, alternativas, motivo.

1. **Estrutura de pastas.** Usar as pastas existentes (`app/Actions`, `app/Actions/Orchestrators`, `app/Enums`, `app/DTO`, `app/Ai`, `app/Jobs`, `app/Support`). Alternativa: `app/Domain/Principles` + `app/Infrastructure` da spec. Motivo: projeto proíbe pastas base novas sem aprovação; aprovado pelo dono.
2. **Isolamento.** Princípios continuam globais; tabelas novas de estudo têm `access_token_id` (cascade) e toda consulta filtra por ele. Alternativa: estudo global. Motivo: padrão de Conceitos/Notas; aprovado pelo dono.
3. **Princípio do tipo Concept.** Título = `Concepts::term`, enunciado = `Concepts::definition`. Alternativa: ignorar. Motivo: aprovado pelo dono; evita deixar princípios de fora.
4. **Agendador.** SM-2 simplificado como função pura nova. Alternativa: `ReviewSchedule`. Motivo: o existente é em aulas e só tem lembrou/travou; a spec exige Again/Hard/Good/Easy por dia.
5. **Commits.** Um por fase direto na `main`, sem push; testes só `--testsuite=Unit,Feature`.
6. **Versões.** Laravel 13 / Pest 5 (spec cita 12 / 4). Segue o instalado.
7. **Sem Service, sem Alpine/JS custom.** Conforme spec e projeto.

## Fases 2 e 3

8. **Sem interface para o agendador e sem contratos antecipados.** `Sm2Scheduler` é uma classe só; `ExerciseStrategy` e `PrincipleAiGateway` entraram quando passaram a ter uso.
9. **Composição com retrocesso.** `SessionComposer` usa busca com retrocesso (sessões de ~10 itens). O guloso falhava em algumas sementes onde existia solução.
10. **Heurística de descrições parecidas (7.2) omitida.** `RecallStatementToTitle` não é pulado quando duas descrições são muito parecidas. Limitação conhecida.
11. **IA só avalia `ExplainWhy` na Fase 3.** `CreateExample` com feedback fica para a Fase 4. Tipos que exigem IA saem do sorteio quando `principios.ai.enabled` é falso (`ExerciseType::isOfflineCapable`).
12. **Fallback em tempo de resposta mantém o exercício.** Se a IA falhar no meio de um `ExplainWhy`, não troco o tipo (o usuário já escreveu a resposta): mostro aviso em português simples, guardo o texto, ofereço "Avaliar de novo" e a autoavaliação segue normal. A troca de tipo acontece no planejamento (IA desligada).
13. **Limite diário por token via Cache** (`principios:ai:{token}:{data}`), contado por envio e não por tentativa interna. Estourou: fallback sem erro.
14. **Prompt injection.** `<` e `>` do texto do usuário viram `‹` e `›` antes de entrar no prompt, para ele não fechar a tag de dados; as instruções dizem que o conteúdo das tags é dado. A saída é validada por `AnswerEvaluation::fromArray` (nada da resposta da IA vira ação).
15. **Testes nunca chamam o provedor real.** `tests/Pest.php` faz `PrincipleAnswerEvaluator::fake()->preventStrayPrompts()` em todo teste Feature.
16. **`memory_limit` de 512M no `phpunit.xml`.** A suíte passou do limite padrão de 128M do PHP.
