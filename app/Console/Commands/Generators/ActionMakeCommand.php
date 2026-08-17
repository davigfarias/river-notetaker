<?php

declare(strict_types=1);

namespace App\Console\Commands\Generators;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:action')]
#[Description('Cria uma nova Action')]
class ActionMakeCommand extends GeneratorCommand
{
    protected $name = 'make:action';

    protected $type = 'Action';

    protected function getStub(): string
    {
        return base_path('stubs/action.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Sobrescreve a Action caso já exista'],
        ];
    }
}
