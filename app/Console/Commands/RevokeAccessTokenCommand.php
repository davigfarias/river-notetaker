<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RevokeAccessToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

#[Signature('token:revoke {id : ID do token a ser revogado} {--force : Revoga sem pedir confirmação}')]
#[Description('Revoga um token de acesso')]
class RevokeAccessTokenCommand extends Command
{
    use ConfirmableTrait;

    public function __construct(private readonly RevokeAccessToken $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirmToProceed('Você tem certeza que deseja revogar este token?')) {
            return self::FAILURE;
        }

        $outcome = $this->action->handle((int) $this->argument('id'));

        if (! $outcome->success) {
            $this->error($outcome->message);

            return self::FAILURE;
        }

        $this->info($outcome->message);

        return self::SUCCESS;
    }
}
