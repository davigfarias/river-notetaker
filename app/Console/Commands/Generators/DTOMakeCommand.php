<?php

declare(strict_types=1);

namespace App\Console\Commands\Generators;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:dto')]
#[\Illuminate\Console\Attributes\Description('Cria um novo DTO (Data Transfer Object)')]
class DTOMakeCommand extends GeneratorCommand
{
    protected $name = 'make:dto';

    protected $type = 'DTO';

    protected function getStub(): string
    {
        return base_path('stubs/dto.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\DTO';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Sobrescreve o DTO caso já exista'],
        ];
    }
}
