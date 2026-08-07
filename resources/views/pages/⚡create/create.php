<?php

use App\Actions\{GetAllDisciplines, GetTags, ObserveTerm};
use App\Actions\Orchestrators\SaveNote;
use App\DTO\{AdvicesDTO, ConceptsDTO, NotesDTO, ReferencesDTO};
use App\Enums\ReferencesIcon;
use Flux\Flux;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Criar uma Nova Nota')] class extends Component
{
    public bool $showDeleteModal = false;

    public NotesDTO $notes;

    protected function rules(): array
    {
        return [
            'notes.title' => 'required|string|min:3|max:255',
            'notes.discipline_id' => 'required|numeric',
            'notes.tags' => 'nullable|array',
            'notes.concepts' => 'nullable|array',
            'notes.impressions' => 'nullable|string',
            'notes.pastoral_advice' => 'nullable|array',
            'notes.life_experiences' => 'nullable|string',
            'notes.references' => 'nullable|array',
        ];
    }

    protected function messages(): array
    {
        return [
            'notes.title.required' => 'É necessário um título para sua nota',
            'notes.discipline_id.required' => 'Selecione uma disciplina',
        ];
    }

    public function mount(NotesDTO $notes)
    {
        $this->notes = clone $notes;
    }

    public function boot(
        GetAllDisciplines $getAllDisciplinesAction,
        GetTags $getTagsAction): void
    {
        $this->disciplines($getAllDisciplinesAction);
        $this->tags($getTagsAction);
    }

    #[Computed]
    public function disciplines(GetAllDisciplines $action)
    {
        $check = $action->handle();

        match ($check->success) {
            true => $this->disciplines = $check->data,
            false => $this->disciplines = null
        };
    }

    public function addConcept(): void
    {
        $this->notes->concepts[] = new ConceptsDTO;
    }

    public function removeConcept(int $index): void
    {
        unset($this->notes->concepts[$index]);
        $this->notes->concepts = array_values($this->notes->concepts);
    }

    public function verifyExistence(
        ObserveTerm $action,
        string $index)
    {
        $typed = $this->notes->concepts[$index] ?? null;

        if (! $typed)
        {
            return;
        }

        $check = $action
            ->handle(trim($typed->term));

        match ($check->data)
        {
            true => Flux::toast(text: 'O conceito já está registrado no sistema!', variant: 'alert'),
            false => null,
        };
    }

    public function addPastoralAdvice(): void
    {
        $this->notes->pastoral_advice[] = new AdvicesDTO;
    }

    public function removePastoralAdvice(int $index): void
    {
        unset($this->notes->pastoral_advice[$index]);
        $this->notes->pastoral_advice = array_values($this->notes->pastoral_advice);
    }

    public function addReference(): void
    {
        $reference = new ReferencesDTO;
        $reference->type = ReferencesIcon::BookOpen->value;

        $this->notes->references[] = $reference;
    }

    public function removeReference(int $index): void
    {
        unset($this->notes->references[$index]);
        $this->notes->references = array_values($this->notes->references);
    }

    private function filterConcepts(): array
    {
        $concepts = array_filter($this->notes->concepts ?? [], function ($concept)
        {
            return ! empty(trim($concept->term ?? ''))
                && ! empty(trim($concept->definition ?? ''));
        });

        return array_values($concepts);
    }

    private function filterPastoralAdvice(): array
    {
        $advices = array_filter($this->notes->pastoral_advice ?? [], function ($pastoral)
        {
            return ! empty(trim($pastoral->category ?? ''))
                && ! empty(trim($pastoral->advice ?? ''));
        });

        return array_values($advices);
    }

    private function filterReferences(): array
    {
        $refs = array_filter($this->notes->references ?? [], function ($ref)
        {
            return ! empty(trim($ref->type ?? ''))
                && ! empty(trim($ref->reference_text ?? ''));
        });

        return array_values($refs);
    }

    #[Computed]
    public function tags(GetTags $action)
    {
        $check = $action->handle();

        match ($check->success)
        {
            true => $this->tags = $check->data,
            false => $this->tags = null,
        };
    }

    public function addTag(string $title): void
    {
        $tags = collect($this->notes->tags);

        $this->notes->tags = $tags->contains($title)
            ? $tags->reject($title)->values()->all()
            : $tags->push($title)->all();
    }

    public function save(SaveNote $action): void
    {
        $this->validate();

        $this->notes->concepts = $this->filterConcepts();
        $this->notes->pastoral_advice = $this->filterPastoralAdvice();
        $this->notes->references = $this->filterReferences();

        $outcome = $action->handle($this->notes);

        if ($outcome->success)
        {
            Flux::toast(text: 'Anotação salva com sucesso.', variant: 'success');
            $this->redirectRoute('dashboard', navigate: true);
        } else
        {
            Flux::toast(text: $outcome->message, variant: 'danger');
        }
    }
};
