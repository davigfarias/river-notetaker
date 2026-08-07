<?php

declare(strict_types=1);

namespace App\Console\Commands\Generators;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:repository')]
#[\Illuminate\Console\Attributes\Description('Cria uma nova classe de Repository')]
class RepositoryMakeCommand extends GeneratorCommand
{
    protected $name = 'make:repository';

    protected $type = 'Repository';

    protected function getStub(): string
    {
        return base_path('stubs/repository.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Repository';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Sobrescreve a classe caso já exista'],
        ];
    }
}
