<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateAccessToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('token:generate {name : Nome/rótulo do token, também gravado nas notas criadas com ele}')]
#[Description('Gera um novo token de acesso de 4 dígitos')]
class GenerateAccessTokenCommand extends Command
{
    public function __construct(private readonly GenerateAccessToken $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $outcome = $this->action->handle($this->argument('name'));

        if (! $outcome->success) {
            $this->error($outcome->message);

            return self::FAILURE;
        }

        $this->info("Token gerado: {$outcome->data['plainTextToken']}");
        $this->warn('Copie agora — esse código não será exibido novamente.');

        return self::SUCCESS;
    }
}
