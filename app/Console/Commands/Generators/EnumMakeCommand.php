<?php

declare(strict_types=1);

namespace App\Console\Commands\Generators;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:enum')]
#[\Illuminate\Console\Attributes\Description('Cria um novo Enum')]
class EnumMakeCommand extends GeneratorCommand
{
    protected $name = 'make:enum';

    protected $type = 'Enum';

    protected function getStub(): string
    {
        return $this->option('plain')
            ? base_path('stubs/enum.plain.stub')
            : base_path('stubs/enum.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Enums';
    }

    protected function getOptions(): array
    {
        return [
            ['plain', 'p', InputOption::VALUE_NONE, 'Cria um Enum puro, sem valor de backing (string)'],
            ['force', 'f', InputOption::VALUE_NONE, 'Sobrescreve o Enum caso já exista'],
        ];
    }
}
