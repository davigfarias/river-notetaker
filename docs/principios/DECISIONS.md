# Decisões: Sessão de Princípios

Formato: decisão, alternativas, motivo.

1. **Estrutura de pastas.** Usar as pastas existentes (`app/Actions`, `app/Actions/Orchestrators`, `app/Enums`, `app/DTO`, `app/Ai`, `app/Jobs`, `app/Support`). Alternativa: `app/Domain/Principles` + `app/Infrastructure` da spec. Motivo: projeto proíbe pastas base novas sem aprovação; aprovado pelo dono.
2. **Isolamento.** Princípios continuam globais; tabelas novas de estudo têm `access_token_id` (cascade) e toda consulta filtra por ele. Alternativa: estudo global. Motivo: padrão de Conceitos/Notas; aprovado pelo dono.
3. **Princípio do tipo Concept.** Título = `Concepts::term`, enunciado = `Concepts::definition`. Alternativa: ignorar. Motivo: aprovado pelo dono; evita deixar princípios de fora.
4. **Agendador.** SM-2 simplificado como função pura nova. Alternativa: `ReviewSchedule`. Motivo: o existente é em aulas e só tem lembrou/travou; a spec exige Again/Hard/Good/Easy por dia.
5. **Commits.** Um por fase direto na `main`, sem push; testes só `--testsuite=Unit,Feature`.
6. **Versões.** Laravel 13 / Pest 5 (spec cita 12 / 4). Segue o instalado.
7. **Sem Service, sem Alpine/JS custom.** Conforme spec e projeto.
