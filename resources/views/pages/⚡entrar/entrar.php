<?php

use App\Actions\ValidateAccessToken;
use Flux\Flux;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Entrar')] class extends Component
{
    public string $code = '';

    public function entrar(ValidateAccessToken $action): void
    {
        $key = 'access-token-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 8)) {
            $this->code = '';
            Flux::toast(text: 'Muitas tentativas. Aguarde alguns minutos.', variant: 'danger');

            return;
        }

        RateLimiter::hit($key, 300);

        $outcome = $action->handle($this->code);

        if (! $outcome->success) {
            $this->code = '';
            Flux::toast(text: $outcome->message, variant: 'danger');

            return;
        }

        RateLimiter::clear($key);

        session(['access_token_id' => $outcome->data->id]);
        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }
};
