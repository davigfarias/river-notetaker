<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SetAccessTokenCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

#[Signature('token:set-code {id : ID do token} {code : Novo código de 4 dígitos} {--force : Altera sem pedir confirmação}')]
#[Description('Define manualmente o código de um token de acesso existente')]
class SetAccessTokenCodeCommand extends Command
{
    use ConfirmableTrait;

    public function __construct(private readonly SetAccessTokenCode $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirmToProceed('Você tem certeza que deseja alterar o código deste token?')) {
            return self::FAILURE;
        }

        $outcome = $this->action->handle((int) $this->argument('id'), (string) $this->argument('code'));

        if (! $outcome->success) {
            $this->error($outcome->message);

            return self::FAILURE;
        }

        $this->info($outcome->message);

        return self::SUCCESS;
    }
}
