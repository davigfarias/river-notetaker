<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\CheckAccessTokenStillValid;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureAccessTokenIsValid
{
    public function __construct(private CheckAccessTokenStillValid $action) {}

    public function handle(Request $request, Closure $next): Response
    {
        $accessTokenId = $request->session()->get('access_token_id');

        if (! $accessTokenId || ! $this->action->handle((int) $accessTokenId)->success) {
            $request->session()->forget('access_token_id');

            return redirect()->route('entrar');
        }

        return $next($request);
    }
}
