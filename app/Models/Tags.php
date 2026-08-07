<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Sushi\Sushi;

/**
 * @property-read int|string $id
 * @property-read string $title
 */
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'tags')]
class Tags extends Model
{
    use Searchable, Sushi;

    /**
     * @var list<array{
     *     id: int,
     *     title: string,
     * }>
     */
    protected $rows = [
        [
            'id' => 1,
            'title' => 'Português',
        ],
        [
            'id' => 2,
            'title' => 'Metodologia',
        ],
        [
            'id' => 3,
            'title' => 'Psicologia',
        ],
        [
            'id' => 4,
            'title' => 'Filosofia',
        ],
        [
            'id' => 5,
            'title' => 'Antropologia',
        ],
        [
            'id' => 6,
            'title' => 'Planejamento',
        ],
        [
            'id' => 7,
            'title' => 'Psicopatologia',
        ],
        [
            'id' => 8,
            'title' => 'História',
        ],
        [
            'id' => 9,
            'title' => 'Antigo Testamento',
        ],
        [
            'id' => 10,
            'title' => 'Novo Testamento',
        ],
        [
            'id' => 11,
            'title' => 'Geografia',
        ],
        [
            'id' => 12,
            'title' => 'Arqueologia',
        ],
        [
            'id' => 13,
            'title' => 'Idiomas',
        ],
        [
            'id' => 14,
            'title' => 'Hermenêutica',
        ],
        [
            'id' => 15,
            'title' => 'Teologia Bíblica',
        ],
        [
            'id' => 16,
            'title' => 'Exegese',
        ],
        [
            'id' => 17,
            'title' => 'Cultura',
        ],
        [
            'id' => 18,
            'title' => 'Manuscritologia',
        ],
        [
            'id' => 19,
            'title' => 'Vocação',
        ],
        [
            'id' => 20,
            'title' => 'Poimênica',
        ],
        [
            'id' => 21,
            'title' => 'Aconselhamento',
        ],
        [
            'id' => 22,
            'title' => 'Liderança',
        ],
        [
            'id' => 23,
            'title' => 'Gestão',
        ],
        [
            'id' => 24,
            'title' => 'Missões',
        ],
        [
            'id' => 25,
            'title' => 'Evangelização',
        ],
        [
            'id' => 26,
            'title' => 'Educação',
        ],
        [
            'id' => 27,
            'title' => 'Pregação',
        ],
        [
            'id' => 28,
            'title' => 'Denominações',
        ],
        [
            'id' => 29,
            'title' => 'Capelania',
        ],
        [
            'id' => 30,
            'title' => 'Comunicação',
        ],
        [
            'id' => 31,
            'title' => 'Teologia Sistemática',
        ],
        [
            'id' => 32,
            'title' => 'Teologia do Culto',
        ],
        [
            'id' => 33,
            'title' => 'Ética',
        ],
        [
            'id' => 34,
            'title' => 'Símbolos de Fé',
        ],
        [
            'id' => 35,
            'title' => 'Apologética',
        ],
        [
            'id' => 36,
            'title' => 'Credos',
        ],
        [
            'id' => 37,
            'title' => 'Confissões',
        ],
    ];

    /**
     * @return array{
     *     id: int|string,
     *     title: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}
