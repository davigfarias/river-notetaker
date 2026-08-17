<div class="flex flex-1 items-center justify-center px-6">
    <flux:card class="w-full max-w-sm">
        <form wire:submit="entrar" class="space-y-8">
            <div class="space-y-2 text-center">
                <flux:heading size="lg">Acesso restrito</flux:heading>
                <flux:text>Digite o código de acesso.</flux:text>
            </div>

            <flux:otp
                wire:model="code"
                length="4"
                private
                label="Código de acesso"
                label:sr-only
                submit="auto"
                class="mx-auto"
            />
        </form>
    </flux:card>
</div>
