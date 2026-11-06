# Fase 0: Descoberta (Sessão de Princípios)

Somente leitura. Caminhos relativos à raiz do projeto.

## 1. Princípios hoje
- Hierarquia: `PrincipleTopic` (tema) > `PrincipleCategory` (opcional) > `Principle`. Tema liga a disciplinas N:N pela pivot `discipline_principle_topic`.
- `app/Models/Principle.php`: `principle_topic_id`, `principle_category_id`, `position`, `type` (`App\Enums\PrincipleType`: `Concept` | `Text`), `concept_id`, `title` (nullable), `body` (nullable).
- Tipo `Text` tem `title` + `body`. Tipo `Concept` só tem `concept_id`; texto vem de `Concepts::term` e `Concepts::definition`.
- Actions em `app/Actions` (AddTextPrinciple, ReorderPrinciple, DeletePrinciple...). Forms em `app/DTO/PrincipleTextForm.php`.
- Rotas: `/principios` (`pages::principios`), `/principios/{slug}` (`pages::principio`), ambas com `EnsureAccessTokenIsValid`.
- Páginas: `resources/views/pages/⚡principios`, `⚡principio`. Testes: `tests/Feature/PrincipleTest.php` e vizinhos. Factories: `PrincipleFactory`, `PrincipleTopicFactory`, `PrincipleCategoryFactory`. Sem seeders. Sem Policies.

## 2. Isolamento
- Não há usuário/equipe; há `AccessToken` (sessão `access_token_id`). Sem global scope, trait ou Policy.
- Padrão: coluna `access_token_id` + `->where('access_token_id', $id)` manual em cada Action.
- Princípios, temas, categorias e `quiz_questions` são GLOBAIS (sem `access_token_id`).

## 3. Repetição espaçada existente
- `app/Support/ReviewSchedule.php`: escada em AULAS (1-2-4-8), ancorada no dia da aula da disciplina. Só lembrou/travou. Não serve para Again/Hard/Good/Easy.
- `review_logs` polimórfica (`app/Models/ReviewLog.php`). Conceitos usam contadores (`ConceptRecallCounter`), sem data.
- Não existe SM-2.

## 4. Múltipla escolha
- `app/Ai/Agents/QuizQuestionGenerator.php` (structured output), `ValidateGeneratedQuestions`, `SubmitNoteQuizOrchestrator`. Acoplado a Notas; não reaproveitável direto. Distinção (7.5) será feita na própria estratégia (comparar id).

## 5. IA
- `laravel/ai` v0.11.0, provider padrão `groq` (`config/ai.php`), `GROQ_API_KEY`.
- Agentes em `app/Ai/Agents` (`Agent`, `HasStructuredOutput`, `Promptable`), chamados com `(new Agent)->prompt(...)`; resposta acessível como array.
- Testes: `Agent::fake([...])`, `assertPrompted`, `preventStrayPrompts()`. `tests/Pest.php` já faz fake do `QuizQuestionGenerator`.
- Falha: Job com `tries`, `failed()` loga; Actions capturam `\Exception` e devolvem `Outcome::failure`.

## 6. Actions / Orchestrators
- `app/Actions/<Verbo><Substantivo>.php`: `final readonly class`, método `handle`, retorno `App\Support\Outcome` (`success`, `failure`, `noViewMessage`, `data`).
- `app/Actions/SubActions` e `app/Actions/Orchestrators` (composição com `DB::transaction`, deps no construtor). Ex.: `AnswerConceptCardOrchestrator`.
- Não existe camada Service.

## 7. UI
- Livewire 4 single-file: `resources/views/pages/⚡nome/nome.php` + `.blade.php`, rota `pages::nome`, `#[Lazy]` + `@placeholder`.
- Modelo de estudo: `⚡estudar-conceitos` (cartão que vira, histórico, estados `empty|inactive|done`).
- Flux: heading, text, button, modal, input, badge, skeleton; toast via `Flux::toast`. Estado vazio: bloco `border-dashed` com ícone + heading + text.
- Strings pt-BR inline; sem pasta `lang/`.

## 8. Fila
- `QUEUE_CONNECTION=database`. Sem Horizon. Jobs de IA existem (`GenerateNoteQuizJob`).
- Produção pode não ter worker: geração de exercícios deve ter caminho sob demanda.

## 9. Qualidade
- PHP 8.4, Laravel 13, Livewire 4.3, Flux 2.15, Pest 5, PHPStan nível 7 (`app/`), Pint preset laravel, CI `composer ci:check`.
- Rodar só `--testsuite=Unit,Feature` (Browser trava).

## 10. Banco
- Dev sqlite, produção MySQL (índice ≤ 64 caracteres, DDL com commit implícito). Migrations `2026_MM_DD_HHMMSS_*`, `foreignId()->constrained()->cascadeOnDelete()`.

## Decisões de adaptação
Ver `DECISIONS.md`.
