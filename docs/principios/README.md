# Sessão de Princípios

Estudo ativo dos princípios: o usuário tenta responder antes de ver o gabarito, avalia a si mesmo e o
princípio volta por repetição espaçada. A IA gera exercícios e avalia respostas, mas nunca decide a nota.

## Como funciona

1. **Abrir:** botão "Estudar princípios" na aba Princípios (`/principios/estudar`). Escolha opcional de
   disciplina e categoria; "Todas" mistura tudo.
2. **Planejar:** `StartPrincipleSessionOrchestrator` junta vencidos (mais atrasados primeiro) e novos
   (limite por dia), escolhe o tipo de cada item (`ExerciseTypeChooser`) e intercala
   (`SessionComposer`). A sessão mistura níveis de Bloom desde a primeira exposição.
3. **Responder:** `SubmitExerciseAnswerOrchestrator` grava a resposta. Se o tipo usa IA, `EvaluateAttemptAnswer`
   pede a avaliação. A tela mostra gabarito, avaliação e a sugestão de nota.
4. **Avaliar-se:** o usuário escolhe Errei, Difícil, Bom ou Fácil (`CompleteExerciseReviewOrchestrator`).
   `Sm2Scheduler` calcula quando o princípio volta. "Errei" reaparece uma vez no fim da sessão.
5. **Retomar:** cada item respondido é gravado na hora; recarregar a página continua do próximo item.

## Tipos de exercício

| Tipo | Nível | Gera com IA | Avalia com IA | Resposta |
|---|---|---|---|---|
| Título → enunciado | Lembrar | não | não | autoavaliação |
| Enunciado → título | Lembrar | não | não | autoavaliação |
| Explicar por quê | Entender | não | sim | texto livre |
| Aplicar a um caso | Aplicar | sim (cache) | sim | texto livre |
| Distinguir princípios | Analisar | sim (cache) | só a justificativa | escolha + justificativa |
| Avaliar interpretação | Avaliar | sim (cache) | sim | texto livre |
| Criar exemplo | Criar | não | só comenta (sem nota) | texto livre |

## Configurar (`config/principios.php`)

| Chave | Padrão | Para que serve |
|---|---|---|
| `session.default_size` | 8 | itens por sessão |
| `session.max_new_per_day` | 3 | princípios novos por dia |
| `session.min_higher_order_ratio` | 0.5 | fatia mínima de Aplicar/Analisar/Avaliar/Criar |
| `ai.enabled` | `PRINCIPIOS_AI_ENABLED` (true) | desliga toda a IA; a sessão segue só com exercícios offline |
| `ai.stock_per_type` | 2 | exercícios gerados guardados por princípio e tipo |
| `ai.max_retries` | 1 | novas tentativas quando a IA devolve formato inválido |
| `ai.timeout_seconds` | 30 | espera máxima por chamada |
| `ai.confidence_threshold` | 0.6 | abaixo disso: "avaliação incerta", sem sugestão de nota |
| `ai.evaluate_distinction_rationale` | true | a IA avalia a justificativa de quem acertou a escolha |
| `ai.daily_limit_per_user` | 60 | chamadas de IA por dia e por token; estourou, segue sem IA |
| `scheduler.*` | 2.5 / 1.3 / 365 | facilidade inicial, mínima e intervalo máximo (SM-2) |

A IA usa o provedor padrão do Laravel AI SDK (`config/ai.php`, hoje Groq com `GROQ_API_KEY`).

## Quando a IA falha

Nada quebra a sessão. Sem IA, ou no limite do dia, ou com timeout, ou com resposta inválida:
- **Na avaliação:** aparece um aviso em português simples, a resposta digitada continua na tela, há o botão
  "Avaliar de novo" e a autoavaliação segue normal.
- **Na geração:** o item é trocado por um exercício offline e a tela diz "Sem IA agora; usando exercício offline".
- **No planejamento:** com a IA desligada, os tipos que dependem dela nem são sorteados.

## Cache e reposição de exercícios

Exercícios gerados ficam em `principle_exercises`, por token. O `source_hash` (título + enunciado + categoria)
decide se ainda valem: editar o princípio os marca como `Stale` e eles nunca são servidos. O cache entrega o menos
servido primeiro. Com fila de verdade (`QUEUE_CONNECTION` diferente de `sync`), abrir a sessão e servir do estoque
baixo despachem `GenerateExerciseStockJob` (um por token, princípio, tipo e hash). Sem worker, a geração é sob demanda.

## Segurança

- Estudo isolado por token (`access_token_id` em todas as tabelas novas); princípios continuam compartilhados.
- O texto do princípio é a única fonte de verdade nos prompts; `<` e `>` do texto do usuário são trocados antes de
  entrar no prompt; tudo entre tags é tratado como dado.
- Toda resposta da IA passa por DTO validado; ids que a IA devolve são conferidos contra o escopo (e os candidatos
  do exercício de distinguir vêm do código, não da IA).
- Logs guardam só metadados (ids, versão do prompt, latência, veredito), nunca o texto das respostas.

## Como testar

```
php artisan test --compact --testsuite=Unit,Feature
vendor/bin/pint --format agent
vendor/bin/phpstan analyse --memory-limit=1G
```

Nunca rode a suíte `Browser` (trava). Nenhum teste chama a IA real: `tests/Pest.php` bloqueia prompts sem fake.

Para experimentar com dados de exemplo (só acrescentam, nunca apagam):

```
php artisan db:seed --class=PrincipleStudyDemoSeeder
php artisan db:seed --class=PrincipleStudyAllTypesSessionSeeder
```

O segundo abre uma sessão com um item de cada tipo; depois abra `/principios/estudar`.

## Limites conhecidos

- A evidência científica vem de material mais factual que "princípios"; trate a mistura de níveis como hipótese e
  ajuste `config/principios.php` olhando "Seu desempenho nos últimos 30 dias".
- "Enunciado → título" não é pulado quando duas descrições são muito parecidas.
- O estoque pré-gerado só acontece com worker de fila; sem ele a primeira exibição de cada exercício espera a IA.
- A IA pode errar doutrina; por isso a nota é sempre do usuário e a avaliação mostra "incerta" quando a confiança é baixa.
- Não há testes `arch()`: o projeto não usa esse recurso.
