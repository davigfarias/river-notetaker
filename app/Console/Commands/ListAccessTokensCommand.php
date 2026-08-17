<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ListAccessTokens;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('token:list')]
#[Description('Lista os tokens de acesso cadastrados')]
class ListAccessTokensCommand extends Command
{
    public function __construct(private readonly ListAccessTokens $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $outcome = $this->action->handle();

        if (! $outcome->success) {
            $this->error($outcome->message);

            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Nome', 'Último uso', 'Revogado em', 'Criado em'],
            $outcome->data->map(fn ($token) => [
                $token->id,
                $token->name,
                $token->lastUsedAt ?? '—',
                $token->revokedAt ?? '—',
                $token->createdAt,
            ])
        );

        return self::SUCCESS;
    }
}
